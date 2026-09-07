/**
 * Public student self-service retake-exam page — no auth, no login.
 * Identity is the student code alone (Leng's call, 2026-09-08) — every
 * request re-sends it rather than trusting a stored token.
 *
 * Fully self-contained, same as state-exam's public pages: its own toast,
 * no SweetAlert/admin-module dependency.
 */

import { baseUri } from "../app";

const API_BASE = baseUri('retake-exam');

const els = {
    code: document.getElementById('retakeCode'),
    lookupBtn: document.getElementById('retakeLookupBtn'),
    lookupError: document.getElementById('retakeLookupError'),
    toastStack: document.getElementById('retakeToastStack'),

    result: document.getElementById('retakeResult'),
    studentInitial: document.getElementById('retakeStudentInitial'),
    studentName: document.getElementById('retakeStudentName'),
    studentCode: document.getElementById('retakeStudentCode'),

    pendingBatches: document.getElementById('retakePendingBatches'),
    confirmSection: document.getElementById('retakeConfirmSection'),
    saveBtn: document.getElementById('retakeSaveBtn'),
    confirmBtn: document.getElementById('retakeConfirmBtn'),

    confirmedSection: document.getElementById('retakeConfirmedSection'),
    confirmedList: document.getElementById('retakeConfirmedList'),
};

let currentCode = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

// ---------- Toast ----------

