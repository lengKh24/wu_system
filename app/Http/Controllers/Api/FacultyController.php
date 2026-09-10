<?php
namespace App\Http\Controllers\Api;

use App\Exports\FacultyExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyRequest;
use App\Http\Resources\FacultyResource;
use App\Imports\FacultyImport;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FacultyController extends Controller
{
    public function __construct()
    {
        $this->name          = 'Faculty';
        $this->model         = Faculty::class;
        $this->resource      = FacultyResource::class;
        $this->relationships = 'majors';
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->list($request);
    }

    public function exportList(Request $request)
    {
        return $this->export(
            new FacultyExport($request->only(['search'])),
            'faculties'
        );
    }

    /**
     * Bulk faculty import — see FacultyImport's docblock for the exact
     * column contract and what gets skipped vs created.
     */
    public function importFile(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new FacultyImport();

        try {
            Excel::import($import, $validated['file']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Faculty import failed', ['error' => $e->getMessage()]);
            return no_data('The file could not be processed. Please check it is a valid, correctly formatted spreadsheet.', 422);
        }

        return has_data(['report' => $import->report()], 'Import complete.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FacultyRequest $request)
    {
        return $this->save($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Faculty $faculty)
    {
        return $this->view($faculty);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FacultyRequest $request, Faculty $faculty)
    {
        return $this->release($request, $faculty);
    }

    /**
     * Disable the specified resource from storage.
     */
    public function destroy(Faculty $faculty)
    {
        return $this->disable($faculty);
    }

    /**
     * Restore a soft-deleted of the resource.
     */
    public function restore(Faculty $faculty)
    {
        return $this->enable($faculty);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function force_destroy(Faculty $faculty)
    {
        return $this->clear($faculty);
    }

    /**
     * Permanently delete multiple faculties in one request — same
     * hard-delete behavior as force_destroy() above, just batched. Pass
     * {"all": true} to wipe every faculty instead of listing ids
     * individually. Deletes via a raw DB::table() query rather than
     * looping per-model, same reasoning as SubjectController::bulkDestroy.
     *
     * WARNING: majors.faculty_id and subjects.faculty_id both
     * cascadeOnDelete() — deleting a faculty here DB-cascades to delete
     * every major and subject under it too.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'all'   => 'sometimes|boolean',
            'ids'   => 'sometimes|array|min:1',
            'ids.*' => 'integer|exists:faculties,id',
        ]);

        $all = $validated['all'] ?? false;
        if (! $all && empty($validated['ids'])) {
            return no_data('Either "ids" (non-empty array) or "all": true is required.', 422);
        }

        return execute(function () use ($validated, $all) {
            $query = Faculty::withTrashed();

            if (! $all) {
                $query->whereIn('id', $validated['ids']);
            }

            $ids   = $query->pluck('id');
            $count = $ids->count();

            \Illuminate\Support\Facades\DB::table('faculties')->whereIn('id', $ids)->delete();

            return has_data(null, "{$count} faculty(ies) permanently deleted.");
        });
    }
}
