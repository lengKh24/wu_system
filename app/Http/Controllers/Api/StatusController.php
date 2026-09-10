<?php
namespace App\Http\Controllers\Api;

use App\Exports\StatusExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StatusRequest;
use App\Http\Resources\StatusResource;
use App\Imports\StatusImport;
use App\Models\Status;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StatusController extends Controller
{
    public function __construct()
    {
        $this->name     = 'Status';
        $this->model    = Status::class;
        $this->resource = StatusResource::class;
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
            new StatusExport($request->only(['search'])),
            'statuses'
        );
    }

    /**
     * Bulk status import — see StatusImport's docblock for the exact
     * column contract and what gets skipped vs created (matched on
     * name_en, not shortcut — see that class for why).
     */
    public function importFile(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new StatusImport();

        try {
            Excel::import($import, $validated['file']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Status import failed', ['error' => $e->getMessage()]);
            return no_data('The file could not be processed. Please check it is a valid, correctly formatted spreadsheet.', 422);
        }

        return has_data(['report' => $import->report()], 'Import complete.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StatusRequest $request)
    {
        return $this->save($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Status $status)
    {
        return $this->view($status);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StatusRequest $request, Status $status)
    {
        return $this->release($request, $status);
    }

    /**
     * Disable the specified resource from storage.
     */
    public function destroy(Status $status)
    {
        return $this->disable($status);
    }

    /**
     * Restore a soft-deleted of the resource.
     */
    public function restore(Status $status)
    {
        return $this->enable($status);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function force_destroy(Status $status)
    {
        return $this->clear($status);
    }

    /**
     * Permanently delete multiple statuses in one request — same
     * hard-delete behavior as force_destroy() above, just batched. Pass
     * {"all": true} to wipe every status instead of listing ids
     * individually. Deletes via a raw DB::table() query rather than
     * looping per-model, same reasoning as SubjectController::bulkDestroy.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'all'   => 'sometimes|boolean',
            'ids'   => 'sometimes|array|min:1',
            'ids.*' => 'integer|exists:statuses,id',
        ]);

        $all = $validated['all'] ?? false;
        if (! $all && empty($validated['ids'])) {
            return no_data('Either "ids" (non-empty array) or "all": true is required.', 422);
        }

        return execute(function () use ($validated, $all) {
            $query = Status::withTrashed();

            if (! $all) {
                $query->whereIn('id', $validated['ids']);
            }

            $ids   = $query->pluck('id');
            $count = $ids->count();

            \Illuminate\Support\Facades\DB::table('statuses')->whereIn('id', $ids)->delete();

            return has_data(null, "{$count} status(es) permanently deleted.");
        });
    }
}
