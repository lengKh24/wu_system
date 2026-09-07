import { CONFIG } from './config.js';
import { state, Toast } from './core.js';
import { renderTable } from './table-render.js';
import { renderPagination } from './pagination.js';

/**
 * Loads the retake registrations list for the current filters/search/page
 * and renders it into the table. Every filter param is only sent when set,
 * matching RetakeRegistrationController@index's optional-filter contract.
 */
export async function loadRegistrations(dom, ApiService, permissions) {
    state.searchAbortController?.abort();
    state.searchAbortController = new AbortController();

    const params = new URLSearchParams({
        search: state.search || '',
        page: state.page,
        per_page: CONFIG.PER_PAGE,
    });

    Object.entries(state.filters).forEach(([key, value]) => {
        if (value) params.set(key, value);
    });

    const { error, aborted, data } = await ApiService.request(`${CONFIG.REGISTRATIONS_API}?${params.toString()}`, {
        signal: state.searchAbortController.signal,
    });

    if (aborted) return;
    if (error) {
        Toast.fire({ icon: 'error', title: 'មិនអាចទាញយកទិន្នន័យបានទេ' });
        return;
    }

    const rows = Array.isArray(data?.data) ? data.data : [];
    renderTable(dom, permissions, rows);
    renderPagination(data?.meta);
}
