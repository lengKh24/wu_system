/**
 * Bulk Import / Export for the Student list — same dropzone + results-table
 * pattern as the retake exam module's import (resources/js/retake-exam/
 * batches.js's openImportModal/submitImportForm/renderImportResults).
 */
import { getById, baseUri } from '../app';
import { Toast } from './core.js';

const EXPORT_URL = baseUri('students-export');
const IMPORT_URL = baseUri('students-import');

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function buildImportDom() {
    return {
        exportBtn: getById('studentExportBtn'),
        importBtn: getById('studentImportBtn'),
        searchInput: getById('studentSearchInput'),

        importModal: getById('studentImportModal'),
        importForm: getById('studentImportForm'),
        importDropzone: getById('studentImportDropzone'),
        importFileInput: getById('studentImportFile'),
        importFileName: getById('studentImportFileName'),
        importClearBtn: getById('studentImportClearBtn'),
        importSubmitBtn: getById('studentImportSubmitBtn'),
        importSpinner: getById('studentImportSpinner'),
        importSubmitLabel: getById('studentImportSubmitLabel'),

        resultsModal: getById('studentImportResultsModal'),
        resultsBody: getById('studentImportResultsBody'),
    };
}

function openModal(modalEl) {
    if (!modalEl) return;
    const card = modalEl.querySelector(':scope > div');
    modalEl.classList.remove('invisible', 'opacity-0');
    modalEl.classList.add('flex');
    requestAnimationFrame(() => {
        card?.classList.remove('scale-90', 'opacity-0');
        card?.classList.add('scale-100', 'opacity-100');
    });
}

function closeModal(modalEl) {
    if (!modalEl) return;
    const card = modalEl.querySelector(':scope > div');
    modalEl.classList.add('opacity-0');
    card?.classList.remove('scale-100', 'opacity-100');
    card?.classList.add('scale-90', 'opacity-0');

    setTimeout(() => {
        modalEl.classList.add('invisible');
        modalEl.classList.remove('flex');
    }, 300);
}

function setImportFile(dom, file) {
    if (!file) return;

    const transfer = new DataTransfer();
    transfer.items.add(file);
    if (dom.importFileInput) dom.importFileInput.files = transfer.files;

    if (dom.importFileName) dom.importFileName.textContent = file.name;
    dom.importClearBtn?.classList.remove('hidden');
}

function setImportSubmitting(dom, isSubmitting) {
    if (dom.importSubmitBtn) dom.importSubmitBtn.disabled = isSubmitting;
    dom.importSpinner?.classList.toggle('hidden', !isSubmitting);
    if (dom.importSubmitLabel) {
        dom.importSubmitLabel.textContent = isSubmitting
            ? 'កំពុងនាំចូល... (Importing...)'
            : 'នាំចូល (Import)';
    }
}

function clearImportFile(dom) {
    if (dom.importFileInput) dom.importFileInput.value = '';
    if (dom.importFileName) dom.importFileName.textContent = '';
    dom.importClearBtn?.classList.add('hidden');
}

