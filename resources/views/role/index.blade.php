@extends('layouts.dashboard')
@section('title', 'Roles & Permissions')
@section('content')

    <script>
        window.CURRENT_USER_ID = {{ auth()->id() }};
        window.CAN_DELETE_USER = @json(auth()->user()->can('role.delete'));
        window.CAN_RESET_PASSWORD = @json(auth()->user()->can('role.edit'));
    </script>

    <x-core.page-header title="តួនាទី និងសិទ្ធិចូលប្រើ (Roles & Permissions)"
        subtitle="គ្រប់គ្រងអ្វីដែលបុគ្គលិកនីមួយៗអាចមើល ឬកែប្រែបាន (Control what each staff member can see and edit)" />

    <div class="space-y-10">
        {{-- Roles --}}
        <div>
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2.5">
                    <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12a3 3 0 100-6 3 3 0 000 6zM17.804 21c.512-.75.79-1.638.79-2.556C18.594 15.36 15.964 13 12.75 13H12a4.5 4.5 0 00-4.5 4.5c0 .918.278 1.806.79 2.556M15 6a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-neutral-800 dark:text-white leading-tight">តួនាទី (Roles)</h3>
                        <p class="text-[11px] text-neutral-400">Reusable permission bundles you assign to staff</p>
                    </div>
                </div>
                <button type="button" onclick="RolesModule.openCreate()"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all active:scale-95">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    បង្កើតតួនាទីថ្មី (Create Role)
                </button>
            </div>

            <div id="roles-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"></div>
        </div>

        {{-- Staff Accounts --}}
        <div>
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2.5">
                    <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-600/10 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-neutral-800 dark:text-white leading-tight">បុគ្គលិក (Staff Accounts)</h3>
                        <p id="staff-count-label" class="text-[11px] text-neutral-400">&nbsp;</p>
                    </div>
                </div>
                <div class="relative w-full max-w-xs">
                    <div class="absolute inset-y-0 left-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
                        </svg>
                    </div>
                    <input id="staffSearchInput" type="text" placeholder="ស្វែងរកបុគ្គលិក (Search staff)..."
                        class="block w-full p-2.5 ps-9 text-sm text-neutral-900 border border-neutral-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-neutral-900 dark:border-white/10 dark:placeholder-neutral-500 dark:text-white transition-all">
                </div>
            </div>

            <div id="loading-overlay"
                class="hidden py-10 items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <div id="users-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4"></div>
        </div>
    </div>

    <x-ui.modal id="roleModal" title="បង្កើតតួនាទីថ្មី (Create Role)" formId="roleForm" maxWidth="max-w-3xl">
        <input type="hidden" name="id">
        <div>
            <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1.5">
                ឈ្មោះតួនាទី (Role Name)</label>
            <input required type="text" name="name" placeholder="e.g., Exam Officer"
                class="w-full px-4 py-2.5 text-sm bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                    សិទ្ធិចូលប្រើម៉ូឌុល (Module Permissions)</label>
                <button type="button" id="toggleAllPermissions"
                    class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                    ជ្រើសរើសទាំងអស់ (Select All)
                </button>
            </div>

            <div class="grid grid-cols-4 gap-2 px-4 pb-2 text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                <div class="col-span-1"></div>
                <div class="col-span-3 grid grid-cols-4 text-center">
                    <span>View</span>
                    <span>Create</span>
                    <span>Edit</span>
                    <span>Delete</span>
                </div>
            </div>

            <div id="permission-grid"
                class="divide-y divide-neutral-100 dark:divide-white/5 border border-neutral-200 dark:border-white/10 rounded-xl max-h-[42vh] overflow-y-auto scrollbar-thin scrollbar-thumb-neutral-200 dark:scrollbar-thumb-white/10">
            </div>
        </div>

        <x-slot:footer>
            <button type="button" onclick="AppModal.toggle(false)"
                class="px-5 py-2.5 text-sm font-medium text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-white/5 rounded-xl transition-all">
                បោះបង់ (Cancel)
            </button>
            <button type="submit" form="roleForm" id="roleSubmitBtn"
                class="px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 active:scale-95 rounded-xl transition-all">
                រក្សាទុក (Save Role)
            </button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal id="resetPasswordModal" cardId="resetPasswordModalCard" titleId="resetPasswordModalTitle"
        closeFn="ResetPasswordModal"
        title="កំណត់ពាក្យសម្ងាត់ថ្មី (Reset Password)" formId="resetPasswordForm" maxWidth="max-w-md">
        <input type="hidden" name="user_id">
        <p id="resetPasswordUserLabel" class="text-sm text-neutral-500 dark:text-neutral-400"></p>

        <div>
            <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1.5">
                ពាក្យសម្ងាត់ថ្មី (New Password)</label>
            <div class="relative">
                <input required type="password" name="password" id="resetPasswordInput" minlength="8"
                    placeholder="យ៉ាងតិច ៨ តួអក្សរ (At least 8 characters)"
                    class="w-full px-4 py-2.5 pr-11 text-sm bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                <button type="button" id="toggleResetPasswordVisibility"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
            </div>
            <p class="text-[11px] text-neutral-400 mt-1.5">អ្នកប្រើប្រាស់នេះនឹងត្រូវប្រើពាក្យសម្ងាត់ថ្មីនេះនៅពេលចូលលើកក្រោយ (This staff member will use this password to log in next time)</p>
        </div>

        <x-slot:footer>
            <button type="button" onclick="ResetPasswordModal.toggle(false)"
                class="px-5 py-2.5 text-sm font-medium text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-white/5 rounded-xl transition-all">
                បោះបង់ (Cancel)
            </button>
            <button type="submit" form="resetPasswordForm" id="resetPasswordSubmitBtn"
                class="px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 active:scale-95 rounded-xl transition-all">
                កំណត់ពាក្យសម្ងាត់ (Reset Password)
            </button>
        </x-slot:footer>
    </x-ui.modal>

@endsection

@push('scripts')
    @vite(['resources/js/role/index.js'])
@endpush
