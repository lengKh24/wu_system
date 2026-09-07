<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Module => label.
     */
    protected const MODULES = [
        'student'     => 'Student',
        'batch'       => 'Batch',
        'group'       => 'Group',
        'major'       => 'Major',
        'faculty'     => 'Faculty',
        'campus'      => 'Campus',
        'shift'       => 'Shift',
        'app-status'  => 'Student Status Options',
        'lecturer'    => 'Lecturer',
        'subject'     => 'Subject',
        'state-exam'  => 'State Exam',
        'certificate' => 'Certificate',
        'role'        => 'Roles & Permissions',
        'activity'    => 'Activity Log',
        'alert'       => 'Alerts',

        // Retake Exam module (2026-09)
        'retake-term'         => 'Retake Term',
        'exam-type'           => 'Exam Type',
        'retake-batch'        => 'Retake Batch',
        'retake-registration' => 'Retake Registration',
        'retake-payment'      => 'Retake Payment (Student Affairs)',
        'retake-score'        => 'Retake Score',
        'retake-cs'           => 'Retake Customer Service',
        'payment-batch'       => 'Payment Batch',
        'payment-entry'       => 'Payment Entry',
    ];

    protected const ACTIONS = ['view', 'create', 'edit', 'delete'];

    protected const ROLE_MODULES = [
        'Enrollment Officer'         => ['student', 'batch', 'group', 'major', 'faculty', 'campus', 'shift', 'app-status'],
        'Exam Officer'               => ['state-exam', 'lecturer', 'subject'],
        'Score/Certificate Officer'  => ['certificate'],
        'Registrar Office'           => ['retake-term', 'exam-type', 'retake-batch'],
    ];

    /**
     * Per-role EXTRA permissions, additive to ROLE_MODULES above, for a
     * role that only needs SOME actions on a module rather than the full
     * view/create/edit/delete set that ROLE_MODULES always grants.
     *
     * This exists specifically for the retake exam module's RBAC split:
     * Student Affairs can see registrations but only ever pays/invites
     * (never scores, never deletes); Score can see registrations but only
     * ever scores (never touches payment); Accounting only reconciles
     * payment_entries and can never flip payment_status itself; Customer
     * Service is read-only on registered students and nothing else.
     */
    protected const ROLE_PERMISSIONS = [
        'Registrar Office' => [
            'retake-registration.view', 'retake-registration.create',
            'retake-registration.edit', 'retake-registration.delete',
        ],
        'Student Affairs' => [
            'retake-registration.view', 'retake-payment.edit',
            'payment-batch.view', 'payment-batch.create', 'payment-batch.edit',
        ],
        'Score/Certificate Officer' => [
            'retake-registration.view', 'retake-score.edit',
        ],
        'Accounting' => [
            'retake-registration.view',
            // Read-only — ACC needs to see which payment_batch to
            // reconcile against, but creating/editing a batch stays SA's
            // job (see the payment_batches migration docblock).
            'payment-batch.view',
            'payment-entry.view', 'payment-entry.create', 'payment-entry.edit',
        ],
        'Customer Service' => [
            'retake-cs.view',
        ],
    ];

    public function run(): void
    {
        $allPermissions = [];

        foreach (array_keys(self::MODULES) as $module) {
            foreach (self::ACTIONS as $action) {
                $allPermissions[] = Permission::firstOrCreate(['name' => "{$module}.{$action}"]);
            }
        }

        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions($allPermissions);

        foreach (self::ROLE_MODULES as $roleName => $modules) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $permissions = Permission::whereIn(
                'name',
                collect($modules)->flatMap(fn ($m) => collect(self::ACTIONS)->map(fn ($a) => "{$m}.{$a}"))->all()
            )->get();
            // Additive, not a replace — re-running this seeder (e.g. after
            // adding a new module) must never wipe out permissions someone
            // assigned by hand through the /role UI afterward.
            $role->givePermissionTo($permissions);
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionNames) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $permissions = Permission::whereIn('name', $permissionNames)->get();
            $role->givePermissionTo($permissions);
        }

        $adminUser = User::where('email', 'admin@system.me')->first();
        $adminUser?->syncRoles([$admin]);
    }
}
