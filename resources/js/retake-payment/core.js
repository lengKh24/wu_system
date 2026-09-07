/**
 * core.js — small, always-touched-together infrastructure pieces for SA's
 * retake payment page.
 */

export const state = {
    debounceTimer: null,
    searchAbortController: null,
    search: '',
    filters: {
        exam_type_id: '',
        retake_term_id: '',
        payment_status: 'unpaid', // SA opens here to work through what's owed
    },
    page: 1,
    terms: [],
    examTypes: [],
    // Row ids currently ticked for bulk "Mark Paid" — a Set, not an array,
    // since add/remove/has all need to be O(1) as the table re-renders.
    selectedIds: new Set(),
    // Registration ids + student currently open in the Mark Paid modal —
    // set right before the modal opens, read on submit.
    payingIds: [],
    payingStudentId: null,
    // The image File currently staged in the Mark Paid modal (from file
    // picker, drag-drop, or clipboard paste) — read on submit.
    payingFile: null,
};

export function buildDom() {
    return {
        tableBody: document.getElementById('retake-payment-table-body'),
        searchInput: document.getElementById('retakePaymentSearchInput'),
        loader: document.getElementById('loading-overlay'),
        termFilter: document.getElementById('retakePaymentTermFilter'),
        examTypeFilter: document.getElementById('retakePaymentExamTypeFilter'),
        statusFilter: document.getElementById('retakePaymentStatusFilter'),
        markPaidSelectedBtn: document.getElementById('retakeMarkPaidSelectedBtn'),
        selectedCountLabel: document.getElementById('retakeSelectedCountLabel'),

        // Mark Paid modal
        payModal: document.getElementById('retakePayModal'),
        payForm: document.getElementById('retakePayForm'),
        payContext: document.getElementById('retakePayContext'),
        payDropzone: document.getElementById('retakePayDropzone'),
        payFileInput: document.getElementById('retakePayFile'),
        payPreview: document.getElementById('retakePayPreview'),
        payFileName: document.getElementById('retakePayFileName'),
        payClearBtn: document.getElementById('retakePayClearBtn'),
        payRemark: document.getElementById('retakePayRemark'),
    };
}

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

export const Toast = typeof Swal !== 'undefined'
    ? Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    })
    : { fire: (opts) => console.log('[Toast fallback]', opts) };
