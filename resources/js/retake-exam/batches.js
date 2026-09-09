import { CONFIG } from './config.js';
import { state, Toast } from './core.js';

const EXAM_TYPE_LABEL_KH = {
    '1st_supplementary': 'ប្រឡងសង លើកទី១',
    '2nd_supplementary': 'ប្រឡងសង លើកទី២',
    restudy: 'រៀនសង',
    special: 'ប្រឡងពិសេស',
};

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

/** Loads batches (most recent first) and renders the batch chip strip. */
export async function loadBatches(dom, ApiService, permissions, onSelect) {
    const { error, data } = await ApiService.request(`${CONFIG.BATCHES_API}?per_page=30&sort=-generated_at`);
    if (error) return;

    state.batches = Array.isArray(data?.data) ? data.data : [];
    renderBatchStrip(dom, permissions, onSelect);
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function renderBatchStrip(dom, permissions, onSelect) {
    if (!dom.batchStrip) return;

    if (state.batches.length === 0) {
        dom.batchStrip.innerHTML = '<p class="text-xs text-neutral-400 italic px-1">មិនទាន់មានបាច់ណាមួយទេ (No batches yet — import the 1st Supplementary file to create one).</p>';
        return;
    }

    dom.batchStrip.innerHTML = state.batches.map((batch) => {
        const isActive = String(state.filters.batch_id) === String(batch.id);
        const examTypeLabel = EXAM_TYPE_LABEL_KH[batch.exam_type?.code] || batch.exam_type?.name_en || '—';
        const statusColor = batch.status === 'closed'
            ? 'border-neutral-300 dark:border-white/20'
            : 'border-emerald-300 dark:border-emerald-500/40';
        const activeClasses = isActive
            ? 'ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-500/10'
            : 'bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-white/5';

        const closeBtn = permissions.canEditBatch && batch.status !== 'closed'
            ? `<button data-batch-action="close" data-batch-id="${batch.id}" class="text-[11px] font-semibold text-amber-600 hover:underline" title="Close batch">Close</button>`
            : '';
        const carryBtn = permissions.canEditBatch && batch.status === 'closed'
            ? `<button data-batch-action="carry-forward" data-batch-id="${batch.id}" data-batch-exam-type="${batch.exam_type?.code ?? ''}" class="text-[11px] font-semibold text-indigo-600 hover:underline" title="Generate next stage">Carry forward →</button>`
            : '';
        const telegramBtn = permissions.canEditBatch
            ? `<button data-batch-action="telegram" data-batch-id="${batch.id}" data-batch-link="${escapeHtml(batch.telegram_group_link ?? '')}" class="text-[11px] font-semibold text-sky-600 hover:underline" title="Set Telegram group link">Telegram</button>`
            : '';
        const deleteBtn = permissions.canDeleteBatch
            ? `<button data-batch-action="delete" data-batch-id="${batch.id}" class="ml-auto p-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-colors" title="Delete batch permanently (testing)">
                <svg class="w-3.5 h-3.5 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>`
            : '';

        return `
        <div class="shrink-0 w-64 border ${statusColor} ${activeClasses} rounded-xl p-3 space-y-2 transition-colors cursor-pointer" data-batch-select="${batch.id}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-neutral-800 dark:text-neutral-100">${examTypeLabel}</span>
                <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded ${batch.status === 'closed' ? 'bg-neutral-200 text-neutral-600 dark:bg-white/10 dark:text-neutral-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'}">${batch.status}</span>
            </div>
            <div class="text-[11px] text-neutral-500 dark:text-neutral-400">${escapeHtml(batch.term?.title || 'No term (Special)')}</div>
            <div class="text-[11px] text-neutral-400">${batch.registrations_count ?? 0} registrations</div>
            <div class="flex items-center gap-3 pt-1 border-t border-neutral-100 dark:border-white/5">${closeBtn}${carryBtn}${telegramBtn}${deleteBtn}</div>
        </div>`;
    }).join('');

    dom.batchStrip.querySelectorAll('[data-batch-select]').forEach((el) => {
        el.addEventListener('click', (e) => {
            if (e.target.closest('[data-batch-action]')) return;
            const id = el.dataset.batchSelect;
            state.filters.batch_id = String(state.filters.batch_id) === String(id) ? '' : id;
            renderBatchStrip(dom, permissions, onSelect);
            onSelect();
        });
    });
}

/**
 * Opens the "New Retake Term" modal, pre-filled with whatever campuses
 * exist. Requires at least one Campus (campus.index owns creating those) —
 * shown as an inline hint + link rather than a blocking validation error.
 */
export async function openTermModal(dom, ApiService) {
    dom.termForm?.reset();

    const { error, data } = await ApiService.request(`${CONFIG.CAMPUSES_API}?per_page=100`);
    const campuses = !error && Array.isArray(data?.data) ? data.data : [];

    if (dom.termCampusSelect) {
        dom.termCampusSelect.innerHTML = campuses.map((c) => `<option value="${c.id}">${escapeHtml(c.name_en || c.name)}</option>`).join('');
        dom.termCampusSelect.disabled = campuses.length === 0;
    }
    dom.termCampusHint?.classList.toggle('hidden', campuses.length > 0);

    window.RetakeTermModal.toggle(true);
}

/** Submits the "New Retake Term" form. */
export async function submitTermForm(dom, ApiService, onDone) {
    const campus_id = dom.termCampusSelect?.value;
    const title = dom.termTitleInput?.value.trim();

    if (!campus_id) {
        Toast.fire({ icon: 'warning', title: 'សូមបង្កើតបរិវេណជាមុនសិន (Create a campus first)' });
        return;
    }
    if (!title) {
        Toast.fire({ icon: 'warning', title: 'សូមបញ្ចូលចំណងជើង (Please enter a title)' });
        dom.termTitleInput?.focus();
        return;
    }

    const { error, data } = await ApiService.request(CONFIG.TERMS_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            campus_id,
            title,
            start_date: dom.termStartInput?.value || null,
            end_date: dom.termEndInput?.value || null,
            is_active: true,
        }),
    });

    if (error) {
        const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
        Toast.fire({ icon: 'error', title: firstError || data?.message || 'មិនអាចបង្កើតបានទេ' });
        return;
    }

    Toast.fire({ icon: 'success', title: 'បង្កើតរយៈពេលជោគជ័យ!' });
    window.RetakeTermModal.toggle(false);
    await loadLookups(dom, ApiService);
    onDone();
}

