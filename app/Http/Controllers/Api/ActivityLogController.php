<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Lists every create/update/delete recorded across the app — who did
     * it, when, on which record, and (for updates) exactly which fields
     * changed. Filterable by module, user, and action so it stays usable
     * once the log has thousands of rows.
     */
    public function index(Request $request)
    {
        $limit = $request->integer('per_page', 20);

        $query = Activity::query()->with('causer')->latest();

        if ($module = $request->input('module')) {
            $query->where('log_name', $module);
        }

        if ($event = $request->input('event')) {
            $query->where('event', $event);
        }

        if ($causerId = $request->input('causer_id')) {
            $query->where('causer_id', $causerId);
        }

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $activities = $query->paginate($limit)->onEachSide(1);

        return response()->json([
            'data' => collect($activities->items())->map(fn (Activity $a) => $this->toArray($a))->values(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page'    => $activities->lastPage(),
                'total'        => $activities->total(),
            ],
        ]);
    }

    /**
     * Distinct module names (log_name) actually present in the log, for
     * the filter dropdown — no point listing modules nothing happened in.
     */
    public function modules()
    {
        $modules = Activity::query()->distinct()->orderBy('log_name')->pluck('log_name');
        return has_data($modules);
    }

    /**
     * Users who have at least one logged action, for the filter dropdown.
     */
    public function users()
    {
        $userIds = Activity::query()->whereNotNull('causer_id')->distinct()->pluck('causer_id');
        $users   = User::whereIn('id', $userIds)->get(['id', 'name', 'email']);
        return has_data($users);
    }

    protected function toArray(Activity $activity): array
    {
        $old = $activity->properties->get('old', []);
        $new = $activity->properties->get('attributes', []);

        return [
            'id'          => $activity->id,
            'module'      => $activity->log_name,
            'event'       => $activity->event,
            'description' => $activity->description,
            'subject_type' => $activity->subject_type ? class_basename($activity->subject_type) : null,
            'subject_id'  => $activity->subject_id,
            'causer'      => $activity->causer ? [
                'id'    => $activity->causer->id,
                'name'  => $activity->causer->name,
                'email' => $activity->causer->email,
            ] : null,
            'changes'     => $this->diff($old, $new),
            'created_at'  => $activity->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Old/new side by side, only for fields that actually differ — so the
     * UI can show "status: pending → completed" instead of a full dump of
     * every attribute on every update.
     */
    protected function diff(array $old, array $new): array
    {
        $fields = array_unique([...array_keys($old), ...array_keys($new)]);
        $diff   = [];

        foreach ($fields as $field) {
            $before = $old[$field] ?? null;
            $after  = $new[$field] ?? null;
            if ($before !== $after) {
                $diff[$field] = ['old' => $before, 'new' => $after];
            }
        }

        return $diff;
    }
}
