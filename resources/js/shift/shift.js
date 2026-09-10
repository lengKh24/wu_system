/**
 * shift Management System Module
 * Encapsulated to prevent global scope pollution, using event delegation and decoupled UI templates.
 */
(() => {
    'use strict';

    // 1. CONFIGURATION & CONSTANTS
    const CONFIG = {
        API_BASE: '/api/v1/shifts',
        EXPORT_URL: '/api/v1/shifts-export',
        IMPORT_URL: '/api/v1/shifts-import',
        BULK_DESTROY_URL: '/api/v1/shifts-bulk-destroy',
        DEBOUNCE_DELAY: 300,
        LOCALE: 'en-GB'
    };

    // 2. CENTRALIZED DOM SELECTORS
    const DOM = {
        form: document.getElementById('addshiftForm'),
        tableBody: document.getElementById('shift-table-body'),
        searchInput: document.getElementById('shiftSearchInput'),
        loader: document.getElementById('loading-overlay'),
        modal: document.getElementById('shiftModal'),
        modalCard: document.getElementById('modalCard'),
        modalTitle: document.getElementById('modalTitle'),
        submitBtn: document.getElementById('addshiftForm')?.querySelector('button[type="submit"]'),

        exportBtn: document.getElementById('shiftExportBtn'),
        destroyAllBtn: document.getElementById('shiftDestroyAllBtn'),

        importBtn: document.getElementById('shiftImportBtn'),
        importModal: document.getElementById('shiftImportModal'),
        importModalCard: document.getElementById('shiftImportModalCard'),
        importForm: document.getElementById('shiftImportForm'),
        importDropzone: document.getElementById('shiftImportDropzone'),
        importFileInput: document.getElementById('shiftImportFile'),
        importFileName: document.getElementById('shiftImportFileName'),
        importClearBtn: document.getElementById('shiftImportClearBtn'),
        importSubmitBtn: document.getElementById('shiftImportSubmitBtn'),
        importSpinner: document.getElementById('shiftImportSpinner'),
        importSubmitLabel: document.getElementById('shiftImportSubmitLabel'),

        importResultsModal: document.getElementById('shiftImportResultsModal'),
        importResultsModalCard: document.getElementById('shiftImportResultsModalCard'),
        importResultsBody: document.getElementById('shiftImportResultsBody'),
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
        editingshiftId: null,
        debounceTimer: null
    };

    // 4. CORE API SERVICES (Isolated HTTP layer)
const ApiService = {
    async request(url, options = {}) {
        this.toggleLoader(true);
        try {
            // Destructure headers to merge them safely, default to POST/GET dynamically
            const { headers, method = 'GET', body, ...restOptions } = options;

            const response = await fetch(url, {
                method: method, // Explicitly enforce the target HTTP verb
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    ...headers
                },
                body: body, // Explicitly attach data body payload
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
    async function loadshifts(searchQuery = '') {
        const url = `${CONFIG.API_BASE}?search=${encodeURIComponent(searchQuery)}`;
        const { error, data } = await ApiService.request(url);

        if (error) {
            Toast.fire({ icon: 'error', title: 'Failed to load shifts' });
            return;
        }

        // Handle both object-paginated dynamic data lists or straight array responses safely
        const records = data && Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
        renderTable(records);
    }

    async function handleEditAction(id) {
        const { error, data } = await ApiService.request(`${CONFIG.API_BASE}/${id}`);
        if (error) {
            Toast.fire({ icon: 'error', title: 'មិនអាចទាញយកទិន្នន័យបានទេ' });
            return;
        }

        const payload = data.data || data;
        state.isEditMode = true;
        state.editingshiftId = id;

        if (DOM.modalTitle) DOM.modalTitle.textContent = 'កែប្រែព័ត៌មានវេន';
        if (DOM.submitBtn) DOM.submitBtn.textContent = 'ធ្វើបច្ចុប្បន្នភាព';

        if (DOM.form) {
            ['name_kh', 'name_en', 'shortcut', 'remark'].forEach(field => {
                const element = DOM.form.querySelector(`[name="${field}"]`);
                if (element) element.value = payload[field] ?? '';
            });
        }
        toggleModal(true);
    }

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
            loadshifts(DOM.searchInput?.value || '');
        } else {
            Toast.fire({ icon: 'error', title: 'មានបញ្ហាមិនអាចលុបទិន្នន័យនេះបាន' });
        }
    }

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

        const url = state.isEditMode ? `${CONFIG.API_BASE}/${state.editingshiftId}` : CONFIG.API_BASE;
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
            loadshifts();
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
    function renderTable(shifts) {
        if (!DOM.tableBody) return;

        if (!shifts || shifts.length === 0) {
            DOM.tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-neutral-500">No records found.</td></tr>';
            return;
        }

        DOM.tableBody.innerHTML = shifts.map((shift, index) => {
            const formattedDate = new Date(shift.created_at).toLocaleDateString(CONFIG.LOCALE, {
                day: '2-digit', month: 'short', year: 'numeric'
            });

            return `
                <tr class="group hover:bg-indigo-50/50 dark:hover:bg-indigo-500/5 transition-all duration-200 border-b border-neutral-100 dark:border-white/5">
                    <td class="px-6 py-4 text-neutral-500 font-mono text-sm">${index + 1}</td>
                    <td class="px-6 py-4 font-bold text-indigo-600 dark:text-indigo-400">${shift.name_kh ?? '<span class="text-neutral-400 italic">N/A</span>'}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mr-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div>
                                <div class="font-semibold text-neutral-900 dark:text-white">${shift.name_kh}</div>
                                <div class="text-xs text-neutral-400 font-mono">${shift.name_en}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 max-w-[250px] truncate" title="${shift.shortcut ?? ''}">
                            ${shift.shortcut?? '<span class="italic opacity-40 text-xs">No Shortcut</span>'}
                        </p>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 max-w-[250px] truncate" title="${shift.remark ?? ''}">
                            ${shift.remark ?? '<span class="italic opacity-40 text-xs">No remarks</span>'}
                        </p>
                    </td>
                    <td class="px-6 py-4 text-xs text-neutral-500 font-mono">${formattedDate}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <button data-action="edit" data-id="${shift.id}" class="p-2 text-amber-600 hover:bg-indigo-100 dark:hover:bg-indigo-500/20 rounded-lg transition-colors" title="Edit shift">
                                <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button data-action="delete" data-id="${shift.id}" class="p-2 text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-500/20 rounded-lg transition-colors" title="Delete shift">
                                <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
        }).join('');
    }

    // 7. INTERACTIVE & MODAL TRANSLATION ENGINE
    // Generic open/close animation, shared by the create/edit modal and the
    // two import-related modals added alongside it (same markup shape:
    // invisible/opacity-0 backdrop + scale-90 card).
    function toggleModalEl(modalEl, cardEl, forceOpen = null, onOpen = null, onClose = null) {
        if (!modalEl || !cardEl) return;

        const isOpen = modalEl.classList.contains('flex');
        const makeOpen = forceOpen !== null ? forceOpen : !isOpen;

        if (makeOpen) {
            modalEl.classList.remove('invisible');
            modalEl.classList.add('flex');
            requestAnimationFrame(() => {
                modalEl.classList.remove('opacity-0');
                cardEl.classList.remove('scale-90', 'opacity-0');
                cardEl.classList.add('scale-100', 'opacity-100');
            });
            onOpen?.();
        } else {
            modalEl.classList.add('opacity-0');
            cardEl.classList.remove('scale-100', 'opacity-100');
            cardEl.classList.add('scale-90', 'opacity-0');
            setTimeout(() => {
                modalEl.classList.add('invisible');
                modalEl.classList.remove('flex');
                onClose?.();
            }, 300);
        }
    }

    function toggleModal(forceOpen = null) {
        toggleModalEl(DOM.modal, DOM.modalCard, forceOpen, () => {
            setTimeout(() => DOM.form?.querySelector('[name="name_kh"]')?.focus(), 250);
        }, resetFormState);
    }

    function resetFormState() {
        if (DOM.form) DOM.form.reset();
        state.isEditMode = false;
        state.editingshiftId = null;

        if (DOM.modalTitle) DOM.modalTitle.textContent = 'បន្ថែមសាស្ត្រាចារ្យថ្មី';
        if (DOM.submitBtn) DOM.submitBtn.textContent = 'រក្សាទុក';

        document.querySelectorAll('.smart-hint').forEach(hint => {
            hint.classList.add('opacity-0', 'scale-95', 'translate-y-1');
        });
    }

    // --- Import / Export (mirrors resources/js/subject/subject.js) --------

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function setImportFile(file) {
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        if (DOM.importFileInput) DOM.importFileInput.files = transfer.files;
        if (DOM.importFileName) DOM.importFileName.textContent = file.name;
        DOM.importClearBtn?.classList.remove('hidden');
    }

    function clearImportFile() {
        if (DOM.importFileInput) DOM.importFileInput.value = '';
        if (DOM.importFileName) DOM.importFileName.textContent = '';
        DOM.importClearBtn?.classList.add('hidden');
    }

    function setImportSubmitting(isSubmitting) {
        if (DOM.importSubmitBtn) DOM.importSubmitBtn.disabled = isSubmitting;
        DOM.importSpinner?.classList.toggle('hidden', !isSubmitting);
        if (DOM.importSubmitLabel) {
            DOM.importSubmitLabel.textContent = isSubmitting ? 'កំពុងនាំចូល... (Importing...)' : 'នាំចូល (Import)';
        }
    }

    function importIssueTable(title, rows, headers, mapRow) {
        if (!Array.isArray(rows) || rows.length === 0) return '';

        return `
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wide text-rose-600 dark:text-rose-400 mb-2">${title} (${rows.length})</h4>
                <div class="overflow-x-auto border border-rose-200/70 dark:border-rose-500/20 rounded-xl">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400">
                            <tr>${headers.map((h) => `<th class="px-3 py-2 font-bold">${h}</th>`).join('')}</tr>
                        </thead>
                        <tbody class="divide-y divide-rose-100 dark:divide-rose-500/10">
                            ${rows.map((r) => `<tr>${mapRow(r).map((v) => `<td class="px-3 py-2 text-neutral-600 dark:text-neutral-300">${escapeHtml(v)}</td>`).join('')}</tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;
    }

    function renderImportResults(report) {
        if (!DOM.importResultsBody) return;

        const summary = `
            <div class="flex items-center gap-3 px-4 py-3.5 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200/70 dark:border-emerald-500/20 rounded-xl">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <span class="text-sm font-bold text-emerald-800 dark:text-emerald-300">${report.created_count ?? 0} row(s) created/updated</span>
            </div>`;

        const skippedTable = importIssueTable(
            'របាំងឆ្លងកាត់ (Skipped rows)',
            report.skipped,
            ['Row', 'Code', 'Reason'],
            (r) => [r.row, r.code, r.reason]
        );

        DOM.importResultsBody.innerHTML = summary + skippedTable;
        toggleModalEl(DOM.importResultsModal, DOM.importResultsModalCard, true);
    }

    async function submitImportForm(e) {
        e.preventDefault();
        if (DOM.importSubmitBtn?.disabled) return;

        const file = DOM.importFileInput?.files[0];
        if (!file) {
            Toast.fire({ icon: 'warning', title: 'សូមជ្រើសរើសឯកសារ (Please choose a file)' });
            return;
        }

        const body = new FormData();
        body.append('file', file);

        setImportSubmitting(true);
        try {
            const { error, data } = await ApiService.request(CONFIG.IMPORT_URL, { method: 'POST', body });

            if (error) {
                Toast.fire({ icon: 'error', title: data?.message || 'ការនាំចូលបរាជ័យ (Import failed)' });
                return;
            }

            toggleModalEl(DOM.importModal, DOM.importModalCard, false);
            clearImportFile();

            const report = data?.data?.report ?? {};
            Toast.fire({ icon: 'success', title: `នាំចូលជោគជ័យ! (${report.created_count ?? 0} row(s))` });

            renderImportResults(report);
            loadshifts(DOM.searchInput?.value || '');
        } finally {
            setImportSubmitting(false);
        }
    }

    function exportCurrentFilters() {
        const params = new URLSearchParams();
        if (DOM.searchInput?.value) params.set('search', DOM.searchInput.value);
        window.open(`${CONFIG.EXPORT_URL}?${params.toString()}`, '_blank');
    }

    // Permanent hard-delete of every shift — bypasses soft-delete entirely,
    // so this asks for a typed confirmation rather than a plain OK/Cancel
    // dialog.
    async function handleDestroyAll() {
        const confirmation = await Swal.fire({
            title: 'លុបវេនទាំងអស់ជាអចិន្ត្រៃយ៍?',
            html: 'សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ។ វាយ <b>DELETE</b> ដើម្បីបញ្ជាក់។<br><span class="text-xs text-neutral-400">This permanently deletes ALL shifts and cannot be undone. Type DELETE to confirm.</span>',
            icon: 'warning',
            input: 'text',
            inputPlaceholder: 'DELETE',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'លុបទាំងអស់ (Destroy All)',
            cancelButtonText: 'បោះបង់',
            preConfirm: (value) => {
                if (value !== 'DELETE') {
                    Swal.showValidationMessage('Type DELETE exactly to confirm.');
                    return false;
                }
                return true;
            },
        });

        if (!confirmation.isConfirmed) return;

        const { error, data } = await ApiService.request(CONFIG.BULK_DESTROY_URL, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ all: true }),
        });

        if (!error) {
            Toast.fire({ icon: 'success', title: data?.message || 'លុបទិន្នន័យទាំងអស់បានជោគជ័យ!' });
            loadshifts('');
        } else {
            Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចលុបទិន្នន័យទាំងអស់បានទេ' });
        }
    }

    function initImportDropzone() {
        DOM.importDropzone?.addEventListener('click', () => DOM.importFileInput?.click());
        DOM.importDropzone?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                DOM.importFileInput?.click();
            }
        });

        DOM.importFileInput?.addEventListener('change', () => {
            const file = DOM.importFileInput.files?.[0];
            if (file) setImportFile(file);
        });

        DOM.importClearBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            clearImportFile();
        });

        ['dragover', 'dragleave', 'drop'].forEach((eventName) => {
            DOM.importDropzone?.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });
        DOM.importDropzone?.addEventListener('dragover', () => {
            DOM.importDropzone.classList.add('border-indigo-400', 'dark:border-indigo-500/50');
        });
        DOM.importDropzone?.addEventListener('dragleave', () => {
            DOM.importDropzone.classList.remove('border-indigo-400', 'dark:border-indigo-500/50');
        });
        DOM.importDropzone?.addEventListener('drop', (e) => {
            DOM.importDropzone.classList.remove('border-indigo-400', 'dark:border-indigo-500/50');
            const file = e.dataTransfer?.files?.[0];
            if (file) setImportFile(file);
        });
    }

    // 8. EVENT ATTACHMENTS PIPELINE
    function initEvents() {
        // Expose toggleModal securely only for explicit HTML elements like header close/open triggers
        window.AppModal = { toggle: (open) => toggleModal(open) };
        window.ShiftImportModal = {
            toggle: (open) => toggleModalEl(DOM.importModal, DOM.importModalCard, open, null, clearImportFile),
        };
        window.ShiftImportResultsModal = {
            toggle: (open) => toggleModalEl(DOM.importResultsModal, DOM.importResultsModalCard, open),
        };

        DOM.importBtn?.addEventListener('click', () => window.ShiftImportModal.toggle(true));
        DOM.exportBtn?.addEventListener('click', exportCurrentFilters);
        DOM.destroyAllBtn?.addEventListener('click', handleDestroyAll);
        DOM.importForm?.addEventListener('submit', submitImportForm);
        initImportDropzone();

        // Search Input Engine with Clean Debouncing
        DOM.searchInput?.addEventListener('input', (e) => {
            clearTimeout(state.debounceTimer);
            state.debounceTimer = setTimeout(() => {
                loadshifts(e.target.value);
            }, CONFIG.DEBOUNCE_DELAY);
        });

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
        const inputsWithHints = document.querySelectorAll('#addshiftForm input, #addshiftForm textarea');
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
    document.addEventListener('DOMContentLoaded', () => {
        initEvents();
        loadshifts();
    });
})();
