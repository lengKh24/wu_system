function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

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

// Last-rendered rows, keyed by id — lets the Score action open the modal
// with the full record without re-fetching or serializing names into
// data-* attributes.
let lastRows = new Map();

export function getRenderedRow(id) {
    return lastRows.get(String(id));
}

/** Renders the retake registrations list into dom.tableBody, Score's columns only. */
export function renderTable(dom, permissions, rows) {
    if (!dom.tableBody) return;

    lastRows = new Map((rows ?? []).map((row) => [String(row.id), row]));

    if (!rows || rows.length === 0) {
        dom.tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-10 text-neutral-500">
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
    const subject = row.subject ?? {};
    const lecturer = row.lecturer ?? {};
    const term = row.term ?? {};
    const examType = row.exam_type ?? {};

    const studentName = escapeHtml(student.name || student.name_en || 'N/A');
    const studentCode = escapeHtml(student.code ?? '');
    const subjectName = escapeHtml(subject.name || subject.name_en || subject.code || 'N/A');
    const lecturerName = escapeHtml(lecturer.name || lecturer.name_en || '—');
    const termTitle = escapeHtml(term.title ?? '—');
    const examTypeName = escapeHtml(examType.name_en || examType.code || '—');

    const outcomeBadge = badge(OUTCOME_LABEL[row.outcome] ?? row.outcome ?? '—', OUTCOME_BADGE[row.outcome] ?? OUTCOME_BADGE.pending);
    const score = row.score !== null && row.score !== undefined
        ? `<span class="font-mono font-bold">${escapeHtml(row.score)}</span>`
        : '<span class="text-neutral-400">—</span>';

    const scoreBtn = permissions.canScore
        ? `<button data-action="score" data-id="${row.id}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-500/10 rounded-lg hover:bg-violet-100 dark:hover:bg-violet-500/20 transition-colors" title="Enter score">
            <svg class="w-3.5 h-3.5 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            ពិន្ទុ (Score)
        </button>`
        : '<span class="text-neutral-300 text-xs">—</span>';

    return `
        <tr class="block md:table-row bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm md:shadow-none md:border-0 md:border-b md:rounded-none overflow-hidden md:overflow-visible">
            <td class="hidden md:table-cell px-6 py-4 text-neutral-400 font-mono text-xs">${index + 1}</td>

            <!-- MOBILE CARD -->
            <td class="block md:hidden p-0">
                <div class="p-4 border-b border-neutral-100 dark:border-white/5">
                    <div class="font-bold text-neutral-900 dark:text-neutral-100 text-[15px] leading-tight">${studentName}</div>
                    <div class="text-xs text-neutral-400">${studentCode} · ${subjectName}</div>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 px-4 py-3">${outcomeBadge}${score}</div>
                <div class="grid grid-cols-2 gap-x-3 gap-y-2.5 px-4 pb-3 text-xs">
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Term</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">${termTitle}</span></div>
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Exam Type</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">${examTypeName}</span></div>
                    <div><span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wide block">Lecturer</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">${lecturerName}</span></div>
                </div>
                <div class="px-4 pb-4">${scoreBtn}</div>
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
            <td class="hidden md:table-cell px-6 py-4 text-sm">${lecturerName}</td>
            <td class="hidden md:table-cell px-6 py-4">${outcomeBadge}</td>
            <td class="hidden md:table-cell px-6 py-4">${score}</td>
            <td class="hidden md:table-cell p-6 text-right">${scoreBtn}</td>
        </tr>`;
}
