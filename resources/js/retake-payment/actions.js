import { CONFIG } from './config.js';
import { state, Toast } from './core.js';
import { getRenderedRow } from './table-render.js';
import { compressImage } from './image-compress.js';

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

/**
 * Opens the Mark Paid modal for one or more registration ids. All ids
 * must belong to the same student — one payment_batch (one invoice)
 * covers one student's subjects, never a mix. Caller is responsible for
 * that invariant (see index.js's bulk-selection guard).
 */
export function openPayModal(dom, ids) {
    const rows = ids.map((id) => getRenderedRow(id)).filter(Boolean);
    if (rows.length === 0) return;

    state.payingIds = ids;
    state.payingStudentId = rows[0].student?.id ?? null;

    const studentName = escapeHtml(rows[0].student?.name || rows[0].student?.code || '—');
    const subjectList = rows.map((r) => escapeHtml(r.subject?.name || r.subject?.code || '—')).join(', ');

    if (dom.payContext) {
        dom.payContext.innerHTML = `<strong>${studentName}</strong><br><span class="text-xs text-neutral-500 dark:text-neutral-400">${rows.length} subject(s): ${subjectList}</span>`;
    }
    if (dom.payRemark) dom.payRemark.value = '';
    clearPayFile(dom);

    window.RetakePayModal.toggle(true);
}

/**
 * Stages an image (from file picker, drag-drop, or clipboard paste) for
 * upload — compressed first (see image-compress.js) so what's staged for
 * preview is exactly what gets uploaded, not the raw multi-MB original.
 */
export async function setPayFile(dom, file) {
    if (!file || !file.type.startsWith('image/')) return;

    const compressed = await compressImage(file);
    state.payingFile = compressed;

    const url = URL.createObjectURL(compressed);
    if (dom.payPreview) {
        dom.payPreview.src = url;
        dom.payPreview.classList.remove('hidden');
    }
    if (dom.payFileName) {
        const kb = Math.round(compressed.size / 1024);
        dom.payFileName.textContent = `${compressed.name || 'pasted-image'} (${kb} KB)`;
    }
    if (dom.payClearBtn) dom.payClearBtn.classList.remove('hidden');
}

export function clearPayFile(dom) {
    state.payingFile = null;
    if (dom.payFileInput) dom.payFileInput.value = '';
    if (dom.payPreview) {
        dom.payPreview.src = '';
        dom.payPreview.classList.add('hidden');
    }
    if (dom.payFileName) dom.payFileName.textContent = '';
    if (dom.payClearBtn) dom.payClearBtn.classList.add('hidden');
}

/**
 * Submits the Mark Paid modal — creates a new PaymentBatch for the
 * student (multipart, since it may carry an image), then marks every id
 * in state.payingIds paid against it in one bulk-mark-paid call (works
 * the same whether it's 1 id or several).
 */
export async function submitPayForm(dom, ApiService, onDone) {
    if (!state.payingStudentId || state.payingIds.length === 0) return;

    const body = new FormData();
    body.append('student_id', state.payingStudentId);
    if (state.payingFile) body.append('invoice_file', state.payingFile);
    if (dom.payRemark?.value) body.append('remark', dom.payRemark.value);

    const { error: batchError, data: batchData } = await ApiService.request(CONFIG.PAYMENT_BATCHES_API, {
        method: 'POST',
        body,
    });

    if (batchError) {
        const firstError = batchData?.errors ? Object.values(batchData.errors)[0]?.[0] : null;
        Toast.fire({ icon: 'error', title: firstError || batchData?.message || 'មិនអាចបង្កើតវិក័យបត្របានទេ' });
        return;
    }

    const paymentBatchId = batchData?.data?.id;

    const { error, data } = await ApiService.request(`${CONFIG.REGISTRATIONS_API}/bulk-mark-paid`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            payment_batch_id: paymentBatchId,
            ids: state.payingIds,
        }),
    });

    if (error) {
        Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចកត់ត្រាការបង់ប្រាក់បានទេ' });
        return;
    }

    Toast.fire({ icon: 'success', title: data?.message || 'កត់ត្រាការបង់ប្រាក់ជោគជ័យ!' });
    window.RetakePayModal.toggle(false);
    state.payingIds = [];
    state.payingStudentId = null;
    state.selectedIds.clear();
    clearPayFile(dom);
    onDone();
}

export async function handleInviteTelegram(ApiService, id, onDone) {
    const { error, data } = await ApiService.request(`${CONFIG.REGISTRATIONS_API}/${id}/invite-telegram`, {
        method: 'PATCH',
    });

    if (error) {
        Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចអញ្ជើញបានទេ' });
        return;
    }
    Toast.fire({ icon: 'success', title: 'អញ្ជើញជោគជ័យ!' });
    onDone();
}
