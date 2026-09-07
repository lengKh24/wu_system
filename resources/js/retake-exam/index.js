import { CONFIG } from './config.js';
import { buildDom, state, openModal, closeModal } from './core.js';
import { createApiService } from './api-service.js';
import { loadRegistrations } from './list.js';
import {
    loadLookups, loadBatches,
    openTermModal, submitTermForm,
    openImportModal, submitImportForm, setImportFile, clearImportFile,
    handleCloseBatch, handleCarryForward, handleSetTelegram, handleDeleteBatch,
} from './batches.js';
import { bindPagination } from './pagination.js';
import { handleSetOutcome, handleToggleSelection, handleDelete, handleRestore } from './actions.js';

document.addEventListener('DOMContentLoaded', () => {
    const dom = buildDom();
    const ApiService = createApiService(dom);
    const permissions = {
        canEdit: window.CAN_EDIT_RETAKE_REGISTRATION === true,
        canDelete: window.CAN_DELETE_RETAKE_REGISTRATION === true,
        canEditBatch: window.CAN_EDIT_RETAKE_BATCH === true,
        canCreateBatch: window.CAN_CREATE_RETAKE_BATCH === true,
        canCreateTerm: window.CAN_CREATE_RETAKE_TERM === true,
        canDeleteBatch: window.CAN_DELETE_RETAKE_BATCH === true,
    };

    const refresh = () => loadRegistrations(dom, ApiService, permissions);
    const refreshBatchesAndList = () => {
        loadBatches(dom, ApiService, permissions, refresh);
        refresh();
    };

    if (!permissions.canCreateBatch) dom.importBtn?.classList.add('hidden');
    if (!permissions.canCreateTerm) dom.newTermBtn?.classList.add('hidden');

    // Every <x-ui.modal> toggles itself via `<closeFn>.toggle(open)` — each
    // modal on this page gets its own named global (see role/index.js for
    // the same pattern with more than one modal on a page).
    window.RetakeTermModal = { toggle: (open) => (open ? openModal(dom.termModal) : closeModal(dom.termModal)) };
    window.RetakeImportModal = { toggle: (open) => (open ? openModal(dom.importModal) : closeModal(dom.importModal)) };
    window.RetakeImportResultsModal = { toggle: (open) => (open ? openModal(dom.importResultsModal) : closeModal(dom.importResultsModal)) };

    initFilters(dom, refresh);
    initTable(dom, ApiService, refresh);
    initBatchStrip(dom, ApiService, refreshBatchesAndList);
    bindPagination((page) => {
        state.page = page;
        refresh();
    });

    dom.importBtn?.addEventListener('click', () => openImportModal(dom));
    dom.newTermBtn?.addEventListener('click', () => openTermModal(dom, ApiService));
    dom.termForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        submitTermForm(dom, ApiService, refreshBatchesAndList);
    });
    dom.importForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        submitImportForm(dom, ApiService, refreshBatchesAndList);
    });
    dom.exportBtn?.addEventListener('click', () => exportCurrentFilters());
    initImportDropzone(dom);

    loadLookups(dom, ApiService).then(() => {
        loadBatches(dom, ApiService, permissions, refresh);
        refresh();
    });
});

/**
 * Downloads happen as a plain browser navigation, not a fetch — the route
 * is under the same session-cookie 'auth' middleware as the rest of this
 * page, so a same-origin GET already carries the right cookies; no need to
 * fetch+blob it. Opens in a new tab so the Main List itself isn't disturbed.
 */
function exportCurrentFilters() {
    const params = new URLSearchParams();
    Object.entries(state.filters).forEach(([key, value]) => {
        if (value) params.set(key, value);
    });
    window.open(`${CONFIG.REGISTRATIONS_EXPORT_API}?${params.toString()}`, '_blank');
}

/** Click-to-browse or drag-and-drop a file onto the Import dropzone. */
function initImportDropzone(dom) {
    dom.importDropzone?.addEventListener('click', () => dom.importFileInput?.click());
    dom.importDropzone?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            dom.importFileInput?.click();
        }
    });

    dom.importFileInput?.addEventListener('change', () => {
        const file = dom.importFileInput.files?.[0];
        if (file) setImportFile(dom, file);
    });

    dom.importClearBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        clearImportFile(dom);
    });

    ['dragover', 'dragleave', 'drop'].forEach((eventName) => {
        dom.importDropzone?.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    dom.importDropzone?.addEventListener('dragover', () => {
        dom.importDropzone.classList.add('border-indigo-400', 'dark:border-indigo-500/50');
    });
    dom.importDropzone?.addEventListener('dragleave', () => {
        dom.importDropzone.classList.remove('border-indigo-400', 'dark:border-indigo-500/50');
    });
    dom.importDropzone?.addEventListener('drop', (e) => {
        dom.importDropzone.classList.remove('border-indigo-400', 'dark:border-indigo-500/50');
        const file = e.dataTransfer?.files?.[0];
        if (file) setImportFile(dom, file);
    });
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
        paymentFilter: 'payment_status',
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

function initTable(dom, ApiService, refresh) {
    dom.tableBody?.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        if (btn.dataset.action === 'outcome') {
            await handleSetOutcome(ApiService, id, btn.dataset.outcome, refresh);
        } else if (btn.dataset.action === 'toggle-selection') {
            await handleToggleSelection(ApiService, id, btn.dataset.selected === '1', refresh);
        } else if (btn.dataset.action === 'delete') {
            await handleDelete(ApiService, id, refresh);
        } else if (btn.dataset.action === 'restore') {
            await handleRestore(ApiService, id, refresh);
        }
    });
}

function initBatchStrip(dom, ApiService, refresh) {
    dom.batchStrip?.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-batch-action]');
        if (!btn) return;

        const batchId = btn.dataset.batchId;
        if (btn.dataset.batchAction === 'close') {
            await handleCloseBatch(ApiService, batchId, refresh);
        } else if (btn.dataset.batchAction === 'carry-forward') {
            await handleCarryForward(ApiService, batchId, btn.dataset.batchExamType, refresh);
        } else if (btn.dataset.batchAction === 'telegram') {
            await handleSetTelegram(ApiService, batchId, btn.dataset.batchLink, refresh);
        } else if (btn.dataset.batchAction === 'delete') {
            await handleDeleteBatch(ApiService, batchId, refresh);
        }
    });
}
