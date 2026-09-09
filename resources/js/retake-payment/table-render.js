import { CONFIG } from './config.js';
import { state } from './core.js';

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

const PAYMENT_BADGE = {
    paid: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
    unpaid: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
};

function badge(text, classes) {
    return `<span class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold rounded-full ${classes}">${text}</span>`;
}

// Last-rendered rows, keyed by id — the Mark Paid modal needs the full
// record (student, subject) for its confirmation context.
let lastRows = new Map();

export function getRenderedRow(id) {
    return lastRows.get(String(id));
}

export function renderTable(dom, rows) {
    if (!dom.tableBody) return;

    lastRows = new Map((rows ?? []).map((row) => [String(row.id), row]));

    if (!rows || rows.length === 0) {
        dom.tableBody.innerHTML = `<tr><td colspan="10" class="text-center py-10 text-neutral-500">
            រកមិនឃើញទិន្នន័យទេ (No confirmed registrations found for the current filters).
        </td></tr>`;
        return;
    }

    dom.tableBody.className =
        'grid grid-cols-1 gap-3 p-4 md:p-0 md:table-row-group md:gap-0 md:divide-y md:divide-neutral-200 md:dark:divide-white/5';

    dom.tableBody.innerHTML = rows.map((row, index) => renderRow(row, index)).join('');
}

function renderRow(row, index) {
    const student = row.student ?? {};
    const term = row.term ?? {};
    const examType = row.exam_type ?? {};

    const studentName = escapeHtml(student.name || student.name_en || 'N/A');
    const studentCode = escapeHtml(student.code ?? '');
    const subjectName = escapeHtml(row.subject || 'N/A');
    const termTitle = escapeHtml(term.title ?? '—');
    const examTypeName = escapeHtml(examType.name_en || examType.code || '—');

    const registeredAt = row.registered_at
        ? new Date(row.registered_at).toLocaleDateString(CONFIG.LOCALE, { day: '2-digit', month: 'short', year: 'numeric' })
        : '—';

    const isPaid = row.payment_status === 'paid';
    const paymentBadge = badge(
        isPaid ? 'បង់ (Paid)' : 'មិនទាន់ (Unpaid)',
        PAYMENT_BADGE[row.payment_status] ?? PAYMENT_BADGE.unpaid
    );
    const telegramBadge = row.telegram_invited_at
        ? badge('✓ បានអញ្ជើញ', 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400')
        : (isPaid ? badge('មិនទាន់', 'bg-neutral-100 text-neutral-400 dark:bg-white/5 dark:text-neutral-500') : '<span class="text-neutral-300 text-xs">—</span>');

    const checkbox = !isPaid
        ? `<input type="checkbox" class="row-select w-4 h-4 rounded border-neutral-300 dark:border-white/20 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer" data-id="${row.id}" data-student-id="${student.id}" ${state.selectedIds.has(String(row.id)) ? 'checked' : ''}>`
        : '';

    const actions = renderActions(row, isPaid);

    return `
        <tr class="block md:table-row bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm md:shadow-none md:border-0 md:border-b md:rounded-none overflow-hidden md:overflow-visible">
            <td class="hidden md:table-cell px-6 py-4">${checkbox}</td>
            <td class="hidden md:table-cell px-6 py-4 text-neutral-400 font-mono text-xs">${index + 1}</td>

            <!-- MOBILE CARD -->
            <td class="block md:hidden p-0">
                <div class="flex items-center gap-3 p-4 border-b border-neutral-100 dark:border-white/5">
                    ${checkbox}
                    <div class="min-w-0">
                        <div class="font-bold text-neutral-900 dark:text-neutral-100 text-[15px] leading-tight">${studentName}</div>
                        <div class="text-xs text-neutral-400">${studentCode} · ${subjectName}</div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 px-4 py-3">${paymentBadge}${telegramBadge}</div>
                <div class="grid grid-cols-2 gap-x-3 gap-y-2.5 px-4 pb-3 text-xs">
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Term</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">${termTitle}</span></div>
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Exam Type</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">${examTypeName}</span></div>
                </div>
                <div class="flex flex-wrap items-center gap-2 px-4 pb-4">${actions}</div>
            </td>

            <!-- DESKTOP ROW -->
            <td class="hidden md:table-cell px-6 py-4">
                <div class="flex flex-col gap-0.5">
                    <span class="font-bold text-neutral-900 dark:text-neutral-100 text-sm">${studentName}</span>
                    <span class="text-xs text-neutral-400">${studentCode}</span>
                </div>
            </td>
            <td class="hidden md:table-cell px-6 py-4 text-sm">${termTitle}</td>
            <td class="hidden md:table-cell px-6 py-4 text-sm">${examTypeName}</td>
            <td class="hidden md:table-cell px-6 py-4 text-sm">${subjectName}</td>
            <td class="hidden md:table-cell px-6 py-4 text-xs font-mono text-neutral-500">${registeredAt}</td>
            <td class="hidden md:table-cell px-6 py-4">${paymentBadge}</td>
            <td class="hidden md:table-cell px-6 py-4">${telegramBadge}</td>
            <td class="hidden md:table-cell p-6 text-right">
                <div class="flex justify-end flex-wrap gap-1.5">${actions}</div>
            </td>
        </tr>`;
}

function renderActions(row, isPaid) {
    if (!isPaid) {
        return `<button data-action="mark-paid" data-id="${row.id}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition-colors">
            <svg class="w-3.5 h-3.5 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" /></svg>
            បង់ (Mark Paid)
        </button>`;
    }

    if (!row.telegram_invited_at) {
        return `<button data-action="invite-telegram" data-id="${row.id}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-500/10 rounded-lg hover:bg-sky-100 dark:hover:bg-sky-500/20 transition-colors">
            <svg class="w-3.5 h-3.5 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
            អញ្ជើញ Telegram
        </button>`;
    }

    return '<span class="text-neutral-300 text-xs">—</span>';
}
