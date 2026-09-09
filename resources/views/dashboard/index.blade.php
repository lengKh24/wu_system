@extends('layouts.dashboard')
@section('title', 'Dashboard')

@section('content')

<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">
            ផ្ទាំងគ្រប់គ្រង <span class="text-indigo-600 dark:text-indigo-400">(Dashboard)</span>
        </h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            សង្ខេបនិស្សិតកំពុងសិក្សា — តាមស្ថានភាព, ជំនាន់ និងជំនាញ (live snapshot of every currently-enrolled student)
        </p>
    </div>
    <button id="refreshDashboardBtn" type="button"
        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-neutral-700 dark:text-neutral-200 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl shadow-sm hover:bg-neutral-50 dark:hover:bg-white/5 transition-all active:scale-95">
        <svg id="refreshDashboardIcon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        ផ្ទុកឡើងវិញ (Refresh)
    </button>
</div>

{{-- KPI cards --}}
<div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
    <div class="p-5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">និស្សិតសរុប</span>
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
            </div>
        </div>
        <div id="kpiTotalStudents" class="text-2xl font-black text-neutral-900 dark:text-white">—</div>
        <div class="text-xs text-neutral-400">Total Students</div>
    </div>

    <div class="p-5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">កំពុងសិក្សា</span>
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
        <div id="kpiActiveStudents" class="text-2xl font-black text-emerald-600 dark:text-emerald-400">—</div>
        <div class="text-xs text-neutral-400">Active</div>
    </div>

    <div class="p-5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">ជំនាន់</span>
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /></svg>
            </div>
        </div>
        <div id="kpiTotalBatches" class="text-2xl font-black text-blue-600 dark:text-blue-400">—</div>
        <div class="text-xs text-neutral-400">Batches</div>
    </div>

    <div class="p-5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">ជំនាញ</span>
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-fuchsia-50 dark:bg-fuchsia-500/10 text-fuchsia-600 dark:text-fuchsia-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
            </div>
        </div>
        <div id="kpiTotalMajors" class="text-2xl font-black text-fuchsia-600 dark:text-fuchsia-400">—</div>
        <div class="text-xs text-neutral-400">Majors</div>
    </div>

    <div class="p-5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">ប្រុស</span>
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-500/10 text-sky-600 dark:text-sky-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
            </div>
        </div>
        <div id="kpiMale" class="text-2xl font-black text-sky-600 dark:text-sky-400">—</div>
        <div class="text-xs text-neutral-400">Male</div>
    </div>

    <div class="p-5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">ស្រី</span>
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-pink-50 dark:bg-pink-500/10 text-pink-600 dark:text-pink-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
            </div>
        </div>
        <div id="kpiFemale" class="text-2xl font-black text-pink-600 dark:text-pink-400">—</div>
        <div class="text-xs text-neutral-400">Female</div>
    </div>
</div>

