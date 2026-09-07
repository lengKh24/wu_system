/**
 * core.js — small, always-touched-together infrastructure pieces for
 * ACC's payment-entry reconciliation page.
 */

export const state = {
    page: 1,
    // Payment batch id currently open in the Add Entry modal.
    enteringBatchId: null,
};

export function buildDom() {
    return {
        tableBody: document.getElementById('retake-entry-table-body'),
        loader: document.getElementById('loading-overlay'),

        entryModal: document.getElementById('retakeEntryModal'),
        entryModalTitle: document.getElementById('retakeEntryModalTitle'),
        entryForm: document.getElementById('retakeEntryForm'),
        entryContext: document.getElementById('retakeEntryContext'),
        entryNumberInput: document.getElementById('retakeEntryNumber'),
        entryNoteInput: document.getElementById('retakeEntryNote'),
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