/** Opens the "Import 1st Supplementary" modal, pre-filled with existing terms. */
export function openImportModal(dom) {
    dom.importForm?.reset();
    clearImportFile(dom);

    if (dom.importTermSelect) {
        dom.importTermSelect.innerHTML = state.terms.map((t) => `<option value="${t.id}">${escapeHtml(t.title)}</option>`).join('');
        dom.importTermSelect.disabled = state.terms.length === 0;
    }
    dom.importTermHint?.classList.toggle('hidden', state.terms.length > 0);

    window.RetakeImportModal.toggle(true);
}

/**
 * Stages a file (from the picker or drag-drop) for import. Assigns it onto
 * the real <input type="file"> via DataTransfer so both paths end up in
 * the exact same place — submitImportForm() doesn't need to know which one
 * was used.
 */
export function setImportFile(dom, file) {
    if (!file) return;

    const transfer = new DataTransfer();
    transfer.items.add(file);
    if (dom.importFileInput) dom.importFileInput.files = transfer.files;

    if (dom.importFileName) dom.importFileName.textContent = file.name;
    dom.importClearBtn?.classList.remove('hidden');
}

export function clearImportFile(dom) {
    if (dom.importFileInput) dom.importFileInput.value = '';
    if (dom.importFileName) dom.importFileName.textContent = '';
    dom.importClearBtn?.classList.add('hidden');
}

function setImportSubmitting(dom, isSubmitting) {
    if (dom.importSubmitBtn) dom.importSubmitBtn.disabled = isSubmitting;
    dom.importSpinner?.classList.toggle('hidden', !isSubmitting);
    if (dom.importSubmitLabel) {
        dom.importSubmitLabel.textContent = isSubmitting
            ? 'កំពុងនាំចូល... (Importing...)'
            : 'នាំចូល (Import)';
    }
}

