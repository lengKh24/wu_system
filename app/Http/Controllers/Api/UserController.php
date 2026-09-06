<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->name     = 'User';
        $this->model    = User::class;
        $this->resource = UserResource::class;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit    = $request->integer('per_page', 10);
        $response = $this->model::query()->with('roles');

        if ($sort = $request->input('sort')) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $column    = ltrim($sort, '-');
            $response->orderBy($column, $direction);
        } else {
            $response->latest();
        }

        return $this->resource::collection($response->paginate($limit)->onEachSide(1));
    }

    /**
     * Reassigns a user's role(s) — the only thing this admin surface edits
     * about another account. A user's own profile page handles name/password.
     */
    public function updateRoles(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles'   => 'array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user->syncRoles($validated['roles'] ?? []);

        return new UserResource($user->fresh('roles'));
    }

    /**
     * Deletes a staff account. Route-gated to permission:role.delete
     * (Admin, by default) via api_routes()'s module map.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return no_data('You cannot delete your own account.', 422);
        }

        if ($user->hasRole('Admin') && User::role('Admin')->count() <= 1) {
            return no_data('Cannot delete the last remaining Admin account.', 422);
        }

        $user->delete();

        return has_data(null, 'User deleted.');
    }

    /**
     * Sets a new password for a staff account — an admin action for staff
     * who are locked out, not a self-service "forgot password" flow.
     */
    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return has_data(null, 'Password reset.');
    }
}
