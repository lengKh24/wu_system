import { CONFIG } from './config.js';
import { buildDom, state, openModal, closeModal } from './core.js';
import { createApiService } from './api-service.js';
import { loadRegistrations } from './list.js';
import { loadLookups } from './lookups.js';
import { openScoreModal, submitScoreForm } from './actions.js';
import { getRenderedRow } from './table-render.js';
import { bindPagination } from './pagination.js';

document.addEventListener('DOMContentLoaded', () => {
    const dom = buildDom();
    const ApiService = createApiService(dom);
    const permissions = {
        canScore: window.CAN_EDIT_RETAKE_SCORE === true,
    };

    const refresh = () => loadRegistrations(dom, ApiService, permissions);

    window.RetakeScoreModal = { toggle: (open) => (open ? openModal(dom.scoreModal) : closeModal(dom.scoreModal)) };

    initFilters(dom, refresh);
    initTable(dom);
    bindPagination((page) => {
        state.page = page;
        refresh();
    });

    dom.scoreForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        submitScoreForm(dom, ApiService, refresh);
    });

    loadLookups(dom, ApiService).then(refresh);
});

function initFilters(dom, refresh) {
    dom.searchInput?.addEventListener('input', (e) => {
        clearTimeout(state.debounceTimer);
        state.debounceTimer = setTimeout(() => {
            state.search = e.target.value;
            state.page = 1;
            refresh();
        }, CONFIG.DEBOUNCE_DELAY);
    });

    const filterMap = {
        termFilter: 'retake_term_id',
        examTypeFilter: 'exam_type_id',
        outcomeFilter: 'outcome',
    };

    Object.entries(filterMap).forEach(([domKey, filterKey]) => {
        dom[domKey]?.addEventListener('change', (e) => {
            state.filters[filterKey] = e.target.value;
            state.page = 1;
            refresh();
        });
    });
}

function initTable(dom) {
    dom.tableBody?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="score"]');
        if (!btn) return;

        const row = getRenderedRow(btn.dataset.id);
        if (row) openScoreModal(dom, row);
    });
}