function importIssueTable(rows) {
    if (!Array.isArray(rows) || rows.length === 0) return '';

    return `
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold rounded-full border bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200/70 dark:border-rose-500/20">${rows.length}</span>
                <h4 class="text-sm font-bold text-neutral-800 dark:text-neutral-100">មិនបានបង្កើត (Skipped rows)</h4>
            </div>
            <div class="overflow-x-auto border border-neutral-200 dark:border-white/10 rounded-xl">
                <table class="w-full text-xs text-left">
                    <thead class="bg-neutral-50 dark:bg-white/5 text-neutral-500 dark:text-neutral-400 uppercase">
                        <tr><th class="px-3 py-2 font-bold">Row</th><th class="px-3 py-2 font-bold">Code</th><th class="px-3 py-2 font-bold">Reason</th></tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-white/5">
                        ${rows.map((r) => `<tr>
                            <td class="px-3 py-2 text-neutral-700 dark:text-neutral-300">${r.row}</td>
                            <td class="px-3 py-2 text-neutral-700 dark:text-neutral-300">${escapeHtml(r.code || '—')}</td>
                            <td class="px-3 py-2 text-neutral-700 dark:text-neutral-300">${escapeHtml(r.reason)}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>`;
}

function renderImportResults(dom, report) {
    if (!dom.resultsBody) return;

    const summary = `
        <div class="flex items-center gap-3 px-4 py-3.5 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200/70 dark:border-emerald-500/20 rounded-xl">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <span class="text-sm font-bold text-emerald-800 dark:text-emerald-300">${report.created_count ?? 0} student(s) created</span>
        </div>`;

    dom.resultsBody.innerHTML = summary + importIssueTable(report.skipped);
    openModal(dom.resultsModal);
}

export function initStudentImportExport(ApiService, reloadList) {
    const dom = buildImportDom();

    window.StudentImportModal = { toggle: (open) => (open ? openModal(dom.importModal) : closeModal(dom.importModal)) };
    window.StudentImportResultsModal = { toggle: (open) => (open ? openModal(dom.resultsModal) : closeModal(dom.resultsModal)) };

    dom.exportBtn?.addEventListener('click', () => {
        const params = new URLSearchParams();
        if (dom.searchInput?.value) params.set('search', dom.searchInput.value);
        window.open(`${EXPORT_URL}?${params.toString()}`, '_blank');
    });

    dom.importBtn?.addEventListener('click', () => {
        dom.importForm?.reset();
        clearImportFile(dom);
        window.StudentImportModal.toggle(true);
    });

    dom.importDropzone?.addEventListener('click', () => dom.importFileInput?.click());
    dom.importDropzone?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            dom.importFileInput?.click();
        }
    });
    dom.importFileInput?.addEventListener('change', () => {
        const file = dom.importFileInput.files?.[0];
        if (file) setImportFile(dom, file);
    });
    dom.importClearBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        clearImportFile(dom);
    });

    ['dragover', 'dragleave', 'drop'].forEach((eventName) => {
        dom.importDropzone?.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    dom.importDropzone?.addEventListener('dragover', () => {
        dom.importDropzone.classList.add('border-indigo-400', 'dark:border-indigo-500/50');
    });
    dom.importDropzone?.addEventListener('dragleave', () => {
        dom.importDropzone.classList.remove('border-indigo-400', 'dark:border-indigo-500/50');
    });
    dom.importDropzone?.addEventListener('drop', (e) => {
        dom.importDropzone.classList.remove('border-indigo-400', 'dark:border-indigo-500/50');
        const file = e.dataTransfer?.files?.[0];
        if (file) setImportFile(dom, file);
    });

    dom.importForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Guards against double-submit from a spam click — the request can
        // take a while for a large spreadsheet, and the button is otherwise
        // free to be clicked again while the first import is still in flight.
        if (dom.importSubmitBtn?.disabled) return;

        const file = dom.importFileInput?.files?.[0];
        if (!file) {
            Toast.fire({ icon: 'warning', title: 'សូមជ្រើសរើសឯកសារ (Please choose a file)' });
            return;
        }

        const body = new FormData();
        body.append('file', file);

        setImportSubmitting(dom, true);
        try {
            const { error, data } = await ApiService.request(IMPORT_URL, { method: 'POST', body });

            if (error) {
                const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                Toast.fire({ icon: 'error', title: firstError || data?.message || 'ការនាំចូលបរាជ័យ (Import failed)' });
                return;
            }

            window.StudentImportModal.toggle(false);
            clearImportFile(dom);

            const report = data?.data?.report ?? {};
            Toast.fire({ icon: 'success', title: `នាំចូលជោគជ័យ! (${report.created_count ?? 0} row(s) created)` });
            renderImportResults(dom, report);
            reloadList();
        } finally {
            setImportSubmitting(dom, false);
        }
    });
}
