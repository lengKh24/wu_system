/**
 * Subject Management System Module — base CRUD follows the same pattern as
 * resources/js/major/major.js (single encapsulated IIFE, manual modal
 * toggle animation), extended with an import/export flow that mirrors
 * resources/js/student/import-export.js (dropzone, spam-click guard on
 * submit, categorized results modal).
 */
(() => {
    'use strict';

    const CONFIG = {
        API_BASE: '/api/v1/subjects',
        API_FACULTIES: '/api/v1/faculties',
        EXPORT_URL: '/api/v1/subjects-export',
        IMPORT_URL: '/api/v1/subjects-import',
        BULK_DESTROY_URL: '/api/v1/subjects-bulk-destroy',
        DEBOUNCE_DELAY: 300,
        LOCALE: 'en-GB',
    };

    const LEVEL_LABEL = {
        associate: 'បរិញ្ញាបត្ររង (Associate)',
        bachelor: 'បរិញ្ញាបត្រ (Bachelor)',
        master: 'បរិញ្ញាបត្រជាន់ខ្ពស់ (Master)',
        phd: 'បណ្ឌិត (PhD)',
    };

    const STATE = {
        faculties: [],
        currentPage: 1,
    };

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

    async function loadFacultiesDropdown() {
        if (!DOM.facultySelect) return;

        const { error, data } = await ApiService.request(`${CONFIG.API_FACULTIES}?per_page=200`);
        if (error) {
            console.error('Failed to load faculties for dropdown lookup.');
            return;
        }

        const faculties = data && Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
        STATE.faculties = faculties;

        const initialOption = `<option value="" disabled selected hidden>-- ជ្រើសរើសមហាវិទ្យាល័យ --</option>`;
        DOM.facultySelect.innerHTML = initialOption + faculties.map((faculty) =>
            `<option value="${faculty.id}">${faculty.name_kh} (${faculty.name_en})</option>`
        ).join('');
    }

    const DOM = {
        facultySelect: document.getElementById('facultySelect'),
        form: document.getElementById('subjectForm'),
        tableBody: document.getElementById('subject-table-body'),
        searchInput: document.getElementById('subjectSearchInput'),
        loader: document.getElementById('loading-overlay'),
        modal: document.getElementById('subjectModal'),
        modalCard: document.getElementById('subjectModalCard'),
        modalTitle: document.getElementById('subjectModalTitle'),
        submitBtn: document.getElementById('subjectForm')?.querySelector('button[type="submit"]'),

        exportBtn: document.getElementById('subjectExportBtn'),
        destroyAllBtn: document.getElementById('subjectDestroyAllBtn'),

        importBtn: document.getElementById('subjectImportBtn'),
        importModal: document.getElementById('subjectImportModal'),
        importModalCard: document.getElementById('subjectImportModalCard'),
        importForm: document.getElementById('subjectImportForm'),
        importDropzone: document.getElementById('subjectImportDropzone'),
        importFileInput: document.getElementById('subjectImportFile'),
        importFileName: document.getElementById('subjectImportFileName'),
        importClearBtn: document.getElementById('subjectImportClearBtn'),
        importSubmitBtn: document.getElementById('subjectImportSubmitBtn'),
        importSpinner: document.getElementById('subjectImportSpinner'),
        importSubmitLabel: document.getElementById('subjectImportSubmitLabel'),

        importResultsModal: document.getElementById('subjectImportResultsModal'),
        importResultsModalCard: document.getElementById('subjectImportResultsModalCard'),
        importResultsBody: document.getElementById('subjectImportResultsBody'),
    };

    const Toast = typeof Swal !== 'undefined' ? Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    }) : { fire: console.log };

    const state = {
        isEditMode: false,
        editingSubjectId: null,
        debounceTimer: null,
    };

    const ApiService = {
        async request(url, options = {}) {
            this.toggleLoader(true);
            try {
                const { headers, method = 'GET', body, ...restOptions } = options;
                const response = await fetch(url, {
                    method,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', ...headers },
                    body,
                    ...restOptions,
                });

                const contentType = response.headers.get('content-type');
                const isJson = contentType && contentType.includes('application/json');
                const result = isJson ? await response.json() : null;

                return { error: !response.ok, status: response.status, data: result };
            } catch (err) {
                console.error(`[API Error] Action failed on ${url}:`, err);
                return { error: true, status: 500, data: null };
            } finally {
                this.toggleLoader(false);
            }
        },

        toggleLoader(show) {
            DOM.loader?.classList.toggle('hidden', !show);
        },
    };

    async function loadSubjects(searchQuery = '', page = 1) {
        STATE.currentPage = page;
        const url = `${CONFIG.API_BASE}?search=${encodeURIComponent(searchQuery)}&page=${page}`;
        const { error, data } = await ApiService.request(url);

        if (error) {
            Toast.fire({ icon: 'error', title: 'Failed to load subjects' });
            return;
        }

        const records = data && Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
        renderTable(records);
        renderPagination(data?.meta);
    }

    async function handleEditAction(id) {
        const { error, data } = await ApiService.request(`${CONFIG.API_BASE}/${id}`);
        if (error) {
            Toast.fire({ icon: 'error', title: 'មិនអាចទាញយកទិន្នន័យបានទេ' });
            return;
        }

        const payload = data.data || data;
        state.isEditMode = true;
        state.editingSubjectId = id;

        if (DOM.modalTitle) DOM.modalTitle.textContent = 'កែប្រែមុខវិជ្ជា';
        if (DOM.submitBtn) DOM.submitBtn.textContent = 'ធ្វើបច្ចុប្បន្នភាព';

        if (DOM.form) {
            const facultyId = payload.faculty?.id ?? payload.faculty_id ?? '';
            const values = { ...payload, faculty_id: facultyId };
            ['faculty_id', 'code', 'name_kh', 'name_en', 'level', 'lecturer_hour', 'credit', 'remark'].forEach((field) => {
                const element = DOM.form.querySelector(`[name="${field}"]`);
                if (element) element.value = values[field] ?? '';
            });
        }
        toggleModal(true);
    }

    async function handleDeleteAction(id) {
        const confirmation = await Swal.fire({
            title: 'តើអ្នកប្រាកដជាចង់លុបមែនទេ?',
            text: 'ទិន្នន័យនេះមិនអាចយកមកវិញបានឡើយ!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'បាទ/ចាស លុបវា!',
            cancelButtonText: 'បោះបង់',
        });

        if (!confirmation.isConfirmed) return;

        const { error } = await ApiService.request(`${CONFIG.API_BASE}/${id}`, { method: 'DELETE' });
        if (!error) {
            Toast.fire({ icon: 'success', title: 'លុបទិន្នន័យបានជោគជ័យ!' });
            loadSubjects(DOM.searchInput?.value || '', STATE.currentPage);
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
            const cleanVal = value.toString().trim();
            payload[key] = cleanVal === '' ? null : cleanVal;
        }

        const url = state.isEditMode ? `${CONFIG.API_BASE}/${state.editingSubjectId}` : CONFIG.API_BASE;
        const method = state.isEditMode ? 'PUT' : 'POST';

        const { error, status, data } = await ApiService.request(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        if (!error) {
            Toast.fire({
                icon: 'success',
                title: state.isEditMode ? 'ធ្វើបច្ចុប្បន្នភាពជោគជ័យ!' : 'បង្កើតមុខវិជ្ជាជោគជ័យ!',
            });
            resetFormState();
            toggleModal(false);
            loadSubjects(DOM.searchInput?.value || '', STATE.currentPage);
        } else if (status === 422 && data) {
            const errorMessages = data.errors ? Object.values(data.errors).flat() : ['Validation failed'];
            Toast.fire({
                icon: 'warning',
                title: 'ពិនិត្យទិន្នន័យឡើងវិញ',
                html: `<div class="text-left text-xs text-rose-500 mt-1 list-disc pl-4">${errorMessages.map((msg) => `<li>${msg}</li>`).join('')}</div>`,
            });
        } else {
            Toast.fire({ icon: 'error', title: 'មានបញ្ហាភ្ជាប់ទៅកាន់ប្រព័ន្ធ', text: data?.message || 'Internal Server Error (500).' });
        }
        DOM.submitBtn.disabled = false;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function renderTable(subjects) {
        if (!DOM.tableBody) return;

        if (!subjects || subjects.length === 0) {
            DOM.tableBody.innerHTML = '<tr><td colspan="9" class="text-center py-10 text-neutral-500">No records found.</td></tr>';
            return;
        }

        DOM.tableBody.innerHTML = subjects.map((subject, index) => {
            const faculty = subject.faculty;
            const facultyName = faculty
                ? `${escapeHtml(faculty.name_kh)} <span class="text-xs text-neutral-400 font-mono block">${escapeHtml(faculty.name_en ?? '')}</span>`
                : '<span class="text-neutral-400 italic text-xs">No Faculty Linked</span>';

            const levelLabel = LEVEL_LABEL[subject.level] ?? subject.level ?? '<span class="italic opacity-40 text-xs">—</span>';
            const lecturerHour = subject.lecturer_hour != null
                ? `${subject.lecturer_hour}h`
                : '<span class="italic opacity-40 text-xs">—</span>';

            return `
                <tr class="group hover:bg-indigo-50/50 dark:hover:bg-indigo-500/5 transition-all duration-200 border-b border-neutral-100 dark:border-white/5">
                    <td class="px-6 py-4 text-neutral-500 font-mono text-sm">${(STATE.currentPage - 1) * (subjects.length) + index + 1}</td>
                    <td class="px-6 py-4 font-mono text-sm font-bold text-neutral-700 dark:text-neutral-300">${escapeHtml(subject.code)}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mr-3 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </div>
                            <div>
                                <div class="font-semibold text-neutral-900 dark:text-white">${escapeHtml(subject.name_kh)}</div>
                                <div class="text-xs text-neutral-400 font-mono">${escapeHtml(subject.name_en)}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">${facultyName}</td>
                    <td class="px-6 py-4 text-sm">${levelLabel}</td>
                    <td class="px-6 py-4 text-sm font-mono">${lecturerHour}</td>
                    <td class="px-6 py-4 text-sm font-mono">${subject.credit ?? '<span class="italic opacity-40 text-xs">—</span>'}</td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 max-w-[200px] truncate" title="${escapeHtml(subject.remark ?? '')}">
                            ${subject.remark ? escapeHtml(subject.remark) : '<span class="italic opacity-40 text-xs">No remarks</span>'}
                        </p>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <button data-action="edit" data-id="${subject.id}" class="p-2 text-amber-600 hover:bg-indigo-100 dark:hover:bg-indigo-500/20 rounded-lg transition-colors" title="Edit subject">
                                <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button data-action="delete" data-id="${subject.id}" class="p-2 text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-500/20 rounded-lg transition-colors" title="Delete subject">
                                <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
        }).join('');
    }

    // Generic modal open/close animation — shared by the create/edit modal
    // and the two import-related modals, all built from the same
    // <x-ui.modal> markup shape (invisible/opacity-0 backdrop + scale-90
    // card), same animation major.js uses for its own hand-rolled modal.
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
            setTimeout(() => DOM.form?.querySelector('[name="faculty_id"]')?.focus(), 250);
        }, resetFormState);
    }

    function resetFormState() {
        if (DOM.form) DOM.form.reset();
        state.isEditMode = false;
        state.editingSubjectId = null;
        if (DOM.modalTitle) DOM.modalTitle.textContent = 'បន្ថែមមុខវិជ្ជាថ្មី';
        if (DOM.submitBtn) DOM.submitBtn.textContent = 'រក្សាទុក';
    }

    // --- Import / Export --------------------------------------------------

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
            loadSubjects(DOM.searchInput?.value || '', STATE.currentPage);
        } finally {
            setImportSubmitting(false);
        }
    }

    // Permanent hard-delete of every subject — bypasses soft-delete entirely
    // (see SubjectController::bulkDestroy), so this asks for a typed
    // confirmation rather than a plain OK/Cancel dialog.
    async function handleDestroyAll() {
        const confirmation = await Swal.fire({
            title: 'លុបមុខវិជ្ជាទាំងអស់ជាអចិន្ត្រៃយ៍?',
            html: 'សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ។ វាយ <b>DELETE</b> ដើម្បីបញ្ជាក់។<br><span class="text-xs text-neutral-400">This permanently deletes ALL subjects and cannot be undone. Type DELETE to confirm.</span>',
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
            loadSubjects('', 1);
        } else {
            Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចលុបទិន្នន័យទាំងអស់បានទេ' });
        }
    }

    function exportCurrentFilters() {
        const params = new URLSearchParams();
        if (DOM.searchInput?.value) params.set('search', DOM.searchInput.value);
        window.open(`${CONFIG.EXPORT_URL}?${params.toString()}`, '_blank');
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

    function initEvents() {
        window.AppModal = { toggle: (open) => toggleModal(open) };
        window.SubjectImportModal = {
            toggle: (open) => toggleModalEl(DOM.importModal, DOM.importModalCard, open, null, clearImportFile),
        };
        window.SubjectImportResultsModal = {
            toggle: (open) => toggleModalEl(DOM.importResultsModal, DOM.importResultsModalCard, open),
        };

        DOM.searchInput?.addEventListener('input', (e) => {
            clearTimeout(state.debounceTimer);
            state.debounceTimer = setTimeout(() => loadSubjects(e.target.value, 1), CONFIG.DEBOUNCE_DELAY);
        });

        bindPagination((page) => loadSubjects(DOM.searchInput?.value || '', page));

        DOM.form?.addEventListener('submit', handleFormSubmit);

        DOM.tableBody?.addEventListener('click', (e) => {
            const targetBtn = e.target.closest('button[data-action]');
            if (!targetBtn) return;
            const action = targetBtn.getAttribute('data-action');
            const targetId = targetBtn.getAttribute('data-id');
            if (action === 'edit') handleEditAction(targetId);
            if (action === 'delete') handleDeleteAction(targetId);
        });

        DOM.importBtn?.addEventListener('click', () => window.SubjectImportModal.toggle(true));
        DOM.exportBtn?.addEventListener('click', exportCurrentFilters);
        DOM.destroyAllBtn?.addEventListener('click', handleDestroyAll);
        DOM.importForm?.addEventListener('submit', submitImportForm);
        initImportDropzone();
    }

    document.addEventListener('DOMContentLoaded', async () => {
        initEvents();
        await loadFacultiesDropdown();
        loadSubjects();
    });
})();
