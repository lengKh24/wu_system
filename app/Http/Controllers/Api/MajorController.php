<?php
namespace App\Http\Controllers\Api;

use App\Exports\MajorExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\MajorRequest;
use App\Http\Resources\MajorResource;
use App\Imports\MajorImport;
use App\Models\Major;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MajorController extends Controller
{
   public function __construct()
    {
        $this->name     = 'Major';
        $this->model    = Major::class;
        $this->resource = MajorResource::class;
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
            new MajorExport($request->only(['search', 'faculty_id'])),
            'majors'
        );
    }

    /**
     * Bulk major import — see MajorImport's docblock for the exact
     * column contract and what gets skipped vs created.
     */
    public function importFile(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new MajorImport();

        try {
            Excel::import($import, $validated['file']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Major import failed', ['error' => $e->getMessage()]);
            return no_data('The file could not be processed. Please check it is a valid, correctly formatted spreadsheet.', 422);
        }

        return has_data(['report' => $import->report()], 'Import complete.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MajorRequest $request)
    {
        return $this->save($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Major $major)
    {
        return $this->view($major);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MajorRequest $request, Major $major)
    {
        return $this->release($request, $major);
    }

    /**
     * Disable the specified resource from storage.
     */
    public function destroy(Major $major)
    {
        return $this->disable($major);
    }

    /**
     * Restore a soft-deleted of the resource.
     */
    public function restore(Major $major)
    {
        return $this->enable($major);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function force_destroy(Major $major)
    {
        return $this->clear($major);
    }

    /**
     * Permanently delete multiple majors in one request — same hard-delete
     * behavior as force_destroy() above, just batched. Pass {"all": true}
     * to wipe every major instead of listing ids individually. Deletes via
     * a raw DB::table() query rather than looping per-model, same
     * reasoning as SubjectController::bulkDestroy.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'all'   => 'sometimes|boolean',
            'ids'   => 'sometimes|array|min:1',
            'ids.*' => 'integer|exists:majors,id',
        ]);

        $all = $validated['all'] ?? false;
        if (! $all && empty($validated['ids'])) {
            return no_data('Either "ids" (non-empty array) or "all": true is required.', 422);
        }

        return execute(function () use ($validated, $all) {
            $query = Major::withTrashed();

            if (! $all) {
                $query->whereIn('id', $validated['ids']);
            }

            $ids   = $query->pluck('id');
            $count = $ids->count();

            \Illuminate\Support\Facades\DB::table('majors')->whereIn('id', $ids)->delete();

            return has_data(null, "{$count} major(s) permanently deleted.");
        });
    }
}
