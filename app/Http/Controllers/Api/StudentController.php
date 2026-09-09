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
            'campus',
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

        try {
            Excel::import($import, $validated['file']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Student import failed', ['error' => $e->getMessage()]);
            return no_data('The file could not be processed. Please check it is a valid, correctly formatted spreadsheet.', 422);
        }

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

    /**
     * Permanently delete multiple students in one request (same hard-delete
     * behavior as destroy() above). Pass {"all": true} to wipe every student
     * instead of listing ids individually.
     *
     * Deletes via the "people" table rather than looping per-student: people
     * -> students, and students -> guardians, are both cascadeOnDelete at
     * the DB level (see create_students_table / create_guardians_table
     * migrations), and addresses cascade on person_id — so one raw delete
     * on people cascades through all of it. This bypasses Eloquent's
     * SoftDeletes on Person (its ->delete() would only set deleted_at, which
     * does NOT fire the FK cascade), which is required to actually trigger
     * removal of the dependent rows.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'all'   => 'sometimes|boolean',
            'ids'   => 'sometimes|array|min:1',
            'ids.*' => 'integer|exists:students,id',
        ]);

        $all = $validated['all'] ?? false;
        if (! $all && empty($validated['ids'])) {
            return no_data('Either "ids" (non-empty array) or "all": true is required.', 422);
        }

        return execute(function () use ($validated, $all) {
            $query = Student::withTrashed();

            if (! $all) {
                $query->whereIn('id', $validated['ids']);
            }

            $personIds = $query->pluck('person_id');
            $count     = $personIds->count();

            \Illuminate\Support\Facades\DB::table('people')->whereIn('id', $personIds)->delete();

            return has_data(null, "{$count} student(s) permanently deleted.");
        });
    }
}
