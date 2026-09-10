<x-ui.modal id="lecturerImportModal" card-id="lecturerImportModalCard" title-id="lecturerImportModalTitle"
    title="នាំចូលសាស្ត្រាចារ្យ (Import Lecturers)" form-id="lecturerImportForm"
    close-fn="LecturerImportModal" max-width="max-w-md">
    <p class="text-xs text-neutral-500 dark:text-neutral-400 -mt-1">
        ជួរឈរត្រូវតែដូចនឹងឯកសារនាំចេញ (Columns must match the exported file — export the current list first if you're unsure of the format).
    </p>

    <div>
        <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">ឯកសារ (File)</label>

        <div id="lecturerImportDropzone" tabindex="0"
            class="relative flex flex-col items-center justify-center gap-2 px-4 py-8 text-center border-2 border-dashed border-neutral-300 dark:border-white/15 rounded-xl cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500/50 hover:bg-indigo-50/40 dark:hover:bg-indigo-500/5 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-colors">
            <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
            </svg>
            <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                ចុចដើម្បីជ្រើសរើសឯកសារ ឬអូសមក
                <span class="block text-neutral-400 dark:text-neutral-500 font-normal mt-0.5">Click to browse, or drag a file in (.xlsx, .xls, .csv)</span>
            </p>
        </div>
        <input type="file" id="lecturerImportFile" accept=".xlsx,.xls,.csv" class="hidden">
        <div class="flex items-center justify-between mt-1.5">
            <p id="lecturerImportFileName" class="text-xs text-neutral-400 truncate"></p>
            <button type="button" id="lecturerImportClearBtn" class="hidden text-xs font-semibold text-rose-500 hover:text-rose-600">
                លុបឯកសារ (Remove)
            </button>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" onclick="LecturerImportModal.toggle(false)"
            class="px-4 py-2.5 text-sm font-semibold text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/5 rounded-xl transition-colors">
            បោះបង់ (Cancel)
        </button>
        <button type="submit" form="lecturerImportForm" id="lecturerImportSubmitBtn"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/30 transition-all active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100">
            <svg id="lecturerImportSpinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span id="lecturerImportSubmitLabel">នាំចូល (Import)</span>
        </button>
    </x-slot:footer>
</x-ui.modal>

{{-- Import Results — categorized, readable tables instead of a raw dump. --}}
<x-ui.modal id="lecturerImportResultsModal" card-id="lecturerImportResultsModalCard" title-id="lecturerImportResultsModalTitle"
    title="លទ្ធផលនាំចូល (Import Results)" close-fn="LecturerImportResultsModal" max-width="max-w-2xl">
    <div id="lecturerImportResultsBody" class="space-y-5"></div>

    <x-slot:footer>
        <button type="button" onclick="LecturerImportResultsModal.toggle(false)"
            class="px-4 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/30 transition-all active:scale-95">
            យល់ព្រម (Got it)
        </button>
    </x-slot:footer>
</x-ui.modal>
