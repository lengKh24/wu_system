import { CONFIG } from './config.js';
import { state } from './core.js';

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

/** Loads terms + exam types once and fills the filter <select>s. */
export async function loadLookups(dom, ApiService) {
    const [termsRes, typesRes] = await Promise.all([
        ApiService.request(`${CONFIG.TERMS_API}?per_page=100&sort=-start_date`),
        ApiService.request(`${CONFIG.EXAM_TYPES_API}?per_page=20`),
    ]);

    state.terms = Array.isArray(termsRes.data?.data) ? termsRes.data.data : [];
    state.examTypes = Array.isArray(typesRes.data?.data) ? typesRes.data.data : [];

    if (dom.termFilter) {
        dom.termFilter.innerHTML = '<option value="">គ្រប់រយៈពេល (All terms)</option>' +
            state.terms.map((t) => `<option value="${t.id}">${escapeHtml(t.title)}</option>`).join('');
    }
    if (dom.examTypeFilter) {
        dom.examTypeFilter.innerHTML = '<option value="">គ្រប់ប្រភេទ (All exam types)</option>' +
            state.examTypes.map((t) => `<option value="${t.id}">${escapeHtml(t.name_en || t.code)}</option>`).join('');
    }
}
