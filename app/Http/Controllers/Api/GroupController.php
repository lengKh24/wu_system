<?php
namespace App\Http\Controllers\Api;

use App\Exports\GroupExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GroupRequest;
use App\Http\Resources\GroupResource;
use App\Imports\GroupImport;
use App\Models\Group;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->name     = 'Group';
        $this->model    = Group::class;
        $this->resource = GroupResource::class;
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
            new GroupExport($request->only(['search'])),
            'groups'
        );
    }

    /**
     * Bulk group import — see GroupImport's docblock for the exact
     * column contract and what gets skipped vs created.
     */
    public function importFile(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new GroupImport();

        try {
            Excel::import($import, $validated['file']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Group import failed', ['error' => $e->getMessage()]);
            return no_data('The file could not be processed. Please check it is a valid, correctly formatted spreadsheet.', 422);
        }

        return has_data(['report' => $import->report()], 'Import complete.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GroupRequest $request)
    {
        return $this->save($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Group $group)
    {
        return $this->view($group);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GroupRequest $request, Group $group)
    {
        return $this->release($request, $group);
    }

    /**
     * Disable the specified resource from storage.
     */
    public function destroy(Group $group)
    {
        return $this->disable($group);
    }

    /**
     * Restore a soft-deleted of the resource.
     */
    public function restore(Group $group)
    {
        return $this->enable($group);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function force_destroy(Group $group)
    {
        return $this->clear($group);
    }

    /**
     * Permanently delete multiple groups in one request — same hard-delete
     * behavior as force_destroy() above, just batched. Pass {"all": true}
     * to wipe every group instead of listing ids individually. Deletes via
     * a raw DB::table() query rather than looping per-model, same
     * reasoning as SubjectController::bulkDestroy.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'all'   => 'sometimes|boolean',
            'ids'   => 'sometimes|array|min:1',
            'ids.*' => 'integer|exists:groups,id',
        ]);

        $all = $validated['all'] ?? false;
        if (! $all && empty($validated['ids'])) {
            return no_data('Either "ids" (non-empty array) or "all": true is required.', 422);
        }

        return execute(function () use ($validated, $all) {
            $query = Group::withTrashed();

            if (! $all) {
                $query->whereIn('id', $validated['ids']);
            }

            $ids   = $query->pluck('id');
            $count = $ids->count();

            \Illuminate\Support\Facades\DB::table('groups')->whereIn('id', $ids)->delete();

            return has_data(null, "{$count} group(s) permanently deleted.");
        });
    }
}
