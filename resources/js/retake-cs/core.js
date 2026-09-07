/**
 * core.js — small, always-touched-together infrastructure pieces for
 * Customer Service's read-only retake exam lookup page.
 */

export const state = {
    debounceTimer: null,
    searchAbortController: null,
    search: '',
    page: 1,
};

export function buildDom() {
    return {
        tableBody: document.getElementById('retake-cs-table-body'),
        searchInput: document.getElementById('retakeCsSearchInput'),
        loader: document.getElementById('loading-overlay'),
    };
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
