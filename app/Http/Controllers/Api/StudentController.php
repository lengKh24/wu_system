<?php
namespace App\Http\Controllers\Api;

use App\Exports\StudentExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Http\Resources\StudentResource;
use App\Imports\StudentImport;
use App\Models\Person;
use App\Models\Student;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->name          = 'Student';
        $this->model         = Student::class;
        $this->resource      = StudentResource::class;
        $this->relationships = array_merge([
            'person',
            'batch',
            'major',
            'shift',
            'major.faculty',
            'group',
            'status',
            'guardians',
        ], $this->withPerson());
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->list($request, function ($query) use ($request) {
            if ($request->payment === Student::YEARLY) {
                return $query->yearly();
            }

            if ($request->payment === Student::SEMESTER) {
                return $query->semester();
            }

            return $query;
        });
    }

    /**
     * Exports whatever the list is currently filtered to (search/payment) —
     * same filter contract as index() above. Named exportList, not export,
     * since export(object, string) is a reserved method name on the base
     * Controller (see RetakeBatchController::importFile()'s docblock for
     * the same reasoning — reusing a base method name with an
     * incompatible signature is a fatal error, not a warning).
     */
    public function exportList(Request $request)
    {
        return $this->export(
            new StudentExport($request->only(['search', 'payment'])),
            'students'
        );
    }

    /**
     * Bulk enrollment import — see StudentImport's docblock for the exact
     * column contract and what gets skipped vs created.
     */
    public function importFile(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new StudentImport();
        Excel::import($import, $validated['file']);

        return has_data(['report' => $import->report()], 'Import complete.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StudentRequest $request)
    {
        return execute(function () use ($request) {
            $data    = $request->validated();
            $person  = Person::create($data);
            $student = $person->student()->create($data);

            $person->addresses()->createMany($data['addresses']);
            $student->guardians()->createMany($data['guardians']);

            return new StudentResource($student->load($this->relationships));
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        return new StudentResource($student->load($this->relationships));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StudentRequest $request, Student $student)
    {
        return execute(function () use ($request, $student) {
            $data   = $request->validated();
            $person = $student->person;

            $person->update($data);
            $student->update($data);

            $this->sync_addresses($person, $data['addresses'] ?? []);
            $this->sync_guardians($student, $data['guardians'] ?? []);

            return new StudentResource($student->load($this->relationships));
        });
    }

    /**
     * Disable the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        return execute(function () use ($student) {
            $student->person->addresses()->forceDelete(); // if addresses relation is via person, adjust accordingly
            $student->guardians()->forceDelete();
            $student->person->forceDelete();
            $student->forceDelete();

            return has_data(null, 'Permanently deleted.');
        });
    }

    /**
     * Restore a soft-deleted of the resource.
     */
    public function restore(Student $student)
    {
        return execute(function () use ($student) {
            $student->restore();
            $student->person()->withTrashed()->first()?->restore();

            return new StudentResource($student->load($this->relationships));
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function trash(Student $student)
    {
        return execute(function () use ($student) {
            $student->delete();
            $student->person->delete();

            return has_data(null, 'Moved to trash.');
        });
    }
}
