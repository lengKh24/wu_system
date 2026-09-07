@extends('layouts.dashboard')
@section('title', 'Retake Exam Report')
@section('content')

<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
        <a href="{{ route('retake-registration.index') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-500 hover:text-indigo-600 dark:text-neutral-400 dark:hover:text-indigo-400 transition-colors mb-2">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            ត្រឡប់ក្រោយ (Back to Retake Exam)
        </a>
        <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">
            របាយការណ៍ប្រឡងសង <span class="text-indigo-600 dark:text-indigo-400">(Retake Exam Report)</span>
        </h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            សង្ខេបលទ្ធផល និងការបង់ប្រាក់ តាមប្រភេទប្រឡងនីមួយៗ (registrations confirmed by students only)
        </p>
    </div>
    <div class="flex items-center gap-3">
        <select id="reportTermFilter" class="text-sm rounded-xl border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">គ្រប់រយៈពេល (All terms)</option>
        </select>
        <button id="refreshReportBtn" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-neutral-700 dark:text-neutral-200 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl shadow-sm hover:bg-neutral-50 dark:hover:bg-white/5 transition-all active:scale-95">
            <svg id="refreshReportIcon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            ផ្ទុកឡើងវិញ (Refresh)
        </button>
    </div>
</div>

{{-- KPI cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="p-5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">ចុះឈ្មោះសរុប</span>
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
            </div>
        </div>
        <div id="kpiTotalConfirmed" class="text-2xl font-black text-neutral-900 dark:text-white">—</div>
        <div class="text-xs text-neutral-400">Total Confirmed Registrations</div>
    </div>

    <div class="p-5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">ជាប់</span>
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
        <div id="kpiPassed" class="text-2xl font-black text-emerald-600 dark:text-emerald-400">—</div>
        <div class="text-xs text-neutral-400">Passed</div>
    </div>

    <div class="p-5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">ត្រូវប្រឡងសងទៀត</span>
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </div>
        </div>
        <div id="kpiStillNeedRetake" class="text-2xl font-black text-rose-600 dark:text-rose-400">—</div>
        <div class="text-xs text-neutral-400">Failed + Absent (still need retake)</div>
    </div>

    <div class="p-5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">អត្រាបានបង់</span>
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
            </div>
        </div>
        <div id="kpiPaymentRate" class="text-2xl font-black text-amber-600 dark:text-amber-400">—</div>
        <div class="text-xs text-neutral-400">Payment Collection Rate</div>
    </div>
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2 p-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <h3 class="text-sm font-bold text-neutral-900 dark:text-white mb-1">លទ្ធផលតាមប្រភេទប្រឡង</h3>
        <p class="text-xs text-neutral-400 mb-4">Outcome breakdown by exam type</p>
        <div class="relative h-72">
            <canvas id="outcomeBarChart"></canvas>
        </div>
    </div>

    <div class="p-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <h3 class="text-sm font-bold text-neutral-900 dark:text-white mb-1">ស្ថានភាពបង់ប្រាក់សរុប</h3>
        <p class="text-xs text-neutral-400 mb-4">Overall payment status</p>
        <div class="relative h-72">
            <canvas id="paymentDoughnutChart"></canvas>
        </div>
    </div>
</div>

{{-- Breakdown table --}}
<div class="overflow-hidden bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
    <div class="px-6 py-4 border-b border-neutral-100 dark:border-white/5">
        <h3 class="text-sm font-bold text-neutral-900 dark:text-white">សេចក្តីលម្អិតតាមប្រភេទប្រឡង (Breakdown by Exam Type)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-neutral-500 dark:text-neutral-400">
            <thead class="text-xs uppercase text-neutral-700 bg-neutral-50 dark:bg-neutral-800/50 dark:text-neutral-300">
                <tr>
                    <th class="px-6 py-3">Exam Type</th>
                    <th class="px-6 py-3">Total</th>
                    <th class="px-6 py-3">Passed</th>
                    <th class="px-6 py-3">Failed</th>
                    <th class="px-6 py-3">Absent</th>
                    <th class="px-6 py-3">Pending</th>
                    <th class="px-6 py-3">Paid</th>
                    <th class="px-6 py-3">Unpaid</th>
                    <th class="px-6 py-3">Pass Rate</th>
                </tr>
            </thead>
            <tbody id="examTypeTableBody" class="divide-y divide-neutral-200 dark:divide-white/5">
                <tr><td colspan="9" class="px-6 py-10 text-center text-neutral-400">កំពុងទាញយកទិន្នន័យ...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    window.RETAKE_REPORT_URL = @json(route('retake-registrations.report'));
    window.RETAKE_TERMS_URL = @json(route('retake-terms.index'));
</script>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/js/retake-exam/report.js'])
@endpush
