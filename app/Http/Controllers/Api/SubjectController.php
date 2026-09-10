<?php
namespace App\Http\Controllers\Api;

use App\Exports\SubjectExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Imports\SubjectImport;
use App\Models\Subject;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->name          = 'Subject';
        $this->model         = Subject::class;
        $this->resource      = SubjectResource::class;
        $this->relationships = 'faculty';
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
            new SubjectExport($request->only(['search', 'faculty_id'])),
            'subjects'
        );
    }

    /**
     * Bulk subject import — see SubjectImport's docblock for the exact
     * column contract and what gets skipped vs created.
     */
    public function importFile(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new SubjectImport();

        try {
            Excel::import($import, $validated['file']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Subject import failed', ['error' => $e->getMessage()]);
            return no_data('The file could not be processed. Please check it is a valid, correctly formatted spreadsheet.', 422);
        }

        return has_data(['report' => $import->report()], 'Import complete.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SubjectRequest $request)
    {
        return $this->save($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Subject $subject)
    {
        return $this->view($subject);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SubjectRequest $request, Subject $subject)
    {
        return $this->release($request, $subject);
    }

    /**
     * Disable the specified resource from storage.
     */
    public function destroy(Subject $subject)
    {
        return $this->disable($subject);
    }

    /**
     * Restore a soft-deleted of the resource.
     */
    public function restore(Subject $subject)
    {
        return $this->enable($subject);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function force_destroy(Subject $subject)
    {
        return $this->clear($subject);
    }

    /**
     * Permanently delete multiple subjects in one request — same hard-delete
     * behavior as force_destroy() above, just batched. Pass {"all": true} to
     * wipe every subject instead of listing ids individually.
     *
     * Deletes via a raw DB::table() query rather than looping per-model:
     * bypasses Eloquent's SoftDeletes (a plain ->delete() would only set
     * deleted_at, which does NOT fire the subject_id FK cascade on
     * retake_registrations/deletion_log — see those tables' migrations),
     * which is required to actually cascade-remove any dependent rows.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'all'   => 'sometimes|boolean',
            'ids'   => 'sometimes|array|min:1',
            'ids.*' => 'integer|exists:subjects,id',
        ]);

        $all = $validated['all'] ?? false;
        if (! $all && empty($validated['ids'])) {
            return no_data('Either "ids" (non-empty array) or "all": true is required.', 422);
        }

        return execute(function () use ($validated, $all) {
            $query = Subject::withTrashed();

            if (! $all) {
                $query->whereIn('id', $validated['ids']);
            }

            $ids   = $query->pluck('id');
            $count = $ids->count();

            \Illuminate\Support\Facades\DB::table('subjects')->whereIn('id', $ids)->delete();

            return has_data(null, "{$count} subject(s) permanently deleted.");
        });
    }
}
