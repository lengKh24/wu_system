import { CONFIG } from './config.js';
import { state, Toast } from './core.js';
import { renderTable } from './table-render.js';
import { renderPagination } from './pagination.js';

export async function loadRegistrations(dom, ApiService) {
    state.searchAbortController?.abort();
    state.searchAbortController = new AbortController();

    const params = new URLSearchParams({
        search: state.search || '',
        page: state.page,
        per_page: CONFIG.PER_PAGE,
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