function showToast(type, message) {
    if (!els.toastStack) return;

    const palette = {
        success: { bg: 'bg-emerald-600', icon: 'M4.5 12.75l6 6 9-13.5' },
        error: { bg: 'bg-rose-600', icon: 'M6 18L18 6M6 6l12 12' },
    }[type] ?? { bg: 'bg-neutral-800', icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' };

    const toast = document.createElement('div');
    toast.className = `flex items-center gap-3 px-4 py-3.5 rounded-2xl shadow-2xl text-white text-sm font-bold ${palette.bg} translate-x-6 opacity-0 transition-all duration-300`;
    toast.innerHTML = `
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="${palette.icon}" />
        </svg>
        <span>${escapeHtml(message)}</span>`;

    els.toastStack.appendChild(toast);
    requestAnimationFrame(() => toast.classList.remove('translate-x-6', 'opacity-0'));

    setTimeout(() => {
        toast.classList.add('translate-x-6', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 2600);
}

// ---------- API ----------

function post(path, body) {
    return fetch(API_BASE + path, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(body),
    }).then((res) => res.json().then((json) => {
        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Something went wrong.');
        }
        return json.data;
    }));
}

function subjectLabel(row) {
    return (row.subject && (row.subject.name || row.subject.code)) ? (row.subject.name || row.subject.code) : ('Subject #' + row.id);
}

// ---------- Rendering ----------

function badge(text, classes) {
    return `<span class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold rounded-full ${classes}">${text}</span>`;
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

function renderStudent(data) {
    const s = data.student;
    const name = [s.first_name_kh, s.last_name_kh].filter(Boolean).join(' ')
        || [s.first_name, s.last_name].filter(Boolean).join(' ')
        || s.code;

    els.studentName.textContent = name;
    els.studentCode.textContent = s.code;
    els.studentInitial.textContent = (name || '?').trim().charAt(0).toUpperCase();
}

function renderPending(data) {
    const container = els.pendingBatches;
    container.innerHTML = '';

    if (!data.pending_batches || data.pending_batches.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 px-5 bg-white/60 dark:bg-neutral-900/50 border border-dashed border-neutral-200 dark:border-white/10 rounded-2xl">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">មិនមានការចុះឈ្មោះដែលកំពុងរង់ចាំទេ (No pending registration found for this student).</p>
            </div>`;
        els.confirmSection.classList.add('hidden');
        return;
    }

    data.pending_batches.forEach((batch) => {
        const rows = [...(batch.will_register || []), ...(batch.will_not_register || [])];

        const block = document.createElement('div');
        block.className = 'bg-white/80 dark:bg-neutral-900/70 backdrop-blur-sm border border-neutral-200/80 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden';
        block.innerHTML = `
            <div class="px-5 py-4 bg-gradient-to-r from-indigo-600/[0.06] to-transparent dark:from-indigo-500/10 border-b border-neutral-100 dark:border-white/5">
                <div class="font-bold text-neutral-900 dark:text-white text-sm">${escapeHtml(batch.term || '—')}</div>
                <div class="text-xs text-neutral-400">${escapeHtml(batch.exam_type || '—')}</div>
            </div>
            <div class="p-4 space-y-2">
                ${rows.map((row) => subjectRow(row)).join('')}
            </div>`;
        container.appendChild(block);
    });

    els.confirmSection.classList.remove('hidden');
}

function subjectRow(row) {
    const checked = !!row.is_selected;
    return `
        <label data-subject-row
            class="flex items-center gap-3 px-4 py-3.5 rounded-xl border cursor-pointer transition-all ${checked
                ? 'border-indigo-300 dark:border-indigo-500/40 bg-indigo-50 dark:bg-indigo-500/10'
                : 'border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-white/5'}">
            <input type="checkbox" class="w-4 h-4 rounded border-neutral-300 dark:border-white/20 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer"
                data-registration-id="${row.id}" ${checked ? 'checked' : ''}>
            <span class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">${escapeHtml(subjectLabel(row))}</span>
        </label>`;
}

function renderConfirmed(data) {
    const rows = data.confirmed_registrations || [];

    if (rows.length === 0) {
        els.confirmedSection.classList.add('hidden');
        return;
    }

    els.confirmedList.innerHTML = rows.map((row) => {
        const paymentBadge = badge(
            row.payment_status === 'paid' ? 'បង់ (Paid)' : 'មិនទាន់ (Unpaid)',
            PAYMENT_BADGE[row.payment_status] ?? PAYMENT_BADGE.unpaid
        );
        const outcomeBadge = badge(OUTCOME_LABEL[row.outcome] ?? row.outcome ?? '—', OUTCOME_BADGE[row.outcome] ?? OUTCOME_BADGE.pending);
        const examTypeName = (row.exam_type && row.exam_type.name) || '—';
        const termTitle = (row.term && row.term.title) || '—';

        return `
            <div class="flex items-center justify-between gap-3 px-4 py-3.5 bg-white/80 dark:bg-neutral-900/70 backdrop-blur-sm border border-neutral-200/80 dark:border-white/10 rounded-xl">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-neutral-900 dark:text-white truncate">${escapeHtml(subjectLabel(row))}</div>
                    <div class="text-xs text-neutral-400">${escapeHtml(termTitle)} · ${escapeHtml(examTypeName)}</div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">${paymentBadge}${outcomeBadge}</div>
            </div>`;
    }).join('');

    els.confirmedSection.classList.remove('hidden');
}

function renderAll(data) {
    els.result.classList.remove('hidden');
    renderStudent(data);
    renderPending(data);
    renderConfirmed(data);
}

// ---------- Events ----------

// Delegated so the row's border/background flips the instant the
// checkbox is toggled, without waiting on a full re-render.
els.pendingBatches.addEventListener('change', (e) => {
    const checkbox = e.target.closest('input[data-registration-id]');
    if (!checkbox) return;

    const row = checkbox.closest('[data-subject-row]');
    if (!row) return;

    row.classList.toggle('border-indigo-300', checkbox.checked);
    row.classList.toggle('dark:border-indigo-500/40', checkbox.checked);
    row.classList.toggle('bg-indigo-50', checkbox.checked);
    row.classList.toggle('dark:bg-indigo-500/10', checkbox.checked);
    row.classList.toggle('border-neutral-200', !checkbox.checked);
    row.classList.toggle('dark:border-white/10', !checkbox.checked);
    row.classList.toggle('bg-white', !checkbox.checked);
    row.classList.toggle('dark:bg-neutral-900', !checkbox.checked);
});

els.lookupBtn.addEventListener('click', () => {
    els.lookupError.classList.add('hidden');
    currentCode = els.code.value.trim();

    if (!currentCode) {
        els.lookupError.textContent = 'សូមបញ្ចូលកូដនិស្សិត (Please enter your student code).';
        els.lookupError.classList.remove('hidden');
        return;
    }

    post('/lookup', { code: currentCode })
        .then(renderAll)
        .catch((err) => {
            els.lookupError.textContent = err.message;
            els.lookupError.classList.remove('hidden');
        });
});

els.code.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') els.lookupBtn.click();
});

els.saveBtn.addEventListener('click', () => {
    const selections = Array.from(els.pendingBatches.querySelectorAll('input[data-registration-id]')).map((el) => ({
        id: parseInt(el.dataset.registrationId, 10),
        is_selected: el.checked,
    }));

    post('/select', { code: currentCode, selections })
        .then((data) => {
            showToast('success', 'រក្សាទុកជម្រើសបានជោគជ័យ! (Selections saved)');
            renderAll(data);
        })
        .catch((err) => showToast('error', err.message));
});

els.confirmBtn.addEventListener('click', () => {
    if (!window.confirm('បញ្ជាក់ការចុះឈ្មោះជាមួយមុខវិជ្ជាដែលបានធីកខាងលើ? សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ។\n\nConfirm registration with the subjects currently checked? This cannot be undone here.')) {
        return;
    }

    post('/confirm', { code: currentCode })
        .then((data) => {
            showToast('success', 'បញ្ជាក់ការចុះឈ្មោះជោគជ័យ! សូមទៅកាន់ការិយាល័យកិច្ចការនិស្សិត (Confirmed — please proceed to Student Affairs for payment)');
            renderAll(data);
        })
        .catch((err) => showToast('error', err.message));
});
