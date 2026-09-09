/**
 * Registrar dashboard (charts + KPIs + breakdown tables). Pulls from
 * /api/v1/dashboard/report (see Api\DashboardController::report()).
 * Chart.js is loaded globally via CDN in the Blade view — same pattern as
 * resources/js/retake-exam/report.js.
 */

const REPORT_URL = window.DASHBOARD_REPORT_URL;

const els = {
    kpiTotalStudents: document.getElementById('kpiTotalStudents'),
    kpiActiveStudents: document.getElementById('kpiActiveStudents'),
    kpiTotalBatches: document.getElementById('kpiTotalBatches'),
    kpiTotalMajors: document.getElementById('kpiTotalMajors'),
    kpiMale: document.getElementById('kpiMale'),
    kpiFemale: document.getElementById('kpiFemale'),
    statusTableBody: document.getElementById('statusTableBody'),
    batchTableBody: document.getElementById('batchTableBody'),
    majorTableBody: document.getElementById('majorTableBody'),
    matrixTableHead: document.getElementById('matrixTableHead'),
    matrixTableBody: document.getElementById('matrixTableBody'),
    refreshBtn: document.getElementById('refreshDashboardBtn'),
    refreshIcon: document.getElementById('refreshDashboardIcon'),
};

// A fixed, repeating palette (not random) so the same slice/bar keeps the
// same color across a refresh — random colors would make the chart look
// like it changed even when the underlying data didn't.
const PALETTE = [
    '#6366f1', '#10b981', '#f43f5e', '#f59e0b', '#0ea5e9', '#a855f7',
    '#f97316', '#14b8a6', '#ec4899', '#84cc16', '#64748b', '#eab308',
    '#8b5cf6', '#06b6d4', '#f472b6', '#22c55e', '#94a3b8',
];

let batchBarChart = null;
let statusBarChart = null;
let majorBarChart = null;
let sexDoughnutChart = null;
let degreeDoughnutChart = null;

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

function renderKpis(overall, bySex) {
    if (els.kpiTotalStudents) els.kpiTotalStudents.textContent = overall.total_students ?? 0;
    if (els.kpiActiveStudents) els.kpiActiveStudents.textContent = overall.active_students ?? 0;
    if (els.kpiTotalBatches) els.kpiTotalBatches.textContent = overall.total_batches ?? 0;
    if (els.kpiTotalMajors) els.kpiTotalMajors.textContent = overall.total_majors ?? 0;

    const male = bySex.find((r) => r.sex === 'male')?.total ?? 0;
    const female = bySex.find((r) => r.sex === 'female')?.total ?? 0;
    if (els.kpiMale) els.kpiMale.textContent = male;
    if (els.kpiFemale) els.kpiFemale.textContent = female;
}

/** Generic "Name / Count / %" table, shared by the status/batch/major sections. */
function renderBreakdownTable(tbody, rows, total, labelOf) {
    if (!tbody) return;

    if (!rows || rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-10 text-center text-neutral-400">No data yet.</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map((row) => {
        const count = Number(row.total) || 0;
        const pct = total > 0 ? ((count / total) * 100).toFixed(1) : '0.0';

        return `
        <tr>
            <td class="px-4 py-3 font-semibold text-neutral-900 dark:text-white">${escapeHtml(labelOf(row))}</td>
            <td class="px-4 py-3 font-mono">${count}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-1.5 rounded-full bg-neutral-100 dark:bg-white/10 overflow-hidden max-w-[80px]">
                        <div class="h-full rounded-full bg-indigo-500" style="width:${pct}%"></div>
                    </div>
                    <span class="text-xs font-bold text-neutral-600 dark:text-neutral-300">${pct}%</span>
                </div>
            </td>
        </tr>`;
    }).join('');
}

/**
 * Batch x Status cross-tab: rows are batches (chronological, from byBatch),
 * columns are statuses (busiest first, from byStatus) — reusing those two
 * lists' order keeps this table's row/column order consistent with the
 * charts and single-dimension tables above it. byBatchStatus only has one
 * row per (batch, status) combo that actually has students, so it's turned
 * into a lookup map first and every missing combo renders as a plain 0.
 */
