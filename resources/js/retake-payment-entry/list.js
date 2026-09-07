import { CONFIG } from './config.js';
import { state, Toast } from './core.js';
import { renderTable } from './table-render.js';
import { renderPagination } from './pagination.js';

export async function loadBatches(dom, ApiService) {
    const params = new URLSearchParams({
        page: state.page,
        per_page: CONFIG.PER_PAGE,
    });

    const { error, data } = await ApiService.request(`${CONFIG.PAYMENT_BATCHES_API}?${params.toString()}`);

    if (error) {
        Toast.fire({ icon: 'error', title: 'មិនអាចទាញយកទិន្នន័យបានទេ' });
        return;
    }

    const rows = Array.isArray(data?.data) ? data.data : [];
    renderTable(dom, rows);
    renderPagination(data?.meta);
}
