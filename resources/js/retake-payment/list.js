import { CONFIG } from './config.js';
import { state, Toast } from './core.js';
import { renderTable } from './table-render.js';
import { renderPagination } from './pagination.js';

/**
 * SA only ever works confirmed registrations — confirmed_only=1 is always
 * sent (see RetakeRegistrationController@index's confirmed_only branch).
 */
export async function loadRegistrations(dom, ApiService) {
    state.searchAbortController?.abort();
    state.searchAbortController = new AbortController();

    const params = new URLSearchParams({
        search: state.search || '',
        page: state.page,
        per_page: CONFIG.PER_PAGE,
        confirmed_only: '1',
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
    renderTable(dom, rows);
    renderPagination(data?.meta);
}