function renderBatchStatusMatrix(byBatch, byStatus, byBatchStatus) {
    if (!els.matrixTableHead || !els.matrixTableBody) return;

    if (!byBatch.length || !byStatus.length) {
        els.matrixTableHead.innerHTML = '';
        els.matrixTableBody.innerHTML = '<tr><td class="px-4 py-10 text-center text-neutral-400">No data yet.</td></tr>';
        return;
    }

    const cellTotal = new Map();
    (byBatchStatus ?? []).forEach((row) => {
        cellTotal.set(`${row.batch_id}_${row.status_id}`, Number(row.total) || 0);
    });

    els.matrixTableHead.innerHTML = `
        <tr>
            <th class="px-4 py-3 sticky left-0 bg-neutral-50 dark:bg-neutral-800/50">Batch</th>
            ${byStatus.map((s) => `<th class="px-4 py-3 text-right whitespace-nowrap">${escapeHtml(s.name_en)}</th>`).join('')}
            <th class="px-4 py-3 text-right font-bold">Total</th>
        </tr>`;

    const rows = byBatch.map((batch) => {
        const cells = byStatus.map((status) => {
            const count = cellTotal.get(`${batch.id}_${status.id}`) ?? 0;
            return `<td class="px-4 py-3 text-right font-mono ${count === 0 ? 'text-neutral-300 dark:text-neutral-700' : ''}">${count}</td>`;
        });
        const rowTotal = Number(batch.total) || 0;

        return `
        <tr>
            <td class="px-4 py-3 font-bold text-neutral-900 dark:text-white sticky left-0 bg-white dark:bg-neutral-900">${escapeHtml(batch.shortcut || batch.name_en)}</td>
            ${cells.join('')}
            <td class="px-4 py-3 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">${rowTotal}</td>
        </tr>`;
    });

    const columnTotals = byStatus.map((status) => byBatch.reduce((sum, batch) => sum + (cellTotal.get(`${batch.id}_${status.id}`) ?? 0), 0));
    const grandTotal = byBatch.reduce((sum, batch) => sum + (Number(batch.total) || 0), 0);
    const footer = `
        <tr class="bg-neutral-50 dark:bg-neutral-800/50 font-bold">
            <td class="px-4 py-3 text-neutral-900 dark:text-white sticky left-0 bg-neutral-50 dark:bg-neutral-800/50">Total</td>
            ${columnTotals.map((t) => `<td class="px-4 py-3 text-right font-mono">${t}</td>`).join('')}
            <td class="px-4 py-3 text-right font-mono text-indigo-600 dark:text-indigo-400">${grandTotal}</td>
        </tr>`;

    els.matrixTableBody.innerHTML = rows.join('') + footer;
}

function renderBatchBarChart(byBatch) {
    const canvas = document.getElementById('batchBarChart');
    if (!canvas) return;

    batchBarChart?.destroy();
    batchBarChart = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: byBatch.map((r) => r.shortcut || r.name_en),
            datasets: [{
                label: 'Students',
                data: byBatch.map((r) => Number(r.total) || 0),
                backgroundColor: '#6366f1',
                borderRadius: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#737373' } },
                y: { beginAtZero: true, grid: { color: 'rgba(115,115,115,0.1)' }, ticks: { color: '#737373', precision: 0 } },
            },
        },
    });
}

/** Horizontal bar — used for both Status and Major, whose labels are too
 * long to fit under vertical bars, and whose category count (11+ statuses,
 * 17 majors) would overload a pie/doughnut. */
function renderHorizontalBarChart(canvasId, chartRef, rows, colorOffset = 0) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    chartRef?.destroy();
    return new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: rows.map((r) => r.name_en),
            datasets: [{
                label: 'Students',
                data: rows.map((r) => Number(r.total) || 0),
                backgroundColor: rows.map((_, i) => PALETTE[(i + colorOffset) % PALETTE.length]),
                borderRadius: 6,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: 'rgba(115,115,115,0.1)' }, ticks: { color: '#737373', precision: 0 } },
                y: { grid: { display: false }, ticks: { color: '#737373', font: { size: 11 } } },
            },
        },
    });
}

function renderDoughnut(canvasId, chartRef, labels, data, colors) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    chartRef?.destroy();
    return new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0 }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: { legend: { position: 'right', labels: { color: '#737373', usePointStyle: true, boxWidth: 8, font: { size: 11 } } } },
        },
    });
}

async function loadDashboard() {
    els.refreshIcon?.classList.add('animate-spin');
    try {
        const json = await fetchJson(REPORT_URL);
        const report = json?.data ?? {};

        const overall = report.overall ?? {};
        const byStatus = report.by_status ?? [];
        const byBatch = report.by_batch ?? [];
        const byMajor = report.by_major ?? [];
        const bySex = report.by_sex ?? [];
        const byDegree = report.by_degree ?? [];
        const byBatchStatus = report.by_batch_status ?? [];
        const total = overall.total_students ?? 0;

        renderKpis(overall, bySex);

        renderBreakdownTable(els.statusTableBody, byStatus, total, (r) => r.name_en);
        renderBreakdownTable(els.batchTableBody, byBatch, total, (r) => `${r.shortcut || r.name_en} (${r.academic_year ?? '—'})`);
        renderBreakdownTable(els.majorTableBody, byMajor, total, (r) => r.name_en);
        renderBatchStatusMatrix(byBatch, byStatus, byBatchStatus);

        renderBatchBarChart(byBatch);
        statusBarChart = renderHorizontalBarChart('statusBarChart', statusBarChart, byStatus);
        majorBarChart = renderHorizontalBarChart('majorBarChart', majorBarChart, byMajor, 3);

        sexDoughnutChart = renderDoughnut(
            'sexDoughnutChart', sexDoughnutChart,
            bySex.map((r) => r.sex),
            bySex.map((r) => Number(r.total) || 0),
            ['#ec4899', '#0ea5e9', '#a855f7'],
        );
        degreeDoughnutChart = renderDoughnut(
            'degreeDoughnutChart', degreeDoughnutChart,
            byDegree.map((r) => r.name_en),
            byDegree.map((r) => Number(r.total) || 0),
            ['#6366f1', '#10b981', '#f59e0b'],
        );
    } catch (err) {
        console.error('[dashboard] failed to load:', err);
    } finally {
        els.refreshIcon?.classList.remove('animate-spin');
    }
}

els.refreshBtn?.addEventListener('click', loadDashboard);

loadDashboard();
