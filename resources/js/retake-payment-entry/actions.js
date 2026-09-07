import { CONFIG } from './config.js';
import { state, Toast } from './core.js';
import { getRenderedBatch } from './table-render.js';

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

export function openEntryModal(dom, batchId) {
    const batch = getRenderedBatch(batchId);
    if (!batch) return;

    state.enteringBatchId = batchId;

    const student = batch.student ?? {};
    if (dom.entryContext) {
        const proof = batch.invoice_url
            ? `<a href="${batch.invoice_url}" target="_blank" rel="noopener" class="inline-block mt-2">
                 <img src="${batch.invoice_url}" alt="Payment proof" class="max-h-32 rounded-lg border border-neutral-200 dark:border-white/10">
               </a>`
            : '<span class="block mt-1 text-xs text-neutral-400">No proof image uploaded</span>';
        dom.entryContext.innerHTML = `<strong>${escapeHtml(student.name || student.code || '—')}</strong>${proof}`;
    }

    // At most one entry per batch (unique constraint) — pre-fill it if one
    // already exists, so re-opening this modal corrects it rather than
    // looking like a fresh, empty "Add".
    const existing = Array.isArray(batch.entries) ? batch.entries[0] : null;
    if (dom.entryNumberInput) dom.entryNumberInput.value = existing?.payment_number ?? '';
    if (dom.entryNoteInput) dom.entryNoteInput.value = existing?.payment_note ?? '';
    if (dom.entryModalTitle) {
        dom.entryModalTitle.textContent = existing
            ? 'កែសម្រួលធាតុផ្គូផ្គង (Edit Reconciliation Entry)'
            : 'បញ្ចូលធាតុផ្គូផ្គង (Add Reconciliation Entry)';
    }

    window.RetakeEntryModal.toggle(true);
}

export async function submitEntryForm(dom, ApiService, onDone) {
    if (!state.enteringBatchId) return;

    const { error, data } = await ApiService.request(CONFIG.PAYMENT_ENTRIES_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            payment_batch_id: state.enteringBatchId,
            payment_number: dom.entryNumberInput?.value || null,
            payment_note: dom.entryNoteInput?.value || null,
        }),
    });

    if (error) {
        const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
        Toast.fire({ icon: 'error', title: firstError || data?.message || 'មិនអាចរក្សាទុកបានទេ' });
        return;
    }

    Toast.fire({ icon: 'success', title: 'រក្សាទុកជោគជ័យ!' });
    window.RetakeEntryModal.toggle(false);
    state.enteringBatchId = null;
    onDone();
}
