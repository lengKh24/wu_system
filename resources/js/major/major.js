/**
 * major Management System Module
 * Encapsulated to prevent global scope pollution, using event delegation and decoupled UI templates.
 */
(() => {
    'use strict';

    // 1. CONFIGURATION & CONSTANTS
    const CONFIG = {
        API_BASE: '/api/v1/majors',
        API_FACULTIES: '/api/v1/faculties',
        DEBOUNCE_DELAY: 300,
        LOCALE: 'en-GB'
    };

    // GLOBAL APP STATE CONTAINER
    const STATE = {
        faculties: [], // Holds local cached reference copy for cross-referencing tables
        currentPage: 1
    };

    // Pagination rendering (mirrors resources/js/student/student-pagination.js's
    // handling of Laravel's standard paginated-resource `meta` shape).
    function renderPagination(meta) {
        const el = document.getElementById('pagination-container');
        if (!el) return;

        if (!meta || !meta.total) {
            el.innerHTML = '';
            return;
        }

        const { current_page: current, last_page: last, total, from, to } = meta;

        el.innerHTML = `
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    កំពុងបង្ហាញ ${from ?? 0}–${to ?? 0} នៃ ${total} (Showing ${from ?? 0}-${to ?? 0} of ${total})
                </p>
                <div class="flex items-center gap-1.5">
                    <button type="button" data-page="${current - 1}" ${current <= 1 ? 'disabled' : ''}
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-neutral-200 dark:border-white/10 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-white/5 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        មុន (Prev)
                    </button>
                    <span class="px-2 text-xs font-medium text-neutral-500 dark:text-neutral-400">ទំព័រ ${current} / ${last}</span>
                    <button type="button" data-page="${current + 1}" ${current >= last ? 'disabled' : ''}
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-neutral-200 dark:border-white/10 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-white/5 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        បន្ទាប់ (Next)
                    </button>
                </div>
            </div>`;
    }

    function bindPagination(onPageChange) {
        document.getElementById('pagination-container')?.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-page]');
            if (!btn || btn.disabled) return;

            const page = Number(btn.dataset.page);
            if (page >= 1) onPageChange(page);
        });
    }

    // Dynamic Dropdown
    async function loadFacultiesDropdown() {
    if (!DOM.facultySelect) return;

    const { error, data } = await ApiService.request(CONFIG.API_FACULTIES);
    if (error) {
        console.error("Failed to load faculties for dropdown lookup.");
        return;
    }

    // Extract records based on pagination structure or raw array outputs
    const faculties = data && Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);

    // Save locally to state memory cache
    STATE.faculties = faculties;

    const initialOption = `<option value="" disabled selected hidden>-- ជ្រើសរើសមហាវិទ្យាល័យ --</option>`;
    DOM.facultySelect.innerHTML = initialOption + faculties.map(faculty => {
        return `<option value="${faculty.id}">${faculty.name_kh} (${faculty.name_en})</option>`;
    }).join('');
}

    // 2. CENTRALIZED DOM SELECTORS
    const DOM = {
        facultySelect: document.getElementById('facultySelect'),
        form: document.getElementById('addmajorForm'),
        tableBody: document.getElementById('major-table-body'),
        searchInput: document.getElementById('majorSearchInput'),
        loader: document.getElementById('loading-overlay'),
        modal: document.getElementById('majorModal'),
        modalCard: document.getElementById('modalCard'),
        modalTitle: document.getElementById('modalTitle'),
        submitBtn: document.getElementById('addmajorForm')?.querySelector('button[type="submit"]')
    };

    // Third-party instance verification
    const Toast = typeof Swal !== 'undefined' ? Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    }) : { fire: console.log };

    // 3. PRIVATE MODULE STATE
    const state = {
        isEditMode: false,
        editingmajorId: null,
        debounceTimer: null
    };

    // 4. CORE API SERVICES (Isolated HTTP layer)
    const ApiService = {
        async request(url, options = {}) {
            this.toggleLoader(true);
            try {
                const { headers, method = 'GET', body, ...restOptions } = options;
                const response = await fetch(url, {
                    method: method,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        ...headers
                    },
                    body: body,
                    ...restOptions
                });

                const contentType = response.headers.get("content-type");
                const isJson = contentType && contentType.includes("application/json");
                const result = isJson ? await response.json() : null;

                if (!response.ok) {
                    return { error: true, status: response.status, data: result };
                }
                return { error: false, status: response.status, data: result };
            } catch (err) {
                console.error(`[API Error] Action failed on ${url}:`, err);
                return { error: true, status: 500, data: null };
            } finally {
                this.toggleLoader(false);
            }
        },

        toggleLoader(show) {
            if (DOM.loader) {
                DOM.loader.classList.toggle('hidden', !show);
            }
        }
    };

    // 5. CORE WORKFLOW CONTROLLERS
    async function loadmajors(searchQuery = '', page = 1) {
        STATE.currentPage = page;
        const url = `${CONFIG.API_BASE}?search=${encodeURIComponent(searchQuery)}&page=${page}`;
        const { error, data } = await ApiService.request(url);

        if (error) {
            Toast.fire({ icon: 'error', title: 'Failed to load majors' });
            return;
        }

        // Handle both object-paginated dynamic data lists or straight array responses safely
        const records = data && Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
        renderTable(records);
        renderPagination(data?.meta);
    }

    //Edit
    async function handleEditAction(id) {
    const { error, data } = await ApiService.request(`${CONFIG.API_BASE}/${id}`);
    if (error) {
        Toast.fire({ icon: 'error', title: 'មិនអាចទាញយកទិន្នន័យបានទេ' });
        return;
    }

    const payload = data.data || data;
    state.isEditMode = true;
    state.editingmajorId = id;

    if (DOM.modalTitle) DOM.modalTitle.textContent = 'កែប្រែព័ត៌មានវេន';
    if (DOM.submitBtn) DOM.submitBtn.textContent = 'ធ្វើបច្ចុប្បន្នភាព';

    if (DOM.form) {
        // Include 'faculty_id' into the value assignments loop mapping cleanly
        ['faculty_id', 'name_kh', 'name_en', 'shortcut', 'remark'].forEach(field => {
            const element = DOM.form.querySelector(`[name="${field}"]`);
            if (element) element.value = payload[field] ?? '';
        });
    }
    toggleModal(true);
}

    // Delete
    async function handleDeleteAction(id) {
        const confirmation = await Swal.fire({
            title: 'តើអ្នកប្រាកដជាចង់លុបមែនទេ?',
            text: "ទិន្នន័យនេះមិនអាចយកមកវិញបានឡើយ!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'បាទ/ចាស លុបវា!',
            cancelButtonText: 'បោះបង់'
        });

        if (!confirmation.isConfirmed) return;

        const { error } = await ApiService.request(`${CONFIG.API_BASE}/${id}`, { method: 'DELETE' });
        if (!error) {
            Toast.fire({ icon: 'success', title: 'លុបទិន្នន័យបានជោគជ័យ!' });
            loadmajors(DOM.searchInput?.value || '', STATE.currentPage);
        } else {
            Toast.fire({ icon: 'error', title: 'មានបញ្ហាមិនអាចលុបទិន្នន័យនេះបាន' });
        }
    }

    //Submit
    async function handleFormSubmit(e) {
        e.preventDefault();
        if (!DOM.form || !DOM.submitBtn) return;

        DOM.submitBtn.disabled = true;
        const formData = new FormData(DOM.form);
        const payload = {};

        for (const [key, value] of formData.entries()) {
            if (key === 'search') continue;
            const cleanVal = value.toString().trim();
            payload[key] = cleanVal === '' ? null : cleanVal;
        }

        const url = state.isEditMode ? `${CONFIG.API_BASE}/${state.editingmajorId}` : CONFIG.API_BASE;
        const method = state.isEditMode ? 'PUT' : 'POST';

        const { error, status, data } = await ApiService.request(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!error) {
            Toast.fire({
                icon: 'success',
                title: state.isEditMode ? 'ធ្វើបច្ចុប្បន្នភាពជោគជ័យ!' : 'បង្កើតវេនជោគជ័យ!',
                text: state.isEditMode ? 'ព័ត៌មានត្រូវបានកែប្រែរួចរាល់។' : `${payload.name_kh} has been added.`
            });
            resetFormState();
            toggleModal(false);
            loadmajors(DOM.searchInput?.value || '', STATE.currentPage);
        } else if (status === 422 && data) {
            const errorMessages = data.errors ? Object.values(data.errors).flat() : ['Validation failed'];
            Toast.fire({
                icon: 'warning',
                title: 'ពិនិត្យទិន្នន័យឡើងវិញ',
                html: `<div class="text-left text-xs text-rose-500 mt-1 list-disc pl-4">${errorMessages.map(msg => `<li>${msg}</li>`).join('')}</div>`
            });
        } else {
            Toast.fire({
                icon: 'error',
                title: 'មានបញ្ហាភ្ជាប់ទៅកាន់ប្រព័ន្ធ',
                text: data?.message || 'Internal Server Error (500).'
            });
        }
        DOM.submitBtn.disabled = false;
    }

    // 6. UI RENDERING & COMPONENT TEMPLATES
    function renderTable(majors) {
    if (!DOM.tableBody) return;

    if (!majors || majors.length === 0) {
        DOM.tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-neutral-500">No records found.</td></tr>';
        return;
    }

    DOM.tableBody.innerHTML = majors.map((major, index) => {
        const formattedDate = new Date(major.created_at).toLocaleDateString(CONFIG.LOCALE, {
            day: '2-digit', month: 'short', year: 'numeric'
        });

        // 1. First, check if relationship object was eagerly loaded by backend
        let matchedFaculty = major.faculty;

        // 2. If not loaded, cross-reference using our global STATE cache map
        if (!matchedFaculty && major.faculty_id && STATE.faculties.length > 0) {
            matchedFaculty = STATE.faculties.find(f => String(f.id) === String(major.faculty_id));
        }

        // 3. Render matching strings or fallbacks cleanly
        const facultyName = matchedFaculty
            ? `${matchedFaculty.name_kh} <span class="text-xs text-neutral-400 font-mono block">${matchedFaculty.name_en ?? ''}</span>`
            : '<span class="text-neutral-400 italic text-xs">No Faculty Linked</span>';

        return `
            <tr class="group hover:bg-indigo-50/50 dark:hover:bg-indigo-500/5 transition-all duration-200 border-b border-neutral-100 dark:border-white/5">
                <td class="px-6 py-4 text-neutral-500 font-mono text-sm">${index + 1}</td>

                <!-- Faculty Name Column Display -->
                <td class="px-6 py-4 font-bold text-indigo-600 dark:text-indigo-400 text-sm">
                    ${facultyName}
                </td>

                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mr-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <div>
                            <div class="font-semibold text-neutral-900 dark:text-white">${major.name_kh}</div>
                            <div class="text-xs text-neutral-400 font-mono">${major.name_en}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm text-neutral-600 dark:text-neutral-400 max-w-[250px] truncate" title="${major.shortcut ?? ''}">
                        ${major.shortcut ?? '<span class="italic opacity-40 text-xs">No Shortcut</span>'}
                    </p>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm text-neutral-600 dark:text-neutral-400 max-w-[250px] truncate" title="${major.remark ?? ''}">
                        ${major.remark ?? '<span class="italic opacity-40 text-xs">No remarks</span>'}
                    </p>
                </td>
                <td class="px-6 py-4 text-xs text-neutral-500 font-mono">${formattedDate}</td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2">
                        <button data-action="edit" data-id="${major.id}" class="p-2 text-amber-600 hover:bg-indigo-100 dark:hover:bg-indigo-500/20 rounded-lg transition-colors" title="Edit major">
                            <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button data-action="delete" data-id="${major.id}" class="p-2 text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-500/20 rounded-lg transition-colors" title="Delete major">
                            <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            </tr>`;
    }).join('');
}

    // 7. INTERACTIVE & MODAL TRANSLATION ENGINE
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

            setTimeout(() => {
                DOM.form?.querySelector('[name="name_kh"]')?.focus();
            }, 250);
        } else {
            DOM.modal.classList.add('opacity-0');
            DOM.modalCard.classList.remove('scale-100', 'opacity-100');
            DOM.modalCard.classList.add('scale-90', 'opacity-0');

            setTimeout(() => {
                DOM.modal.classList.add('invisible');
                DOM.modal.classList.remove('flex');
                resetFormState();
            }, 300);
        }
    }

    function resetFormState() {
        if (DOM.form) DOM.form.reset();
        state.isEditMode = false;
        state.editingmajorId = null;

        if (DOM.modalTitle) DOM.modalTitle.textContent = 'បន្ថែមជំនាញថ្មី';
        if (DOM.submitBtn) DOM.submitBtn.textContent = 'រក្សាទុក';

        document.querySelectorAll('.smart-hint').forEach(hint => {
            hint.classList.add('opacity-0', 'scale-95', 'translate-y-1');
        });
    }

    // 8. EVENT ATTACHMENTS PIPELINE
    function initEvents() {
        // Expose toggleModal securely only for explicit HTML elements like header close/open triggers
        window.AppModal = { toggle: (open) => toggleModal(open) };

        // Search Input Engine with Clean Debouncing
        DOM.searchInput?.addEventListener('input', (e) => {
            clearTimeout(state.debounceTimer);
            state.debounceTimer = setTimeout(() => {
                loadmajors(e.target.value, 1);
            }, CONFIG.DEBOUNCE_DELAY);
        });

        bindPagination((page) => loadmajors(DOM.searchInput?.value || '', page));

        // Form Submit
        DOM.form?.addEventListener('submit', handleFormSubmit);

        // Modern Event Delegation: Intercept Action Buttons without explicit tag onClick attributes
        DOM.tableBody?.addEventListener('click', (e) => {
            const targetBtn = e.target.closest('button[data-action]');
            if (!targetBtn) return;

            const action = targetBtn.getAttribute('data-action');
            const targetId = targetBtn.getAttribute('data-id');

            if (action === 'edit') handleEditAction(targetId);
            if (action === 'delete') handleDeleteAction(targetId);
        });

        // Interactive Tooltips Hint Dynamic Engine
        const inputsWithHints = document.querySelectorAll('#addmajorForm input, #addmajorForm textarea');
        inputsWithHints.forEach(input => {
            const hintBox = input.parentElement.querySelector('.smart-hint');
            const textMessage = input.getAttribute('data-hint');
            if (!hintBox || !textMessage) return;

            hintBox.textContent = textMessage;

            input.addEventListener('input', function() {
                const hasValue = this.value.trim().length > 0;
                hintBox.classList.toggle('opacity-0', !hasValue);
                hintBox.classList.toggle('scale-95', !hasValue);
                hintBox.classList.toggle('translate-y-1', !hasValue);
                hintBox.classList.toggle('opacity-100', hasValue);
                hintBox.classList.toggle('scale-100', hasValue);
                hintBox.classList.toggle('-translate-y-2', hasValue);
            });

            input.addEventListener('blur', () => {
                hintBox.classList.remove('opacity-100', 'scale-100', '-translate-y-2');
                hintBox.classList.add('opacity-0', 'scale-95', 'translate-y-1');
            });
        });
    }

    // 9. INITIALIZE ENGINE RUNNING
    document.addEventListener('DOMContentLoaded', async () => {
        initEvents();
        await loadFacultiesDropdown();
        loadmajors();
    });
})();
