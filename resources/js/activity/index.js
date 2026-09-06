/**
 * Activity Log — read-only feed of who created/updated/deleted what,
 * across every module. Follows the same IIFE pattern as the other
 * simple modules.
 */
(() => {
    'use strict';

    const CONFIG = {
        LOG_API: '/api/v1/activity-log',
        MODULES_API: '/api/v1/activity-log/modules',
        USERS_API: '/api/v1/activity-log/users',
    };

    const EVENT_STYLE = {
        created: { label: 'បង្កើត (Created)', bg: 'bg-emerald-50 dark:bg-emerald-500/10', text: 'text-emerald-700 dark:text-emerald-400', dot: 'bg-emerald-500' },
        updated: { label: 'កែប្រែ (Updated)', bg: 'bg-amber-50 dark:bg-amber-500/10', text: 'text-amber-700 dark:text-amber-400', dot: 'bg-amber-500' },
        deleted: { label: 'លុប (Deleted)', bg: 'bg-rose-50 dark:bg-rose-500/10', text: 'text-rose-700 dark:text-rose-400', dot: 'bg-rose-500' },
    };

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

    const DOM = {
        list: document.getElementById('activity-list'),
        pagination: document.getElementById('activity-pagination'),
        loader: document.getElementById('loading-overlay'),
        searchInput: document.getElementById('activitySearchInput'),
        moduleFilter: document.getElementById('moduleFilter'),
        userFilter: document.getElementById('userFilter'),
        eventFilter: document.getElementById('eventFilter'),
    };

    const state = { page: 1, debounceTimer: null };

    async function request(url) {
        DOM.loader?.classList.remove('hidden');
        DOM.loader?.classList.add('flex');
        try {
            const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const data = await response.json().catch(() => null);
            return { error: !response.ok, data };
        } catch (err) {
            console.error('[Activity Log API Error]', err);
            return { error: true, data: null };
        } finally {
            DOM.loader?.classList.add('hidden');
            DOM.loader?.classList.remove('flex');
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function formatValue(v) {
        if (v === null || v === undefined) return '<span class="italic text-neutral-400">empty</span>';
        if (typeof v === 'boolean') return v ? 'true' : 'false';
        return escapeHtml(String(v));
    }

    function changesHtml(changes) {
        const fields = Object.keys(changes || {});
        if (fields.length === 0) return '';

        return `
            <div class="mt-2 pt-2 border-t border-neutral-100 dark:border-white/5 space-y-1">
                ${fields.map((field) => `
                    <div class="flex items-center gap-2 text-xs">
                        <span class="font-mono font-semibold text-neutral-500 dark:text-neutral-400 shrink-0">${escapeHtml(field)}:</span>
                        <span class="text-rose-500 dark:text-rose-400 line-through truncate">${formatValue(changes[field].old)}</span>
                        <svg class="w-3 h-3 text-neutral-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        <span class="text-emerald-600 dark:text-emerald-400 font-medium truncate">${formatValue(changes[field].new)}</span>
                    </div>`).join('')}
            </div>`;
    }

    function renderList(activities) {
        if (!DOM.list) return;

        if (activities.length === 0) {
            DOM.list.innerHTML = '<p class="text-sm text-neutral-400 text-center py-12">មិនមានកំណត់ត្រា (No activity found)</p>';
            return;
        }

        DOM.list.innerHTML = activities.map((a) => {
            const style = EVENT_STYLE[a.event] ?? EVENT_STYLE.updated;
            const causerName = a.causer?.name ?? 'System';
            const [avatarBg, avatarText] = avatarColor(causerName);
            const hasChanges = a.changes && Object.keys(a.changes).length > 0;

            return `
                <div class="p-4 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl">
                    <div class="flex items-start gap-3">
                        <span class="flex items-center justify-center w-9 h-9 rounded-full ${avatarBg} ${avatarText} font-bold text-xs shrink-0">
                            ${escapeHtml(initials(causerName))}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-neutral-900 dark:text-white text-sm">${escapeHtml(causerName)}</span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold rounded-full ${style.bg} ${style.text}">
                                    <span class="w-1.5 h-1.5 rounded-full ${style.dot}"></span>
                                    ${style.label}
                                </span>
                                ${a.module ? `<span class="px-2 py-0.5 text-[11px] font-semibold bg-neutral-100 dark:bg-white/5 text-neutral-600 dark:text-neutral-300 rounded-full capitalize">${escapeHtml(a.module.replace('-', ' '))}</span>` : ''}
                                ${a.subject_type ? `<span class="text-[11px] text-neutral-400 font-mono">${escapeHtml(a.subject_type)} #${escapeHtml(a.subject_id)}</span>` : ''}
                            </div>
                            <p class="text-xs text-neutral-400 mt-0.5">${escapeHtml(a.description)} &middot; ${escapeHtml(a.created_at)}</p>
                            ${hasChanges ? changesHtml(a.changes) : ''}
                        </div>
                    </div>
                </div>`;
        }).join('');
    }

    function renderPagination(meta) {
        if (!DOM.pagination || !meta) return;
        const { current_page, last_page, total } = meta;

        DOM.pagination.innerHTML = `
            <span class="text-xs text-neutral-400">សរុប ${total} កំណត់ត្រា (${total} total)</span>
            <div class="flex items-center gap-2">
                <button data-page="${current_page - 1}" ${current_page <= 1 ? 'disabled' : ''}
                    class="page-btn px-3 py-1.5 text-xs font-semibold bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-lg text-neutral-600 dark:text-neutral-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-neutral-50 dark:hover:bg-white/5">
                    មុន (Prev)
                </button>
                <span class="text-xs text-neutral-400">${current_page} / ${last_page}</span>
                <button data-page="${current_page + 1}" ${current_page >= last_page ? 'disabled' : ''}
                    class="page-btn px-3 py-1.5 text-xs font-semibold bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-lg text-neutral-600 dark:text-neutral-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-neutral-50 dark:hover:bg-white/5">
                    បន្ទាប់ (Next)
                </button>
            </div>`;

        DOM.pagination.querySelectorAll('.page-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                state.page = parseInt(btn.dataset.page, 10);
                loadActivities();
            });
        });
    }

    async function loadActivities() {
        const params = new URLSearchParams({ page: state.page, per_page: 20 });
        if (DOM.searchInput?.value.trim()) params.set('search', DOM.searchInput.value.trim());
        if (DOM.moduleFilter?.value) params.set('module', DOM.moduleFilter.value);
        if (DOM.userFilter?.value) params.set('causer_id', DOM.userFilter.value);
        if (DOM.eventFilter?.value) params.set('event', DOM.eventFilter.value);

        const { error, data } = await request(`${CONFIG.LOG_API}?${params.toString()}`);
        if (error) return;

        renderList(data?.data ?? []);
        renderPagination(data?.meta);
    }

    async function loadFilters() {
        const [modulesRes, usersRes] = await Promise.all([
            request(CONFIG.MODULES_API),
            request(CONFIG.USERS_API),
        ]);

        if (!modulesRes.error && DOM.moduleFilter) {
            (modulesRes.data?.data ?? []).forEach((module) => {
                const opt = document.createElement('option');
                opt.value = module;
                opt.textContent = module;
                DOM.moduleFilter.appendChild(opt);
            });
        }

        if (!usersRes.error && DOM.userFilter) {
            (usersRes.data?.data ?? []).forEach((user) => {
                const opt = document.createElement('option');
                opt.value = user.id;
                opt.textContent = user.name;
                DOM.userFilter.appendChild(opt);
            });
        }
    }

    function initEvents() {
        DOM.searchInput?.addEventListener('input', () => {
            clearTimeout(state.debounceTimer);
            state.debounceTimer = setTimeout(() => { state.page = 1; loadActivities(); }, 300);
        });

        [DOM.moduleFilter, DOM.userFilter, DOM.eventFilter].forEach((el) => {
            el?.addEventListener('change', () => { state.page = 1; loadActivities(); });
        });
    }

    document.addEventListener('DOMContentLoaded', async () => {
        initEvents();
        await loadFilters();
        await loadActivities();
    });
})();