{{-- Batch trend + small demographic charts --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2 p-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <h3 class="text-sm font-bold text-neutral-900 dark:text-white mb-1">និស្សិតតាមជំនាន់</h3>
        <p class="text-xs text-neutral-400 mb-4">Students by batch (oldest to newest cohort)</p>
        <div class="relative h-72">
            <canvas id="batchBarChart"></canvas>
        </div>
    </div>

    <div class="p-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <h3 class="text-sm font-bold text-neutral-900 dark:text-white mb-1">ភេទ / សញ្ញាបត្រ</h3>
        <p class="text-xs text-neutral-400 mb-4">Gender &amp; degree type</p>
        <div class="relative h-32 mb-4">
            <canvas id="sexDoughnutChart"></canvas>
        </div>
        <div class="relative h-32">
            <canvas id="degreeDoughnutChart"></canvas>
        </div>
    </div>
</div>

{{-- Status + Major charts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="p-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <h3 class="text-sm font-bold text-neutral-900 dark:text-white mb-1">និស្សិតតាមស្ថានភាព</h3>
        <p class="text-xs text-neutral-400 mb-4">Students by status</p>
        <div class="relative h-80">
            <canvas id="statusBarChart"></canvas>
        </div>
    </div>

    <div class="p-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <h3 class="text-sm font-bold text-neutral-900 dark:text-white mb-1">និស្សិតតាមជំនាញ</h3>
        <p class="text-xs text-neutral-400 mb-4">Students by major</p>
        <div class="relative h-80">
            <canvas id="majorBarChart"></canvas>
        </div>
    </div>
</div>

{{-- Detailed breakdown tables --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="overflow-hidden bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="px-6 py-4 border-b border-neutral-100 dark:border-white/5">
            <h3 class="text-sm font-bold text-neutral-900 dark:text-white">តាមស្ថានភាព (By Status)</h3>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-sm text-left text-neutral-500 dark:text-neutral-400">
                <thead class="text-xs uppercase text-neutral-700 bg-neutral-50 dark:bg-neutral-800/50 dark:text-neutral-300 sticky top-0">
                    <tr>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Count</th>
                        <th class="px-4 py-3">%</th>
                    </tr>
                </thead>
                <tbody id="statusTableBody" class="divide-y divide-neutral-200 dark:divide-white/5">
                    <tr><td colspan="3" class="px-4 py-10 text-center text-neutral-400">កំពុងទាញយកទិន្នន័យ...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="px-6 py-4 border-b border-neutral-100 dark:border-white/5">
            <h3 class="text-sm font-bold text-neutral-900 dark:text-white">តាមជំនាន់ (By Batch)</h3>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-sm text-left text-neutral-500 dark:text-neutral-400">
                <thead class="text-xs uppercase text-neutral-700 bg-neutral-50 dark:bg-neutral-800/50 dark:text-neutral-300 sticky top-0">
                    <tr>
                        <th class="px-4 py-3">Batch</th>
                        <th class="px-4 py-3">Count</th>
                        <th class="px-4 py-3">%</th>
                    </tr>
                </thead>
                <tbody id="batchTableBody" class="divide-y divide-neutral-200 dark:divide-white/5">
                    <tr><td colspan="3" class="px-4 py-10 text-center text-neutral-400">កំពុងទាញយកទិន្នន័យ...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="px-6 py-4 border-b border-neutral-100 dark:border-white/5">
            <h3 class="text-sm font-bold text-neutral-900 dark:text-white">តាមជំនាញ (By Major)</h3>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-sm text-left text-neutral-500 dark:text-neutral-400">
                <thead class="text-xs uppercase text-neutral-700 bg-neutral-50 dark:bg-neutral-800/50 dark:text-neutral-300 sticky top-0">
                    <tr>
                        <th class="px-4 py-3">Major</th>
                        <th class="px-4 py-3">Count</th>
                        <th class="px-4 py-3">%</th>
                    </tr>
                </thead>
                <tbody id="majorTableBody" class="divide-y divide-neutral-200 dark:divide-white/5">
                    <tr><td colspan="3" class="px-4 py-10 text-center text-neutral-400">កំពុងទាញយកទិន្នន័យ...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Batch x Status cross-tab --}}
<div class="mt-6 overflow-hidden bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm">
    <div class="px-6 py-4 border-b border-neutral-100 dark:border-white/5">
        <h3 class="text-sm font-bold text-neutral-900 dark:text-white">ស្ថានភាពតាមជំនាន់និមួយៗ (Every Status, Per Batch)</h3>
        <p class="text-xs text-neutral-400 mt-0.5">How many students are in each status, broken down per batch</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-neutral-500 dark:text-neutral-400">
            <thead id="matrixTableHead" class="text-xs uppercase text-neutral-700 bg-neutral-50 dark:bg-neutral-800/50 dark:text-neutral-300"></thead>
            <tbody id="matrixTableBody" class="divide-y divide-neutral-200 dark:divide-white/5">
                <tr><td class="px-4 py-10 text-center text-neutral-400">កំពុងទាញយកទិន្នន័យ...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    window.DASHBOARD_REPORT_URL = @json(route('dashboard.report'));
</script>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/js/dashboard/dashboard.js'])
@endpush
