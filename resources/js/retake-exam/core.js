/**
 * core.js — small, always-touched-together infrastructure pieces for the
 * retake exam Main List. No logic of its own; exists to be imported by
 * files that DO have logic (api-service, list, batches, actions, index).
 */

export const state = {
    debounceTimer: null,
    searchAbortController: null,
    search: '',
    filters: {
        batch_id: '',
        exam_type_id: '',
        retake_term_id: '',
        payment_status: '',
        outcome: '',
    },
    page: 1,
    // Cached lookups, populated once on load, reused by filters + batch chips.
    terms: [],
    examTypes: [],
    batches: [],
};

/** Builds a fresh DOM selector map. Call once, on DOMContentLoaded. */
export function buildDom() {
    return {
        tableBody: document.getElementById('retake-registrations-table-body'),
        searchInput: document.getElementById('retakeSearchInput'),
        loader: document.getElementById('loading-overlay'),
        batchStrip: document.getElementById('retake-batch-strip'),
        termFilter: document.getElementById('retakeTermFilter'),
        examTypeFilter: document.getElementById('retakeExamTypeFilter'),
        paymentFilter: document.getElementById('retakePaymentFilter'),
        outcomeFilter: document.getElementById('retakeOutcomeFilter'),
        importBtn: document.getElementById('retakeImportBtn'),
        newTermBtn: document.getElementById('retakeNewTermBtn'),
        exportBtn: document.getElementById('retakeExportBtn'),

        // Term modal
        termModal: document.getElementById('retakeTermModal'),
        termForm: document.getElementById('retakeTermForm'),
        termCampusSelect: document.getElementById('retakeTermCampus'),
        termCampusHint: document.getElementById('retakeTermCampusHint'),
        termTitleInput: document.getElementById('retakeTermTitle'),
        termStartInput: document.getElementById('retakeTermStart'),
        termEndInput: document.getElementById('retakeTermEnd'),

        // Import modal
        importModal: document.getElementById('retakeImportModal'),
        importForm: document.getElementById('retakeImportForm'),
        importTermSelect: document.getElementById('retakeImportTerm'),
        importTermHint: document.getElementById('retakeImportTermHint'),
        importDropzone: document.getElementById('retakeImportDropzone'),
        importFileInput: document.getElementById('retakeImportFile'),
        importFileName: document.getElementById('retakeImportFileName'),
        importClearBtn: document.getElementById('retakeImportClearBtn'),

        // Import results modal
        importResultsModal: document.getElementById('retakeImportResultsModal'),
        importResultsBody: document.getElementById('retakeImportResultsBody'),
    };
}

/** Generic open/close for the spring-pop <x-ui.modal> shell (shared markup/animation). */
export function openModal(modalEl) {
    if (!modalEl) return;
    const card = modalEl.querySelector(':scope > div');
    modalEl.classList.remove('invisible', 'opacity-0');
    modalEl.classList.add('flex');
    requestAnimationFrame(() => {
        card?.classList.remove('scale-90', 'opacity-0');
        card?.classList.add('scale-100', 'opacity-100');
    });
}

export function closeModal(modalEl, onClosed) {
    if (!modalEl) return;
    const card = modalEl.querySelector(':scope > div');
    modalEl.classList.add('opacity-0');
    card?.classList.remove('scale-100', 'opacity-100');
    card?.classList.add('scale-90', 'opacity-0');

    setTimeout(() => {
        modalEl.classList.add('invisible');
        modalEl.classList.remove('flex');
        onClosed?.();
    }, 300);
}

/** Thin wrapper around SweetAlert2's toast mixin. */
export const Toast = typeof Swal !== 'undefined'
    ? Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    })
    : { fire: (opts) => console.log('[Toast fallback]', opts) };
