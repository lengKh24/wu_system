@extends('layouts.dashboard')
@section('title', 'Retake Exam')
@section('content')

    <script>
        window.CAN_EDIT_RETAKE_REGISTRATION = @json(auth()->user()->can('retake-registration.edit'));
        window.CAN_DELETE_RETAKE_REGISTRATION = @json(auth()->user()->can('retake-registration.delete'));
        window.CAN_EDIT_RETAKE_BATCH = @json(auth()->user()->can('retake-batch.edit'));
        window.CAN_CREATE_RETAKE_BATCH = @json(auth()->user()->can('retake-batch.create'));
        window.CAN_CREATE_RETAKE_TERM = @json(auth()->user()->can('retake-term.create'));
        window.CAN_DELETE_RETAKE_BATCH = @json(auth()->user()->can('retake-batch.delete'));
    </script>

    <x-core.page-header title="ការប្រឡងសង (Retake Exam)"
        subtitle="បញ្ជីទាំងអស់នៃការចុះឈ្មោះប្រឡងសង គ្រប់ដំណាក់កាល (Every retake registration across every stage)" />

    <div class="space-y-4">
        {{-- Batch strip: lifecycle actions (import / close / carry-forward / telegram) --}}
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-bold text-neutral-700 dark:text-neutral-200">ជំនាន់ (Batches)</h3>
            <div class="flex items-center gap-3">
                <a href="{{ route('retake-registration.report') }}"
                    class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    របាយការណ៍ (Report)
                </a>
                <button type="button" id="retakeImportBtn"
                class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 rounded-xl shadow-md shadow-indigo-500/25 active:scale-95 transition-all duration-200">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                    នាំចូល លើកទី១ (Import 1st Supp.)
                </button>
            </div>
        </div>
        <div id="retake-batch-strip" class="flex gap-3 overflow-x-auto pb-2">
            <p class="text-xs text-neutral-400 italic px-1">កំពុងផ្ទុក... (Loading batches...)</p>
        </div>

        {{-- Filters --}}
        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <div class="relative w-full md:w-72 group">
                <div class="absolute inset-y-0 left-0 flex items-center ps-3 pointer-events-none">
                    <svg class="w-4 h-4 text-neutral-500 group-focus-within:text-indigo-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
                    </svg>
                </div>
                <input id="retakeSearchInput" type="text"
                    class="block w-full p-2.5 ps-10 text-sm text-neutral-900 border border-neutral-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-neutral-900 dark:border-white/10 dark:placeholder-neutral-400 dark:text-white transition-all"
                    placeholder="Search by student name/code..." autocomplete="off" />
            </div>

            <div class="flex items-center gap-1.5">
                <select id="retakeTermFilter" class="text-sm rounded-xl border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">គ្រប់រយៈពេល (All terms)</option>
                </select>
                <button type="button" id="retakeNewTermBtn" title="Create a new retake term"
                    class="shrink-0 p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </button>
            </div>
            <select id="retakeExamTypeFilter" class="text-sm rounded-xl border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">គ្រប់ប្រភេទ (All exam types)</option>
            </select>
            <select id="retakePaymentFilter" class="text-sm rounded-xl border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">គ្រប់ស្ថានភាពបង់ (All payment)</option>
                <option value="paid">បង់ (Paid)</option>
                <option value="unpaid">មិនទាន់ (Unpaid)</option>
            </select>
            <select id="retakeOutcomeFilter" class="text-sm rounded-xl border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">គ្រប់លទ្ធផល (All outcomes)</option>
                <option value="pending">រង់ចាំ (Pending)</option>
                <option value="passed">ជាប់ (Passed)</option>
                <option value="failed">ធ្លាក់ (Failed)</option>
                <option value="absent">អវត្តមាន (Absent)</option>
            </select>

            {{-- Exports whatever the filters above are currently set to —
                 e.g. filter to a batch + "Paid" to get the confirmed/paid
                 list for preparing an exam schedule. --}}
            <button type="button" id="retakeExportBtn" title="Export the current filtered list to Excel"
                class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                នាំចេញ (Export)
            </button>
        </div>

        <x-ui.data-table :headers="[
            'N.O',
            'Student',
            'Term',
            'Exam Type',
            'Subject',
            'Lecturer',
            'Status Note',
            'Selected',
            'Registered At',
            'Payment',
            'Outcome / Score',
            ['label' => 'Actions', 'align' => 'right'],
        ]" body-id="retake-registrations-table-body" />
    </div>

    {{-- Create Retake Term --}}
    <x-ui.modal id="retakeTermModal" card-id="retakeTermModalCard" title-id="retakeTermModalTitle"
        title="បង្កើតរយៈពេលថ្មី (New Retake Term)" form-id="retakeTermForm"
        close-fn="RetakeTermModal" max-width="max-w-md">
        <div>
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">បរិវេណ (Campus)</label>
            <select id="retakeTermCampus" required
                class="w-full text-sm p-2.5 rounded-xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </select>
            <p id="retakeTermCampusHint" class="hidden mt-2 text-xs text-amber-600 dark:text-amber-400">
                មិនទាន់មានបរិវេណទេ — <a href="{{ route('campus.index') }}" class="underline font-semibold">បង្កើតបរិវេណជាមុនសិន (create one first)</a>.
            </p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">ចំណងជើង (Title)</label>
            <input type="text" id="retakeTermTitle" required placeholder="e.g. B22Y2S2 (Mar-Jul)"
                class="w-full text-sm p-2.5 rounded-xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white dark:placeholder-neutral-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">ថ្ងៃចាប់ផ្តើម (Start)</label>
                <input type="date" id="retakeTermStart"
                    class="w-full text-sm p-2.5 rounded-xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white [color-scheme:light] dark:[color-scheme:dark] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">ថ្ងៃបញ្ចប់ (End)</label>
                <input type="date" id="retakeTermEnd"
                    class="w-full text-sm p-2.5 rounded-xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white [color-scheme:light] dark:[color-scheme:dark] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        <x-slot:footer>
            <button type="button" onclick="RetakeTermModal.toggle(false)"
                class="px-4 py-2.5 text-sm font-semibold text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/5 rounded-xl transition-colors">
                បោះបង់ (Cancel)
            </button>
            <button type="submit" form="retakeTermForm"
                class="px-4 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/30 transition-all active:scale-95">
                បង្កើត (Create)
            </button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Import 1st Supplementary --}}
    <x-ui.modal id="retakeImportModal" card-id="retakeImportModalCard" title-id="retakeImportModalTitle"
        title="នាំចូល ប្រឡងសង លើកទី១ (Import 1st Supplementary)" form-id="retakeImportForm"
        close-fn="RetakeImportModal" max-width="max-w-md">
        <div>
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">រយៈពេល (Term)</label>
            <select id="retakeImportTerm" required
                class="w-full text-sm p-2.5 rounded-xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </select>
            <p id="retakeImportTermHint" class="hidden mt-2 text-xs text-amber-600 dark:text-amber-400">
                មិនទាន់មានរយៈពេលទេ — <button type="button" onclick="RetakeImportModal.toggle(false); RetakeTermModal.toggle(true);" class="underline font-semibold">បង្កើតរយៈពេលមុនសិន (create one first)</button>.
            </p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">ឯកសារ (File)</label>

            <div id="retakeImportDropzone" tabindex="0"
                class="relative flex flex-col items-center justify-center gap-2 px-4 py-8 text-center border-2 border-dashed border-neutral-300 dark:border-white/15 rounded-xl cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500/50 hover:bg-indigo-50/40 dark:hover:bg-indigo-500/5 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-colors">
                <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                </svg>
                <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                    ចុចដើម្បីជ្រើសរើសឯកសារ ឬអូសមក
                    <span class="block text-neutral-400 dark:text-neutral-500 font-normal mt-0.5">Click to browse, or drag a file in (.xlsx, .xls, .csv)</span>
                </p>
            </div>
            <input type="file" id="retakeImportFile" accept=".xlsx,.xls,.csv" class="hidden">
            <div class="flex items-center justify-between mt-1.5">
                <p id="retakeImportFileName" class="text-xs text-neutral-400 truncate"></p>
                <button type="button" id="retakeImportClearBtn" class="hidden text-xs font-semibold text-rose-500 hover:text-rose-600">
                    លុបឯកសារ (Remove)
                </button>
            </div>
        </div>

        <x-slot:footer>
            <button type="button" onclick="RetakeImportModal.toggle(false)"
                class="px-4 py-2.5 text-sm font-semibold text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/5 rounded-xl transition-colors">
                បោះបង់ (Cancel)
            </button>
            <button type="submit" form="retakeImportForm" id="retakeImportSubmitBtn"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/30 transition-all active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100">
                <svg id="retakeImportSpinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span id="retakeImportSubmitLabel">នាំចូល (Import)</span>
            </button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Import Results — shown after a successful import instead of a raw
         SweetAlert dump, so skipped/flagged rows read as an actual table. --}}
    <x-ui.modal id="retakeImportResultsModal" card-id="retakeImportResultsModalCard" title-id="retakeImportResultsModalTitle"
        title="លទ្ធផលនាំចូល (Import Results)" close-fn="RetakeImportResultsModal" max-width="max-w-2xl">
        <div id="retakeImportResultsBody" class="space-y-5"></div>

        <x-slot:footer>
            <button type="button" onclick="RetakeImportResultsModal.toggle(false)"
                class="px-4 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/30 transition-all active:scale-95">
                យល់ព្រម (Got it)
            </button>
        </x-slot:footer>
    </x-ui.modal>
@endsection

@push('scripts')
    @vite(['resources/js/retake-exam/index.js'])
@endpush
