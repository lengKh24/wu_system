/**
 * REG's retake exam report page (charts + KPIs). Pulls from
 * /api/v1/retake-registrations-report (see
 * RetakeRegistrationController::report()) and the term list for the
 * filter dropdown. Chart.js is loaded globally via CDN in the Blade view —
 * same pattern as resources/js/state-exam/report.js.
 */

const REPORT_URL = window.RETAKE_REPORT_URL;
const TERMS_URL = window.RETAKE_TERMS_URL;

const els = {
    termFilter: document.getElementById('reportTermFilter'),
    kpiTotal: document.getElementById('kpiTotalConfirmed'),
    kpiPassed: document.getElementById('kpiPassed'),
    kpiStillNeedRetake: document.getElementById('kpiStillNeedRetake'),
    kpiPaymentRate: document.getElementById('kpiPaymentRate'),
    tableBody: document.getElementById('examTypeTableBody'),
    refreshBtn: document.getElementById('refreshReportBtn'),
    refreshIcon: document.getElementById('refreshReportIcon'),
};

let outcomeBarChart = null;
let paymentDoughnutChart = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

async function fetchJson(url) {
    const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (!res.ok) throw new Error(`Request failed: ${res.status}`);
    return res.json();
}

function renderKpis(report) {
    const outcomes = report.outcome_counts ?? {};
    const payments = report.payment_counts ?? {};

    const total = report.total_confirmed ?? 0;
    const passed = outcomes.passed ?? 0;
    const stillNeedRetake = (outcomes.failed ?? 0) + (outcomes.absent ?? 0);
    const paid = payments.paid ?? 0;
    const paymentRate = total > 0 ? Math.round((paid / total) * 100) : 0;

    if (els.kpiTotal) els.kpiTotal.textContent = total;
    if (els.kpiPassed) els.kpiPassed.textContent = passed;
    if (els.kpiStillNeedRetake) els.kpiStillNeedRetake.textContent = stillNeedRetake;
    if (els.kpiPaymentRate) els.kpiPaymentRate.textContent = `${paymentRate}%`;

    return { paid, unpaid: payments.unpaid ?? 0 };
}

function renderTable(byExamType) {
    if (!els.tableBody) return;

    if (!byExamType || byExamType.length === 0) {
        els.tableBody.innerHTML = '<tr><td colspan="9" class="px-6 py-10 text-center text-neutral-400">No confirmed registrations yet.</td></tr>';
        return;
    }

    els.tableBody.innerHTML = byExamType.map((row) => {
        const total = Number(row.total) || 0;
        const passed = Number(row.passed) || 0;
        const rate = total > 0 ? Math.round((passed / total) * 100) : 0;

        return `
        <tr>
            <td class="px-6 py-4 font-bold text-neutral-900 dark:text-white">${escapeHtml(row.exam_type_name || row.exam_type_code)}</td>
            <td class="px-6 py-4 font-mono">${total}</td>
            <td class="px-6 py-4 font-mono text-emerald-600 dark:text-emerald-400 font-bold">${row.passed ?? 0}</td>
            <td class="px-6 py-4 font-mono text-rose-600 dark:text-rose-400 font-bold">${row.failed ?? 0}</td>
            <td class="px-6 py-4 font-mono text-orange-600 dark:text-orange-400 font-bold">${row.absent ?? 0}</td>
            <td class="px-6 py-4 font-mono text-neutral-500">${row.pending ?? 0}</td>
            <td class="px-6 py-4 font-mono text-emerald-600 dark:text-emerald-400">${row.paid ?? 0}</td>
            <td class="px-6 py-4 font-mono text-amber-600 dark:text-amber-400">${row.unpaid ?? 0}</td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-2 rounded-full bg-neutral-100 dark:bg-white/10 overflow-hidden max-w-[120px]">
                        <div class="h-full rounded-full bg-emerald-500" style="width:${rate}%"></div>
                    </div>
                    <span class="text-xs font-bold text-neutral-600 dark:text-neutral-300">${rate}%</span>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function renderOutcomeBarChart(byExamType) {
    const canvas = document.getElementById('outcomeBarChart');
    if (!canvas) return;

    const labels = byExamType.map((r) => r.exam_type_name || r.exam_type_code);

    outcomeBarChart?.destroy();
    outcomeBarChart = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Passed', data: byExamType.map((r) => Number(r.passed) || 0), backgroundColor: '#10b981', borderRadius: 6 },
                { label: 'Failed', data: byExamType.map((r) => Number(r.failed) || 0), backgroundColor: '#f43f5e', borderRadius: 6 },
                { label: 'Absent', data: byExamType.map((r) => Number(r.absent) || 0), backgroundColor: '#f97316', borderRadius: 6 },
                { label: 'Pending', data: byExamType.map((r) => Number(r.pending) || 0), backgroundColor: '#a3a3a3', borderRadius: 6 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: '#737373', usePointStyle: true } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#737373' } },
                y: { beginAtZero: true, grid: { color: 'rgba(115,115,115,0.1)' }, ticks: { color: '#737373', precision: 0 } },
            },
        },
    });
}

function renderPaymentDoughnut(paid, unpaid) {
    const canvas = document.getElementById('paymentDoughnutChart');
    if (!canvas) return;

    paymentDoughnutChart?.destroy();
    paymentDoughnutChart = new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Unpaid'],
            datasets: [{ data: [paid, unpaid], backgroundColor: ['#10b981', '#f59e0b'], borderWidth: 0 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: { legend: { position: 'bottom', labels: { color: '#737373', usePointStyle: true } } },
        },
    });
}

async function loadTerms() {
    if (!els.termFilter) return;
    try {
        const json = await fetchJson(`${TERMS_URL}?per_page=100&sort=-start_date`);
        const terms = Array.isArray(json?.data) ? json.data : [];
        els.termFilter.innerHTML = '<option value="">គ្រប់រយៈពេល (All terms)</option>' +
            terms.map((t) => `<option value="${t.id}">${escapeHtml(t.title)}</option>`).join('');
    } catch (err) {
        console.error('[retake report] failed to load terms:', err);
    }
}

async function loadReport() {
    els.refreshIcon?.classList.add('animate-spin');
    try {
        const params = new URLSearchParams();
        if (els.termFilter?.value) params.set('retake_term_id', els.termFilter.value);

        const json = await fetchJson(`${REPORT_URL}?${params.toString()}`);
        const report = json?.data ?? {};
        const byExamType = report.by_exam_type ?? [];

        const { paid, unpaid } = renderKpis(report);
        renderTable(byExamType);
        renderOutcomeBarChart(byExamType);
        renderPaymentDoughnut(paid, unpaid);
    } catch (err) {
        console.error('[retake report] failed to load:', err);
    } finally {
        els.refreshIcon?.classList.remove('animate-spin');
    }
}

els.refreshBtn?.addEventListener('click', loadReport);
els.termFilter?.addEventListener('change', loadReport);

loadTerms().then(loadReport);
