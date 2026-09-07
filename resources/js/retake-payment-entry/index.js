import { buildDom, state, openModal, closeModal } from './core.js';
import { createApiService } from './api-service.js';
import { loadBatches } from './list.js';
import { openEntryModal, submitEntryForm } from './actions.js';
import { bindPagination } from './pagination.js';

document.addEventListener('DOMContentLoaded', () => {
    const dom = buildDom();
    const ApiService = createApiService(dom);

    const refresh = () => loadBatches(dom, ApiService);

    window.RetakeEntryModal = { toggle: (open) => (open ? openModal(dom.entryModal) : closeModal(dom.entryModal)) };

    dom.tableBody?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="add-entry"]');
        if (!btn) return;
        openEntryModal(dom, btn.dataset.id);
    });

    dom.entryForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        submitEntryForm(dom, ApiService, refresh);
    });

    bindPagination((page) => {
        state.page = page;
        refresh();
    });

    refresh();
});
