<?php
namespace App\Http\Controllers\Api;

use App\Exports\ShiftExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Imports\ShiftImport;
use App\Models\Shift;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ShiftController extends Controller
{
    public function __construct()
    {
        $this->name     = 'Shift';
        $this->model    = Shift::class;
        $this->resource = ShiftResource::class;
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
            new ShiftExport($request->only(['search'])),
            'shifts'
        );
    }

    /**
     * Bulk shift import — see ShiftImport's docblock for the exact
     * column contract and what gets skipped vs created.
     */
    public function importFile(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new ShiftImport();

        try {
            Excel::import($import, $validated['file']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Shift import failed', ['error' => $e->getMessage()]);
            return no_data('The file could not be processed. Please check it is a valid, correctly formatted spreadsheet.', 422);
        }

        return has_data(['report' => $import->report()], 'Import complete.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShiftRequest $request)
    {
        return $this->save($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Shift $shift)
    {
        return $this->view($shift);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ShiftRequest $request, Shift $shift)
    {
        return $this->release($request, $shift);
    }

    /**
     * Disable the specified resource from storage.
     */
    public function destroy(Shift $shift)
    {
        return $this->disable($shift);
    }

    /**
     * Restore a soft-deleted of the resource.
     */
    public function restore(Shift $shift)
    {
        return $this->enable($shift);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function force_destroy(Shift $shift)
    {
        return $this->clear($shift);
    }

    /**
     * Permanently delete multiple shifts in one request — same hard-delete
     * behavior as force_destroy() above, just batched. Pass {"all": true}
     * to wipe every shift instead of listing ids individually. Deletes via
     * a raw DB::table() query rather than looping per-model, same
     * reasoning as SubjectController::bulkDestroy.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'all'   => 'sometimes|boolean',
            'ids'   => 'sometimes|array|min:1',
            'ids.*' => 'integer|exists:shifts,id',
        ]);

        $all = $validated['all'] ?? false;
        if (! $all && empty($validated['ids'])) {
            return no_data('Either "ids" (non-empty array) or "all": true is required.', 422);
        }

        return execute(function () use ($validated, $all) {
            $query = Shift::withTrashed();

            if (! $all) {
                $query->whereIn('id', $validated['ids']);
            }

            $ids   = $query->pluck('id');
            $count = $ids->count();

            \Illuminate\Support\Facades\DB::table('shifts')->whereIn('id', $ids)->delete();

            return has_data(null, "{$count} shift(s) permanently deleted.");
        });
    }
}
