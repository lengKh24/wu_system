/**
 * core.js — small, always-touched-together infrastructure pieces for
 * Score's retake-score page. No logic of its own; exists to be imported by
 * files that DO have logic (api-service, list, actions, index).
 */

export const state = {
    debounceTimer: null,
    searchAbortController: null,
    search: '',
    filters: {
        exam_type_id: '',
        retake_term_id: '',
        outcome: '',
    },
    page: 1,
    terms: [],
    examTypes: [],
    // Registration id currently open in the Score modal.
    scoringId: null,
};

/** Builds a fresh DOM selector map. Call once, on DOMContentLoaded. */
export function buildDom() {
    return {
        tableBody: document.getElementById('retake-score-table-body'),
        searchInput: document.getElementById('retakeScoreSearchInput'),
        loader: document.getElementById('loading-overlay'),
        termFilter: document.getElementById('retakeScoreTermFilter'),
        examTypeFilter: document.getElementById('retakeScoreExamTypeFilter'),
        outcomeFilter: document.getElementById('retakeScoreOutcomeFilter'),

        // Score modal
        scoreModal: document.getElementById('retakeScoreModal'),
        scoreForm: document.getElementById('retakeScoreForm'),
        scoreContext: document.getElementById('retakeScoreContext'),
        scoreValueInput: document.getElementById('retakeScoreValue'),
        scoreRemarkInput: document.getElementById('retakeScoreRemark'),
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
