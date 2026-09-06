/**
 * Roles & Permissions module — self-contained, follows the same IIFE
 * pattern as the other simple modules (faculty, shift, etc.).
 */
(() => {
    'use strict';

    const CONFIG = {
        ROLES_API: '/api/v1/roles',
        USERS_API: '/api/v1/users',
        CATALOG_API: '/api/v1/roles/permission-catalog',
    };

    const ACTIONS = ['view', 'create', 'edit', 'delete'];

    // Icon per module — reused from the sidebar for visual consistency.
    // Falls back to a generic folder icon for any module not listed here.
    const MODULE_ICONS = {
        student: '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        batch: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>',
        major: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
        subject: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
        faculty: '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        campus: '<path stroke-linecap="round" stroke-linejoin="round" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        shift: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        group: '<path stroke-linecap="round" stroke-width="2" d="M4.5 17H4a1 1 0 0 1-1-1 3 3 0 0 1 3-3h1m0-3.05A2.5 2.5 0 1 1 9 5.5M19.5 17h.5a1 1 0 0 0 1-1 3 3 0 0 0-3-3h-1m0-3.05a2.5 2.5 0 1 0-2-4.45m.5 13.5h-7a1 1 0 0 1-1-1 3 3 0 0 1 3-3h3a3 3 0 0 1 3 3 1 1 0 0 1-1 1Zm-1-9.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"/>',
        'app-status': '<path stroke-width="2" d="M11.083 5.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.556 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.03 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 20.8c-.764.446-1.688-.248-1.474-1.106l1.03-4.119A1 1 0 0 0 6.8 14.56l-3.33-2.723c-.698-.571-.342-1.702.557-1.771l4.462-.342a1 1 0 0 0 .84-.597l1.753-4.022Z"/>',
        lecturer: '<circle cx="12" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 21v-1a7 7 0 0 1 14 0v1"/>',
        'state-exam': '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9-5 9 5-9 5-9-5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 10v5c0 1.5 3.1 3 7 3s7-1.5 7-3v-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 10v6"/>',
        certificate: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        role: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12a3 3 0 100-6 3 3 0 000 6zM17.804 21c.512-.75.79-1.638.79-2.556C18.594 15.36 15.964 13 12.75 13H12a4.5 4.5 0 00-4.5 4.5c0 .918.278 1.806.79 2.556M15 6a3 3 0 11-6 0 3 3 0 016 0z"/>',
    };
    const DEFAULT_ICON = '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>';

    const AVATAR_PALETTE = [
        ['bg-indigo-600/10', 'text-indigo-600 dark:text-indigo-400'],
        ['bg-emerald-600/10', 'text-emerald-600 dark:text-emerald-400'],
        ['bg-amber-600/10', 'text-amber-600 dark:text-amber-400'],
        ['bg-rose-600/10', 'text-rose-600 dark:text-rose-400'],
        ['bg-sky-600/10', 'text-sky-600 dark:text-sky-400'],
        ['bg-violet-600/10', 'text-violet-600 dark:text-violet-400'],
    ];

    function avatarColor(seed) {
        let hash = 0;
        for (let i = 0; i < seed.length; i++) hash = seed.charCodeAt(i) + ((hash << 5) - hash);
        return AVATAR_PALETTE[Math.abs(hash) % AVATAR_PALETTE.length];
    }

    function initials(name) {
        return (name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]?.toUpperCase() ?? '').join('');
    }

    function moduleIcon(module, extraClass = 'w-4 h-4') {
        return `<svg class="${extraClass}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">${MODULE_ICONS[module] ?? DEFAULT_ICON}</svg>`;
    }

    const DOM = {
        rolesGrid: document.getElementById('roles-grid'),
        usersGrid: document.getElementById('users-grid'),
        staffCountLabel: document.getElementById('staff-count-label'),
        staffSearchInput: document.getElementById('staffSearchInput'),
        loader: document.getElementById('loading-overlay'),
        modal: document.getElementById('roleModal'),
        modalCard: document.getElementById('modalCard'),
        modalTitle: document.getElementById('modalTitle'),
        form: document.getElementById('roleForm'),
        permissionGrid: document.getElementById('permission-grid'),
        submitBtn: document.getElementById('roleSubmitBtn'),
        toggleAllBtn: document.getElementById('toggleAllPermissions'),

        pwModal: document.getElementById('resetPasswordModal'),
        pwModalCard: document.getElementById('resetPasswordModalCard'),
        pwForm: document.getElementById('resetPasswordForm'),
        pwUserLabel: document.getElementById('resetPasswordUserLabel'),
        pwInput: document.getElementById('resetPasswordInput'),
        pwSubmitBtn: document.getElementById('resetPasswordSubmitBtn'),
        pwToggleVisibility: document.getElementById('toggleResetPasswordVisibility'),
    };

    const Toast = typeof Swal !== 'undefined' ? Swal.mixin({
        toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true,
    }) : { fire: console.log };

    const state = {
        catalog: {},
        roles: [],
        users: [],
        editingRoleId: null,
    };

    async function request(url, options = {}) {
        DOM.loader?.classList.remove('hidden');
        DOM.loader?.classList.add('flex');
        try {
            const { headers, method = 'GET', body, ...rest } = options;
            const response = await fetch(url, {
                method,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', ...headers },
                body,
                ...rest,
            });
            const contentType = response.headers.get('content-type');
            const isJson = contentType && contentType.includes('application/json');
            const data = isJson ? await response.json() : null;
            return { error: !response.ok, status: response.status, data };
        } catch (err) {
            console.error('[Roles API Error]', err);
            return { error: true, status: 500, data: null };
        } finally {
            DOM.loader?.classList.add('hidden');
            DOM.loader?.classList.remove('flex');
        }
    }

    // ---- Data loading ----

    async function loadCatalog() {
        const { error, data } = await request(CONFIG.CATALOG_API);
        if (!error) state.catalog = data?.data ?? {};
    }

    async function loadRoles() {
        const { error, data } = await request(CONFIG.ROLES_API);
        if (error) {
            Toast.fire({ icon: 'error', title: 'Failed to load roles' });
            return;
        }
        state.roles = data?.data ?? [];
        renderRoles();
    }

    async function loadUsers() {
        const { error, data } = await request(`${CONFIG.USERS_API}?per_page=100`);
        if (error) {
            Toast.fire({ icon: 'error', title: 'Failed to load users' });
            return;
        }
        state.users = Array.isArray(data?.data) ? data.data : [];
        renderUsers();
    }

    // ---- Rendering: Roles ----

    function renderRoles() {
        if (!DOM.rolesGrid) return;
        if (state.roles.length === 0) {
            DOM.rolesGrid.innerHTML = '<p class="text-sm text-neutral-400 col-span-full">No roles yet.</p>';
            return;
        }

        DOM.rolesGrid.innerHTML = state.roles.map((role) => {
            const modules = [...new Set(role.permissions.map((p) => p.split('.')[0]))].sort();
            const isAdmin = role.name === 'Admin';

            return `
                <div class="group relative p-5 bg-white dark:bg-neutral-900 border rounded-2xl shadow-sm transition-all hover:shadow-md ${isAdmin ? 'border-indigo-200 dark:border-indigo-500/30 ring-1 ring-indigo-500/10' : 'border-neutral-200 dark:border-white/10'}">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center justify-center w-10 h-10 rounded-xl shrink-0 ${isAdmin ? 'bg-gradient-to-tr from-indigo-600 to-violet-500 text-white shadow-md shadow-indigo-500/30' : 'bg-neutral-100 dark:bg-white/5 text-neutral-500 dark:text-neutral-400'}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12a3 3 0 100-6 3 3 0 000 6zM17.804 21c.512-.75.79-1.638.79-2.556C18.594 15.36 15.964 13 12.75 13H12a4.5 4.5 0 00-4.5 4.5c0 .918.278 1.806.79 2.556M15 6a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </span>
                            <div>
                                <h4 class="font-bold text-neutral-900 dark:text-white leading-tight">${escapeHtml(role.name)}</h4>
                                <p class="text-xs text-neutral-400 mt-0.5">${role.users_count} ${role.users_count === 1 ? 'staff member' : 'staff members'}</p>
                            </div>
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
                            <button data-action="edit-role" data-id="${role.id}" class="p-1.5 text-neutral-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-lg transition-colors" title="Edit">
                                <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            ${isAdmin ? '' : `
                            <button data-action="delete-role" data-id="${role.id}" class="p-1.5 text-neutral-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-colors" title="Delete">
                                <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>`}
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5 pt-3 border-t border-neutral-100 dark:border-white/5">
                        ${isAdmin
                            ? '<span class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold bg-indigo-600/10 text-indigo-600 dark:text-indigo-400 rounded-lg"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>All modules</span>'
                            : modules.length === 0
                                ? '<span class="text-[11px] text-neutral-400 italic">No modules assigned</span>'
                                : modules.map((m) => `
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold bg-neutral-100 dark:bg-white/5 text-neutral-600 dark:text-neutral-300 rounded-lg">
                                        ${moduleIcon(m, 'w-3 h-3')}${escapeHtml(m.replace('-', ' '))}
                                    </span>`).join('')
                        }
                    </div>
                </div>`;
        }).join('');
    }

    // ---- Rendering: Staff cards ----

    function renderUsers() {
        if (!DOM.usersGrid) return;

        const query = (DOM.staffSearchInput?.value || '').trim().toLowerCase();
        const filtered = query
            ? state.users.filter((u) => u.name.toLowerCase().includes(query) || u.email.toLowerCase().includes(query))
            : state.users;

        if (DOM.staffCountLabel) {
            DOM.staffCountLabel.textContent = `${state.users.length} ${state.users.length === 1 ? 'account' : 'accounts'} total`;
        }

        if (filtered.length === 0) {
            DOM.usersGrid.innerHTML = '<p class="text-sm text-neutral-400 col-span-full py-6 text-center">No staff accounts found.</p>';
            return;
        }

        DOM.usersGrid.innerHTML = filtered.map((user) => {
            const [bg, text] = avatarColor(user.name || user.email);
            const currentRole = (user.roles ?? [])[0] ?? '';
            const isSelf = String(user.id) === String(window.CURRENT_USER_ID);

            return `
                <div class="p-5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start justify-between gap-2 mb-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="flex items-center justify-center w-11 h-11 rounded-full ${bg} ${text} font-bold text-sm shrink-0">
                                ${escapeHtml(initials(user.name))}
                            </span>
                            <div class="min-w-0">
                                <p class="font-bold text-neutral-900 dark:text-white truncate">${escapeHtml(user.name)}</p>
                                <p class="text-xs text-neutral-400 truncate flex items-center gap-1">
                                    ${escapeHtml(user.email)}
                                    ${user.verified ? '<svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20" title="Verified"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>' : ''}
                                </p>
                            </div>
                        </div>
                        ${!isSelf && (window.CAN_RESET_PASSWORD || window.CAN_DELETE_USER) ? `
                        <div class="flex gap-1 shrink-0">
                            ${window.CAN_RESET_PASSWORD ? `
                            <button data-action="reset-password" data-id="${user.id}" data-name="${escapeHtml(user.name)}" class="p-1.5 text-neutral-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 rounded-lg transition-colors" title="Reset Password">
                                <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            </button>` : ''}
                            ${window.CAN_DELETE_USER ? `
                            <button data-action="delete-user" data-id="${user.id}" data-name="${escapeHtml(user.name)}" class="p-1.5 text-neutral-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-colors" title="Delete Account">
                                <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>` : ''}
                        </div>` : ''}
                    </div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-neutral-400 mb-1.5">Role</label>
                    <div class="relative">
                        <select data-user-id="${user.id}"
                            class="user-role-select w-full appearance-none px-3.5 py-2.5 pr-9 text-sm font-semibold bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 cursor-pointer transition-all">
                            <option value="">— No role —</option>
                            ${state.roles.map((r) => `<option value="${escapeHtml(r.name)}" ${currentRole === r.name ? 'selected' : ''}>${escapeHtml(r.name)}</option>`).join('')}
                        </select>
                        <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>`;
        }).join('');
    }

    // ---- Rendering: permission grid (toggle switches) ----

    function toggleSwitchHtml(permName, checked) {
        return `
            <label class="inline-flex items-center justify-center cursor-pointer">
                <input type="checkbox" value="${permName}" ${checked ? 'checked' : ''} class="permission-checkbox sr-only peer">
                <div class="relative w-9 h-5 bg-neutral-300 dark:bg-white/10 rounded-full peer peer-checked:bg-indigo-600 transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:shadow after:transition-all peer-checked:after:translate-x-4"></div>
            </label>`;
    }

    function renderPermissionGrid(checkedPermissions = []) {
        if (!DOM.permissionGrid) return;
        const modules = Object.keys(state.catalog).sort();

        DOM.permissionGrid.innerHTML = modules.map((module, i) => {
            const perms = state.catalog[module];
            return `
                <div class="grid grid-cols-4 items-center gap-2 px-4 py-3 ${i % 2 === 0 ? 'bg-neutral-50/50 dark:bg-white/[0.015]' : ''}">
                    <div class="col-span-1 flex items-center gap-2 min-w-0">
                        <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-neutral-100 dark:bg-white/5 text-neutral-500 dark:text-neutral-400 shrink-0">
                            ${moduleIcon(module, 'w-3.5 h-3.5')}
                        </span>
                        <span class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 capitalize truncate">${escapeHtml(module.replace('-', ' '))}</span>
                    </div>
                    <div class="col-span-3 grid grid-cols-4">
                        ${ACTIONS.map((action) => {
                            const permName = `${module}.${action}`;
                            if (!perms.includes(permName)) return '<div></div>';
                            return toggleSwitchHtml(permName, checkedPermissions.includes(permName));
                        }).join('')}
                    </div>
                </div>`;
        }).join('');
    }

    // ---- Modal ----

    function toggleModal(forceOpen = null) {
        if (!DOM.modal || !DOM.modalCard) return;
        const isOpen = DOM.modal.classList.contains('flex');
        const makeOpen = forceOpen !== null ? forceOpen : !isOpen;

        if (makeOpen) {
            DOM.modal.classList.remove('invisible');
            DOM.modal.classList.add('flex');
            requestAnimationFrame(() => {
                DOM.modal.classList.remove('opacity-0');
                DOM.modalCard.classList.remove('scale-90', 'opacity-0');
                DOM.modalCard.classList.add('scale-100', 'opacity-100');
            });
        } else {
            DOM.modal.classList.add('opacity-0');
            DOM.modalCard.classList.remove('scale-100', 'opacity-100');
            DOM.modalCard.classList.add('scale-90', 'opacity-0');
            setTimeout(() => {
                DOM.modal.classList.add('invisible');
                DOM.modal.classList.remove('flex');
                DOM.form?.reset();
                state.editingRoleId = null;
            }, 300);
        }
    }

    function openCreate() {
        state.editingRoleId = null;
        if (DOM.modalTitle) DOM.modalTitle.textContent = 'បង្កើតតួនាទីថ្មី (Create Role)';
        if (DOM.submitBtn) DOM.submitBtn.textContent = 'រក្សាទុក (Save Role)';
        DOM.form?.reset();
        renderPermissionGrid([]);
        toggleModal(true);
    }

    function openEdit(roleId) {
        const role = state.roles.find((r) => String(r.id) === String(roleId));
        if (!role) return;

        state.editingRoleId = role.id;
        if (DOM.modalTitle) DOM.modalTitle.textContent = 'កែប្រែតួនាទី (Edit Role)';
        if (DOM.submitBtn) DOM.submitBtn.textContent = 'ធ្វើបច្ចុប្បន្នភាព (Update)';
        DOM.form?.reset();
        const nameInput = DOM.form?.querySelector('[name="name"]');
        if (nameInput) nameInput.value = role.name;
        renderPermissionGrid(role.permissions);
        toggleModal(true);
    }

    async function handleDeleteRole(roleId) {
        const confirmation = await Swal.fire({
            title: 'តើអ្នកប្រាកដជាចង់លុបមែនទេ?',
            text: 'Users assigned to this role must be reassigned first.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'បាទ/ចាស លុបវា!',
            cancelButtonText: 'បោះបង់',
        });
        if (!confirmation.isConfirmed) return;

        const { error, data } = await request(`${CONFIG.ROLES_API}/${roleId}`, { method: 'DELETE' });
        if (!error) {
            Toast.fire({ icon: 'success', title: 'Role deleted' });
            await loadRoles();
        } else {
            Toast.fire({ icon: 'error', title: data?.message || 'Failed to delete role' });
        }
    }

    async function handleFormSubmit(e) {
        e.preventDefault();
        if (!DOM.form || !DOM.submitBtn) return;

        DOM.submitBtn.disabled = true;

        const name = DOM.form.querySelector('[name="name"]')?.value.trim();
        const permissions = [...document.querySelectorAll('.permission-checkbox:checked')].map((el) => el.value);

        const url = state.editingRoleId ? `${CONFIG.ROLES_API}/${state.editingRoleId}` : CONFIG.ROLES_API;
        const method = state.editingRoleId ? 'PUT' : 'POST';

        const { error, status, data } = await request(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, permissions }),
        });

        if (!error) {
            Toast.fire({ icon: 'success', title: state.editingRoleId ? 'Role updated' : 'Role created' });
            toggleModal(false);
            await loadRoles();
            renderUsers();
        } else if (status === 422 && data?.errors) {
            const messages = Object.values(data.errors).flat();
            Toast.fire({ icon: 'warning', title: messages[0] || 'Validation failed' });
        } else {
            Toast.fire({ icon: 'error', title: data?.message || 'Something went wrong' });
        }

        DOM.submitBtn.disabled = false;
    }

    async function handleRoleAssignment(userId, roleName) {
        const { error, data } = await request(`${CONFIG.USERS_API}/${userId}/roles`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ roles: roleName ? [roleName] : [] }),
        });

        if (!error) {
            Toast.fire({ icon: 'success', title: 'Role updated' });
            const user = state.users.find((u) => String(u.id) === String(userId));
            if (user) user.roles = roleName ? [roleName] : [];
        } else {
            Toast.fire({ icon: 'error', title: data?.message || 'Failed to update role' });
        }
    }

    async function handleDeleteUser(userId, userName) {
        const confirmation = await Swal.fire({
            title: 'តើអ្នកប្រាកដជាចង់លុបគណនីនេះមែនទេ?',
            html: `Permanently delete <strong>${escapeHtml(userName)}</strong>'s account? This cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'បាទ/ចាស លុបវា!',
            cancelButtonText: 'បោះបង់',
        });
        if (!confirmation.isConfirmed) return;

        const { error, data } = await request(`${CONFIG.USERS_API}/${userId}`, { method: 'DELETE' });
        if (!error) {
            Toast.fire({ icon: 'success', title: 'Account deleted' });
            state.users = state.users.filter((u) => String(u.id) !== String(userId));
            renderUsers();
        } else {
            Toast.fire({ icon: 'error', title: data?.message || 'Failed to delete account' });
        }
    }

    // ---- Reset password modal ----

    function togglePasswordModal(forceOpen = null) {
        if (!DOM.pwModal || !DOM.pwModalCard) return;
        const isOpen = DOM.pwModal.classList.contains('flex');
        const makeOpen = forceOpen !== null ? forceOpen : !isOpen;

        if (makeOpen) {
            DOM.pwModal.classList.remove('invisible');
            DOM.pwModal.classList.add('flex');
            requestAnimationFrame(() => {
                DOM.pwModal.classList.remove('opacity-0');
                DOM.pwModalCard.classList.remove('scale-90', 'opacity-0');
                DOM.pwModalCard.classList.add('scale-100', 'opacity-100');
            });
        } else {
            DOM.pwModal.classList.add('opacity-0');
            DOM.pwModalCard.classList.remove('scale-100', 'opacity-100');
            DOM.pwModalCard.classList.add('scale-90', 'opacity-0');
            setTimeout(() => {
                DOM.pwModal.classList.add('invisible');
                DOM.pwModal.classList.remove('flex');
                DOM.pwForm?.reset();
            }, 300);
        }
    }

    function openResetPassword(userId, userName) {
        if (!DOM.pwForm) return;
        DOM.pwForm.reset();
        DOM.pwForm.querySelector('[name="user_id"]').value = userId;
        if (DOM.pwUserLabel) DOM.pwUserLabel.textContent = `សម្រាប់ (For): ${userName}`;
        togglePasswordModal(true);
    }

    async function handleResetPasswordSubmit(e) {
        e.preventDefault();
        if (!DOM.pwForm || !DOM.pwSubmitBtn) return;

        DOM.pwSubmitBtn.disabled = true;
        const userId = DOM.pwForm.querySelector('[name="user_id"]').value;
        const password = DOM.pwInput?.value ?? '';

        const { error, status, data } = await request(`${CONFIG.USERS_API}/${userId}/reset-password`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password }),
        });

        if (!error) {
            Toast.fire({ icon: 'success', title: 'Password reset' });
            togglePasswordModal(false);
        } else if (status === 422 && data?.errors) {
            const messages = Object.values(data.errors).flat();
            Toast.fire({ icon: 'warning', title: messages[0] || 'Validation failed' });
        } else {
            Toast.fire({ icon: 'error', title: data?.message || 'Failed to reset password' });
        }

        DOM.pwSubmitBtn.disabled = false;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    // ---- Events ----

    function initEvents() {
        window.AppModal = { toggle: (open) => toggleModal(open) };
        window.RolesModule = { openCreate };
        window.ResetPasswordModal = { toggle: (open) => togglePasswordModal(open) };

        DOM.form?.addEventListener('submit', handleFormSubmit);
        DOM.pwForm?.addEventListener('submit', handleResetPasswordSubmit);

        DOM.pwToggleVisibility?.addEventListener('click', () => {
            if (!DOM.pwInput) return;
            DOM.pwInput.type = DOM.pwInput.type === 'password' ? 'text' : 'password';
        });

        DOM.rolesGrid?.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const id = btn.getAttribute('data-id');
            if (btn.getAttribute('data-action') === 'edit-role') openEdit(id);
            if (btn.getAttribute('data-action') === 'delete-role') handleDeleteRole(id);
        });

        DOM.usersGrid?.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            if (btn.getAttribute('data-action') === 'delete-user') handleDeleteUser(id, name);
            if (btn.getAttribute('data-action') === 'reset-password') openResetPassword(id, name);
        });

        DOM.usersGrid?.addEventListener('change', (e) => {
            const select = e.target.closest('.user-role-select');
            if (!select) return;
            handleRoleAssignment(select.getAttribute('data-user-id'), select.value);
        });

        DOM.staffSearchInput?.addEventListener('input', () => renderUsers());

        DOM.toggleAllBtn?.addEventListener('click', () => {
            const boxes = document.querySelectorAll('.permission-checkbox');
            const allChecked = [...boxes].every((b) => b.checked);
            boxes.forEach((b) => { b.checked = !allChecked; });
        });
    }

    document.addEventListener('DOMContentLoaded', async () => {
        initEvents();
        await loadCatalog();
        await loadRoles();
        await loadUsers();
    });
})();