/** Submits the "Import 1st Supplementary" form. */
export async function submitImportForm(dom, ApiService, onDone) {
    // Guard against a spam-click firing the same import twice while the
    // first request is still in flight.
    if (dom.importSubmitBtn?.disabled) return;

    const termId = dom.importTermSelect?.value;
    const file = dom.importFileInput?.files[0];

    if (!termId) {
        Toast.fire({ icon: 'warning', title: 'សូមបង្កើតរយៈពេលមុនសិន (Create a retake term first)' });
        return;
    }
    if (!file) {
        Toast.fire({ icon: 'warning', title: 'សូមជ្រើសរើសឯកសារ (Please choose a file)' });
        return;
    }

    const body = new FormData();
    body.append('retake_term_id', termId);
    body.append('file', file);

    setImportSubmitting(dom, true);

    try {
        const { error, data } = await ApiService.request(`${CONFIG.BATCHES_API}/import`, {
            method: 'POST',
            body,
        });

        if (error) {
            Toast.fire({ icon: 'error', title: data?.message || 'ការនាំចូលបរាជ័យ (Import failed)' });
            return;
        }

        window.RetakeImportModal.toggle(false);
        clearImportFile(dom);

        const report = data?.data?.report ?? {};
        Toast.fire({ icon: 'success', title: `នាំចូលជោគជ័យ! (${report.created_count ?? 0} row(s) created)` });

        renderImportResults(dom, report);
        onDone();
    } finally {
        setImportSubmitting(dom, false);
    }
}

/**
 * Renders the import report as a proper themed modal — grouped, readable
 * tables per issue type — instead of dumping JSON.stringify(row) into a
 * plain SweetAlert box. Opens even on a clean import (shows just the
 * success summary) so REG always sees the same place for results.
 */
function renderImportResults(dom, report) {
    if (!dom.importResultsBody) return;

    const summary = `
        <div class="flex items-center gap-3 px-4 py-3.5 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200/70 dark:border-emerald-500/20 rounded-xl">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <span class="text-sm font-bold text-emerald-800 dark:text-emerald-300">${report.created_count ?? 0} row(s) created</span>
        </div>`;

    const sections = [
        importIssueTable(
            'សិស្សមិនត្រូវគ្នា (Student not matched)',
            'rose',
            report.skipped_student,
            ['Row', 'Student Code', 'Reason'],
            (r) => [r.row, r.student_code, r.reason]
        ),
        importIssueTable(
            'សាស្ត្រាចារ្យមិនត្រូវគ្នា — បានបង្កើតដោយគ្មានឈ្មោះ (Lecturer not matched — row still created)',
            'amber',
            report.flagged_lecturer,
            ['Row', 'Student Code', 'Lecturer', 'Reason'],
            (r) => [r.row, r.student_code, r.lecturer, r.reason]
        ),
    ].filter(Boolean);

    dom.importResultsBody.innerHTML = summary + sections.join('');
    window.RetakeImportResultsModal.toggle(true);
}

