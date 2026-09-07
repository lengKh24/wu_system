import { CONFIG } from './config.js';
import { buildDom, state, openModal, closeModal, Toast } from './core.js';
import { createApiService } from './api-service.js';
import { loadRegistrations } from './list.js';
import { openPayModal, submitPayForm, handleInviteTelegram, setPayFile, clearPayFile } from './actions.js';
import { getRenderedRow } from './table-render.js';
import { bindPagination } from './pagination.js';

document.addEventListener('DOMContentLoaded', () => {
    const dom = buildDom();
    const ApiService = createApiService(dom);

    const refresh = () => {
        loadRegistrations(dom, ApiService);
        syncBulkBar(dom);
    };

    window.RetakePayModal = { toggle: (open) => (open ? openModal(dom.payModal) : closeModal(dom.payModal)) };

    initFilters(dom, refresh);
    initTable(dom, ApiService, refresh);
    initBulkBar(dom, refresh);
    bindPagination((page) => {
        state.page = page;
        refresh();
    });

    dom.payForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        submitPayForm(dom, ApiService, refresh);
    });
    initPayImagePicker(dom);

    loadTermsAndExamTypes(dom, ApiService).then(refresh);
});

/**
 * Three ways to stage an image on the Mark Paid modal: click-to-browse,
 * drag-and-drop onto the dropzone, or paste (Ctrl+V) an image copied from
 * anywhere else — a screenshot, a chat app, etc. Paste only fires while
 * the modal is open (checked via the dropzone's visibility) so it doesn't
 * hijack clipboard paste elsewhere on the page.
 */
function initPayImagePicker(dom) {
    dom.payDropzone?.addEventListener('click', () => dom.payFileInput?.click());
    dom.payDropzone?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            dom.payFileInput?.click();
        }
    });

    dom.payFileInput?.addEventListener('change', () => {
        const file = dom.payFileInput.files?.[0];
        if (file) setPayFile(dom, file);
    });

    dom.payClearBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        clearPayFile(dom);
    });

    ['dragover', 'dragleave', 'drop'].forEach((eventName) => {
        dom.payDropzone?.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    dom.payDropzone?.addEventListener('dragover', () => {
        dom.payDropzone.classList.add('border-indigo-400', 'dark:border-indigo-500/50');
    });
    dom.payDropzone?.addEventListener('dragleave', () => {
        dom.payDropzone.classList.remove('border-indigo-400', 'dark:border-indigo-500/50');
    });
    dom.payDropzone?.addEventListener('drop', (e) => {
        dom.payDropzone.classList.remove('border-indigo-400', 'dark:border-indigo-500/50');
        const file = e.dataTransfer?.files?.[0];
        if (file) setPayFile(dom, file);
    });

    document.addEventListener('paste', (e) => {
        if (dom.payModal?.classList.contains('invisible')) return; // modal not open

        const item = [...(e.clipboardData?.items || [])].find((i) => i.type.startsWith('image/'));
        if (!item) return;

        const file = item.getAsFile();
        if (file) setPayFile(dom, file);
    });
}

async function loadTermsAndExamTypes(dom, ApiService) {
    const [termsRes, typesRes] = await Promise.all([
        ApiService.request(`${CONFIG.TERMS_API}?per_page=100&sort=-start_date`),
        ApiService.request(`${CONFIG.EXAM_TYPES_API}?per_page=20`),
    ]);

    const terms = Array.isArray(termsRes.data?.data) ? termsRes.data.data : [];
    const examTypes = Array.isArray(typesRes.data?.data) ? typesRes.data.data : [];

    if (dom.termFilter) {
        dom.termFilter.innerHTML = '<option value="">គ្រប់រយៈពេល (All terms)</option>' +
            terms.map((t) => `<option value="${t.id}">${escapeHtml(t.title)}</option>`).join('');
    }
    if (dom.examTypeFilter) {
        dom.examTypeFilter.innerHTML = '<option value="">គ្រប់ប្រភេទ (All exam types)</option>' +
            examTypes.map((t) => `<option value="${t.id}">${escapeHtml(t.name_en || t.code)}</option>`).join('');
    }
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

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
        statusFilter: 'payment_status',
    };

    Object.entries(filterMap).forEach(([domKey, filterKey]) => {
        dom[domKey]?.addEventListener('change', (e) => {
            state.filters[filterKey] = e.target.value;
            state.page = 1;
            state.selectedIds.clear();
            refresh();
        });
    });
}

function initTable(dom, ApiService, refresh) {
    dom.tableBody?.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        if (btn.dataset.action === 'mark-paid') {
            openPayModal(dom, [id]);
        } else if (btn.dataset.action === 'invite-telegram') {
            await handleInviteTelegram(ApiService, id, refresh);
        }
    });

    dom.tableBody?.addEventListener('change', (e) => {
        const checkbox = e.target.closest('.row-select');
        if (!checkbox) return;

        const id = checkbox.dataset.id;
        const studentId = checkbox.dataset.studentId;
        const currentStudentId = getRenderedRow([...state.selectedIds][0])?.student?.id;

        if (checkbox.checked && state.selectedIds.size > 0 && currentStudentId && String(currentStudentId) !== String(studentId)) {
            checkbox.checked = false;
            Toast.fire({ icon: 'warning', title: 'សូមជ្រើសរើសសម្រាប់និស្សិតតែម្នាក់ក្នុងពេលតែមួយ (Select subjects for one student at a time)' });
            return;
        }

        if (checkbox.checked) {
            state.selectedIds.add(id);
        } else {
            state.selectedIds.delete(id);
        }

        syncBulkBar(dom);
    });
}

function initBulkBar(dom, refresh) {
    dom.markPaidSelectedBtn?.addEventListener('click', () => {
        if (state.selectedIds.size === 0) return;
        openPayModal(dom, [...state.selectedIds]);
    });
}

function syncBulkBar(dom) {
    const count = state.selectedIds.size;
    if (dom.selectedCountLabel) dom.selectedCountLabel.textContent = count;
    if (dom.markPaidSelectedBtn) dom.markPaidSelectedBtn.disabled = count === 0;
}
