import { CONFIG } from './config.js';
import { buildDom, state } from './core.js';
import { createApiService } from './api-service.js';
import { initFormLookups } from './address-cascade.js';
import { toggleModal, togglePreviewModal, registerModalCloser, closeModalByName, switchTab } from './ui.js';
import { handlePreviewAction } from './preview.js';
import { loadStudents, handleEditAction, handleDeleteAction, handleFormSubmit } from './student-form.js';
import { bindPagination } from './student-pagination.js';
import { initStudentImportExport } from './import-export.js';

/**
 * THE single DOMContentLoaded listener for this page.
 * (The original file had two of these binding the same submit/search
 * listeners twice, which was silently double-submitting the form on every
 * save. Don't add a second one here — everything goes through initEvents().)
 */
document.addEventListener('DOMContentLoaded', () => {
    const dom = buildDom();
    const ApiService = createApiService(dom);

    // Register how to close each modal, by name. Any button anywhere with
    // data-close-modal="student" or data-close-modal="preview" now works —
    // no per-button globals to remember or keep in sync.
    registerModalCloser('student', () => toggleModal(dom, false));
    registerModalCloser('preview', () => togglePreviewModal(false));

    // Kept for old inline onclick="" markup that hasn't been migrated yet
    // (see Blade snippet below). New buttons should use data-close-modal instead.
    window.AppModal = {
        toggle: (open) => {
            if (open) initFormLookups(ApiService);
            toggleModal(dom, open);
        },
    };
    window.toggleModal = (open) => toggleModal(dom, open);
    window.togglePreviewModal = togglePreviewModal;
    window.switchTab = switchTab;

    bindPagination((page) => loadStudents(dom, ApiService, dom.searchInput?.value || '', page));
    initEvents(dom, ApiService);
    initStudentImportExport(ApiService, () => loadStudents(dom, ApiService, dom.searchInput?.value || ''));
    loadStudents(dom, ApiService);
});

function initEvents(dom, ApiService) {
    // Any close button, anywhere in the document, with data-close-modal="name"
    // — one listener handles all current and future modals.
    document.addEventListener('click', (e) => {
        const closeBtn = e.target.closest('[data-close-modal]');
        if (!closeBtn) return;
        closeModalByName(closeBtn.getAttribute('data-close-modal'));
    });

    // Search input, debounced
    dom.searchInput?.addEventListener('input', (e) => {
        clearTimeout(state.debounceTimer);
        state.debounceTimer = setTimeout(() => {
            loadStudents(dom, ApiService, e.target.value);
        }, CONFIG.DEBOUNCE_DELAY);
    });

    // Create/update form submit
    dom.form?.addEventListener('submit', (e) => handleFormSubmit(dom, ApiService, e));

    // payment_as is either yearly or semester, never both — checking one unchecks the other
    const yearlyCheckbox = dom.form?.querySelector('#payment_as_yearly');
    const semesterCheckbox = dom.form?.querySelector('#payment_as_semester');
    yearlyCheckbox?.addEventListener('change', () => {
        if (yearlyCheckbox.checked) semesterCheckbox.checked = false;
    });
    semesterCheckbox?.addEventListener('change', () => {
        if (semesterCheckbox.checked) yearlyCheckbox.checked = false;
    });

    // Table row actions (preview / edit / delete) via event delegation
    dom.tableBody?.addEventListener('click', (e) => {
        const targetBtn = e.target.closest('button[data-action]');
        if (!targetBtn) return;

        const action = targetBtn.getAttribute('data-action');
        const targetId = targetBtn.getAttribute('data-id');

        if (action === 'preview') handlePreviewAction(ApiService, targetId);
        if (action === 'edit') handleEditAction(dom, ApiService, targetId);
        if (action === 'delete') handleDeleteAction(dom, ApiService, targetId);
    });

    // Smart-hint tooltips on form inputs
    const inputsWithHints = document.querySelectorAll('#studentForm input, #studentForm textarea');
    inputsWithHints.forEach((input) => {
        const hintBox = input.parentElement.querySelector('.smart-hint');
        const textMessage = input.getAttribute('data-hint');
        if (!hintBox || !textMessage) return;

        hintBox.textContent = textMessage;

        input.addEventListener('input', function handleHintInput() {
            const hasValue = this.value.trim().length > 0;
            hintBox.classList.toggle('opacity-0', !hasValue);
            hintBox.classList.toggle('scale-95', !hasValue);
            hintBox.classList.toggle('translate-y-1', !hasValue);
            hintBox.classList.toggle('opacity-100', hasValue);
            hintBox.classList.toggle('scale-100', hasValue);
            hintBox.classList.toggle('-translate-y-2', hasValue);
        });

        input.addEventListener('blur', () => {
            hintBox.classList.remove('opacity-100', 'scale-100', '-translate-y-2');
            hintBox.classList.add('opacity-0', 'scale-95', 'translate-y-1');
        });
    });
}