function importIssueTable(title, color, rows, headers, mapRow) {
    if (!Array.isArray(rows) || rows.length === 0) return '';

    const colorClasses = {
        rose: 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200/70 dark:border-rose-500/20',
        amber: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200/70 dark:border-amber-500/20',
    }[color];

    return `
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold rounded-full border ${colorClasses}">${rows.length}</span>
                <h4 class="text-sm font-bold text-neutral-800 dark:text-neutral-100">${escapeHtml(title)}</h4>
            </div>
            <div class="overflow-x-auto border border-neutral-200 dark:border-white/10 rounded-xl">
                <table class="w-full text-xs text-left">
                    <thead class="bg-neutral-50 dark:bg-white/5 text-neutral-500 dark:text-neutral-400 uppercase">
                        <tr>${headers.map((h) => `<th class="px-3 py-2 font-bold">${escapeHtml(h)}</th>`).join('')}</tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-white/5">
                        ${rows.map((r) => `<tr>${mapRow(r).map((v) => `<td class="px-3 py-2 text-neutral-700 dark:text-neutral-300">${escapeHtml(v ?? '—')}</td>`).join('')}</tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>`;
}

export async function handleCloseBatch(ApiService, batchId, onDone) {
    const confirmation = await Swal.fire({
        title: 'បិទបាច់នេះ? (Close this batch?)',
        text: 'ត្រូវប្រាកដថាលទ្ធផលទាំងអស់ត្រូវបានកត់ត្រារួច (Make sure all outcomes are recorded first).',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'បិទ (Close)',
        cancelButtonText: 'បោះបង់',
    });
    if (!confirmation.isConfirmed) return;

    const { error, data } = await ApiService.request(`${CONFIG.BATCHES_API}/${batchId}/close`, { method: 'PATCH' });
    if (error) {
        Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចបិទបានទេ' });
        return;
    }
    Toast.fire({ icon: 'success', title: 'បិទបាច់ជោគជ័យ! (Batch closed)' });
    onDone();
}

const NEXT_STAGE = {
    '1st_supplementary': '2nd_supplementary',
    '2nd_supplementary': 'restudy',
};

export async function handleCarryForward(ApiService, batchId, currentCode, onDone) {
    const nextCode = NEXT_STAGE[currentCode];
    const nextType = state.examTypes.find((t) => t.code === nextCode);

    if (!nextType) {
        Toast.fire({ icon: 'warning', title: 'គ្មានដំណាក់កាលបន្ទាប់សម្រាប់ប្រភេទនេះទេ (No next stage for this exam type)' });
        return;
    }

    const confirmation = await Swal.fire({
        title: `បង្កើត ${escapeHtml(nextType.name_en)}? (Generate ${escapeHtml(nextType.name_en)}?)`,
        text: 'និស្សិតដែលធ្លាក់ ឬអវត្តមាននឹងត្រូវផ្ទេរទៅដំណាក់កាលនេះ (Failed/absent students will carry forward into this stage).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'បង្កើត (Generate)',
        cancelButtonText: 'បោះបង់',
    });
    if (!confirmation.isConfirmed) return;

    const { error, data } = await ApiService.request(`${CONFIG.BATCHES_API}/${batchId}/carry-forward`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ exam_type_id: nextType.id }),
    });

    if (error) {
        Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចបង្កើតបានទេ' });
        return;
    }
    Toast.fire({ icon: 'success', title: 'បង្កើតដំណាក់កាលបន្ទាប់ជោគជ័យ!' });
    onDone();
}

/**
 * Permanently deletes a batch (and, via FK cascade, every registration in
 * it) — not a soft-delete/trash. Meant for REG to quickly clear out a bad
 * test import while trying the flow, not a normal production action.
 */
export async function handleDeleteBatch(ApiService, batchId, onDone) {
    const confirmation = await Swal.fire({
        title: 'លុបបាច់នេះជាអចិន្ត្រៃយ៍? (Permanently delete this batch?)',
        text: 'ការចុះឈ្មោះទាំងអស់ក្នុងបាច់នេះនឹងត្រូវលុបជាមួយ — មិនអាចយកមកវិញបានឡើយ! (Every registration in this batch is deleted with it — cannot be undone.)',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'បាទ/ចាស លុបជាអចិន្ត្រៃយ៍!',
        cancelButtonText: 'បោះបង់',
    });
    if (!confirmation.isConfirmed) return;

    const { error, data } = await ApiService.request(`${CONFIG.BATCHES_API}/${batchId}/clear`, { method: 'DELETE' });
    if (error) {
        Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចលុបបានទេ' });
        return;
    }
    Toast.fire({ icon: 'success', title: 'លុបបាច់ជោគជ័យ!' });
    if (String(state.filters.batch_id) === String(batchId)) state.filters.batch_id = '';
    onDone();
}

export async function handleSetTelegram(ApiService, batchId, currentLink, onDone) {
    const { value: link } = await Swal.fire({
        title: 'តំណភ្ជាប់ Telegram (Telegram group link)',
        input: 'text',
        inputValue: currentLink || '',
        inputPlaceholder: 'https://t.me/...',
        showCancelButton: true,
        confirmButtonText: 'រក្សាទុក (Save)',
        cancelButtonText: 'បោះបង់',
    });
    if (link === undefined) return;

    const { error, data } = await ApiService.request(`${CONFIG.BATCHES_API}/${batchId}/telegram`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ telegram_group_link: link }),
    });

    if (error) {
        Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចរក្សាទុកបានទេ' });
        return;
    }
    Toast.fire({ icon: 'success', title: 'រក្សាទុកជោគជ័យ!' });
    onDone();
}
