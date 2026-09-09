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

/** Renders the retake registrations list into dom.tableBody. */
export function renderTable(dom, permissions, rows) {
    if (!dom.tableBody) return;

    if (!rows || rows.length === 0) {
        dom.tableBody.innerHTML = `<tr><td colspan="11" class="text-center py-10 text-neutral-500">
            រកមិនឃើញទិន្នន័យទេ (No registrations found for the current filters).
        </td></tr>`;
        return;
    }

    dom.tableBody.className =
        'grid grid-cols-1 gap-3 p-4 md:p-0 md:table-row-group md:gap-0 md:divide-y md:divide-neutral-200 md:dark:divide-white/5';

    dom.tableBody.innerHTML = rows.map((row, index) => renderRow(row, index, permissions)).join('');
}

function renderRow(row, index, permissions) {
    const student = row.student ?? {};
    const lecturer = row.lecturer ?? {};
    const term = row.term ?? {};
    const examType = row.exam_type ?? {};

    const studentName = escapeHtml(student.name || student.name_en || 'N/A');
    const studentCode = escapeHtml(student.code ?? '');
    const restudyBadge = student.is_restudy
        ? badge('RESTUDY', 'bg-fuchsia-50 text-fuchsia-700 dark:bg-fuchsia-500/10 dark:text-fuchsia-400')
        : '';
    const subjectName = escapeHtml(row.subject || 'N/A');
    const lecturerName = escapeHtml(lecturer.name || lecturer.name_en || '—');
    const termTitle = escapeHtml(term.title ?? '—');
    const examTypeName = escapeHtml(examType.name_en || examType.code || '—');
    const statusNote = escapeHtml(row.status_note ?? '');

    const registeredAt = row.registered_at
        ? new Date(row.registered_at).toLocaleDateString(CONFIG.LOCALE, { day: '2-digit', month: 'short', year: 'numeric' })
        : '<span class="text-neutral-400 italic">Not yet</span>';

    const paymentBadge = badge(
        row.payment_status === 'paid' ? 'បង់ (Paid)' : 'មិនទាន់ (Unpaid)',
        PAYMENT_BADGE[row.payment_status] ?? PAYMENT_BADGE.unpaid
    );
    const outcomeBadge = badge(OUTCOME_LABEL[row.outcome] ?? row.outcome ?? '—', OUTCOME_BADGE[row.outcome] ?? OUTCOME_BADGE.pending);
    const selectedBadge = row.is_selected
        ? badge('✓ ជ្រើសរើស', 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400')
        : badge('មិនជ្រើសរើស', 'bg-neutral-100 text-neutral-400 dark:bg-white/5 dark:text-neutral-500');

    const score = row.score !== null && row.score !== undefined
        ? `<span class="font-mono font-bold">${escapeHtml(row.score)}</span>`
        : '<span class="text-neutral-400">—</span>';

    const actions = renderActions(row, permissions);

    return `
        <tr class="block md:table-row bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm md:shadow-none md:border-0 md:border-b md:rounded-none overflow-hidden md:overflow-visible">
            <td class="hidden md:table-cell px-6 py-4 text-neutral-400 font-mono text-xs">${index + 1}</td>

            <!-- MOBILE CARD -->
            <td class="block md:hidden p-0">
                <div class="p-4 border-b border-neutral-100 dark:border-white/5">
                    <div class="flex items-center gap-1.5 font-bold text-neutral-900 dark:text-neutral-100 text-[15px] leading-tight">${studentName}${restudyBadge}</div>
                    <div class="text-xs text-neutral-400">${studentCode} · ${subjectName}</div>
                </div>
                <div class="flex flex-wrap gap-1.5 px-4 py-3">${paymentBadge}${outcomeBadge}${selectedBadge}</div>
                <div class="grid grid-cols-2 gap-x-3 gap-y-2.5 px-4 pb-3 text-xs">
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Term</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">${termTitle}</span></div>
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Exam Type</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">${examTypeName}</span></div>
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Status Note</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">${statusNote}</span></div>
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Score</span>${score}</div>
                </div>
                <div class="flex flex-wrap items-center gap-2 px-4 pb-4">${actions}</div>
            </td>

            <!-- DESKTOP ROW -->
            <td class="hidden md:table-cell px-6 py-4">
                <div class="flex flex-col gap-0.5">
                    <span class="flex items-center gap-1.5 font-bold text-neutral-900 dark:text-neutral-100 text-sm">${studentName}${restudyBadge}</span>
                    <span class="text-xs text-neutral-400">${studentCode}</span>
                </div>
            </td>
            <td class="hidden md:table-cell px-6 py-4 text-sm">${termTitle}</td>
            <td class="hidden md:table-cell px-6 py-4 text-sm">${examTypeName}</td>
            <td class="hidden md:table-cell px-6 py-4 text-sm">${subjectName}</td>
            <td class="hidden md:table-cell px-6 py-4 text-sm">${lecturerName}</td>
            <td class="hidden md:table-cell px-6 py-4 text-xs">${statusNote}</td>
            <td class="hidden md:table-cell px-6 py-4">${selectedBadge}</td>
            <td class="hidden md:table-cell px-6 py-4 text-xs font-mono text-neutral-500">${registeredAt}</td>
            <td class="hidden md:table-cell px-6 py-4">${paymentBadge}</td>
            <td class="hidden md:table-cell px-6 py-4">${outcomeBadge} <div class="mt-1">${score}</div></td>
            <td class="hidden md:table-cell p-6 text-right">
                <div class="flex justify-end flex-wrap gap-1.5">${actions}</div>
            </td>
        </tr>`;
}

function renderActions(row, permissions) {
    const buttons = [];

    if (row.deleted_at) {
        if (permissions.canEdit) {
            buttons.push(`<button data-action="restore" data-id="${row.id}" class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-xl transition-colors" title="Restore"><svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg></button>`);
        }
        return buttons.join('');
    }

    if (permissions.canEdit) {
        buttons.push(`<button data-action="outcome" data-id="${row.id}" data-outcome="${row.outcome ?? 'pending'}" class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 rounded-xl transition-colors" title="Set outcome">
            <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </button>`);
        buttons.push(`<button data-action="toggle-selection" data-id="${row.id}" data-selected="${row.is_selected ? '1' : '0'}" class="p-2 text-slate-600 hover:bg-slate-50 dark:hover:bg-white/5 rounded-xl transition-colors" title="Toggle selection">
            <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="3" stroke-linecap="round" stroke-linejoin="round" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 12l2.5 2.5L16 9" /></svg>
        </button>`);
    }

    if (permissions.canDelete) {
        buttons.push(`<button data-action="delete" data-id="${row.id}" class="p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-xl transition-colors" title="Delete">
            <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>`);
    }

    return buttons.join('') || '<span class="text-neutral-300 text-xs">—</span>';
}
