import { CONFIG } from './config.js';

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

const PAYMENT_BADGE = {
    paid: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
    unpaid: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
};

const OUTCOME_BADGE = {
    pending: 'bg-neutral-100 text-neutral-500 dark:bg-white/5 dark:text-neutral-400',
    passed: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
    failed: 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400',
    absent: 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400',
};

const OUTCOME_LABEL = {
    pending: 'រង់ចាំ (Pending)',
    passed: 'ជាប់ (Passed)',
    failed: 'ធ្លាក់ (Failed)',
    absent: 'អវត្តមាន (Absent)',
};

function badge(text, classes) {
    return `<span class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold rounded-full ${classes}">${text}</span>`;
}

export function renderTable(dom, rows) {
    if (!dom.tableBody) return;

    if (!rows || rows.length === 0) {
        dom.tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-neutral-500">
            រកមិនឃើញទិន្នន័យទេ (No registered students found).
        </td></tr>`;
        return;
    }

    dom.tableBody.className =
        'grid grid-cols-1 gap-3 p-4 md:p-0 md:table-row-group md:gap-0 md:divide-y md:divide-neutral-200 md:dark:divide-white/5';

    dom.tableBody.innerHTML = rows.map((row, index) => renderRow(row, index)).join('');
}

function renderRow(row, index) {
    const student = row.student ?? {};
    const subject = row.subject ?? {};
    const term = row.term ?? {};
    const examType = row.exam_type ?? {};

    const studentName = escapeHtml(student.name || student.name_en || 'N/A');
    const studentCode = escapeHtml(student.code ?? '');
    const subjectName = escapeHtml(subject.name || subject.name_en || subject.code || 'N/A');
    const termTitle = escapeHtml(term.title ?? '—');
    const examTypeName = escapeHtml(examType.name_en || examType.code || '—');

    const registeredAt = row.registered_at
        ? new Date(row.registered_at).toLocaleDateString(CONFIG.LOCALE, { day: '2-digit', month: 'short', year: 'numeric' })
        : '—';

    const paymentBadge = badge(
        row.payment_status === 'paid' ? 'បង់ (Paid)' : 'មិនទាន់ (Unpaid)',
        PAYMENT_BADGE[row.payment_status] ?? PAYMENT_BADGE.unpaid
    );
    const outcomeBadge = badge(OUTCOME_LABEL[row.outcome] ?? row.outcome ?? '—', OUTCOME_BADGE[row.outcome] ?? OUTCOME_BADGE.pending);
    const telegramBadge = row.telegram_invited_at
        ? badge('✓ បានអញ្ជើញ', 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400')
        : badge('មិនទាន់', 'bg-neutral-100 text-neutral-400 dark:bg-white/5 dark:text-neutral-500');

    return `
        <tr class="block md:table-row bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm md:shadow-none md:border-0 md:border-b md:rounded-none overflow-hidden md:overflow-visible">
            <td class="hidden md:table-cell px-6 py-4 text-neutral-400 font-mono text-xs">${index + 1}</td>

            <!-- MOBILE CARD -->
            <td class="block md:hidden p-0">
                <div class="p-4 border-b border-neutral-100 dark:border-white/5">
                    <div class="font-bold text-neutral-900 dark:text-neutral-100 text-[15px] leading-tight">${studentName}</div>
                    <div class="text-xs text-neutral-400">${studentCode} · ${subjectName}</div>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 px-4 py-3">${paymentBadge}${outcomeBadge}${telegramBadge}</div>
                <div class="grid grid-cols-2 gap-x-3 gap-y-2.5 px-4 pb-4 text-xs">
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Term</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">${termTitle}</span></div>
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Exam Type</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">${examTypeName}</span></div>
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Registered At</span><span class="font-mono text-neutral-600 dark:text-neutral-400">${registeredAt}</span></div>
                </div>
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
            <td class="hidden md:table-cell px-6 py-4">${outcomeBadge}</td>
            <td class="hidden md:table-cell px-6 py-4">${telegramBadge}</td>
        </tr>`;
}
