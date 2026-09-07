function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

let lastBatches = new Map();

export function getRenderedBatch(id) {
    return lastBatches.get(String(id));
}

export function renderTable(dom, batches) {
    if (!dom.tableBody) return;

    lastBatches = new Map((batches ?? []).map((b) => [String(b.id), b]));

    if (!batches || batches.length === 0) {
        dom.tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-neutral-500">
            រកមិនឃើញវិក័យបត្រទេ (No payment batches found).
        </td></tr>`;
        return;
    }

    dom.tableBody.className =
        'grid grid-cols-1 gap-3 p-4 md:p-0 md:table-row-group md:gap-0 md:divide-y md:divide-neutral-200 md:dark:divide-white/5';

    dom.tableBody.innerHTML = batches.map((batch, index) => renderRow(batch, index)).join('');
}

function renderRow(batch, index) {
    const student = batch.student ?? {};
    const studentName = escapeHtml(student.name || student.code || 'N/A');
    const studentCode = escapeHtml(student.code ?? '');
    const proof = batch.invoice_url
        ? `<a href="${batch.invoice_url}" target="_blank" rel="noopener" class="inline-block group">
             <img src="${batch.invoice_url}" alt="Payment proof" class="w-12 h-12 object-cover rounded-lg border border-neutral-200 dark:border-white/10 group-hover:opacity-80 transition-opacity">
           </a>`
        : '<span class="text-neutral-300 text-xs">No image</span>';
    const paidAt = batch.paid_at
        ? new Date(batch.paid_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
        : '—';
    // One payment_batch = one invoice = at most one reconciliation entry
    // (enforced by a unique DB constraint — see PaymentEntryController::store()).
    const isReconciled = Array.isArray(batch.entries) && batch.entries.length > 0;
    const entriesBadge = isReconciled
        ? `<span class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">✓ បានផ្គូផ្គង (Reconciled)</span>`
        : `<span class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold rounded-full bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">មិនទាន់ (Not reconciled)</span>`;

    const addEntryBtn = isReconciled
        ? `<button data-action="add-entry" data-id="${batch.id}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-neutral-700 dark:text-neutral-300 bg-neutral-100 dark:bg-white/5 rounded-lg hover:bg-neutral-200 dark:hover:bg-white/10 transition-colors">
            <svg class="w-3.5 h-3.5 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            កែសម្រួល (Edit Entry)
        </button>`
        : `<button data-action="add-entry" data-id="${batch.id}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-500/20 transition-colors">
            <svg class="w-3.5 h-3.5 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" /></svg>
            បញ្ចូលធាតុ (Add Entry)
        </button>`;

    return `
        <tr class="block md:table-row bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm md:shadow-none md:border-0 md:border-b md:rounded-none overflow-hidden md:overflow-visible">
            <td class="hidden md:table-cell px-6 py-4 text-neutral-400 font-mono text-xs">${index + 1}</td>

            <!-- MOBILE CARD -->
            <td class="block md:hidden p-0">
                <div class="flex items-center gap-3 p-4 border-b border-neutral-100 dark:border-white/5">
                    ${proof}
                    <div>
                        <div class="font-bold text-neutral-900 dark:text-neutral-100 text-[15px] leading-tight">${studentName}</div>
                        <div class="text-xs text-neutral-400">${studentCode}</div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 px-4 py-3">${entriesBadge}</div>
                <div class="grid grid-cols-2 gap-x-3 gap-y-2.5 px-4 pb-4 text-xs">
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Paid At</span><span class="font-mono text-neutral-600 dark:text-neutral-400">${paidAt}</span></div>
                </div>
                <div class="px-4 pb-4">${addEntryBtn}</div>
            </td>

            <!-- DESKTOP ROW -->
            <td class="hidden md:table-cell px-6 py-4">
                <div class="flex flex-col gap-0.5">
                    <span class="font-bold text-neutral-900 dark:text-neutral-100 text-sm">${studentName}</span>
                    <span class="text-xs text-neutral-400">${studentCode}</span>
                </div>
            </td>
            <td class="hidden md:table-cell px-6 py-4">${proof}</td>
            <td class="hidden md:table-cell px-6 py-4 text-xs font-mono text-neutral-500">${paidAt}</td>
            <td class="hidden md:table-cell px-6 py-4">${entriesBadge}</td>
            <td class="hidden md:table-cell p-6 text-right">${addEntryBtn}</td>
        </tr>`;
}
