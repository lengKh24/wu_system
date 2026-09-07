import { CONFIG } from './config.js';
import { buildDom, state } from './core.js';
import { createApiService } from './api-service.js';
import { loadRegistrations } from './list.js';
import { bindPagination } from './pagination.js';

document.addEventListener('DOMContentLoaded', () => {
    const dom = buildDom();
    const ApiService = createApiService(dom);

    const refresh = () => loadRegistrations(dom, ApiService);

    dom.searchInput?.addEventListener('input', (e) => {
        clearTimeout(state.debounceTimer);
        state.debounceTimer = setTimeout(() => {
            state.search = e.target.value;
            state.page = 1;
            refresh();
        }, CONFIG.DEBOUNCE_DELAY);
    });

    bindPagination((page) => {
        state.page = page;
        refresh();
    });

    refresh();
});
