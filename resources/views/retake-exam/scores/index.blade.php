@extends('layouts.dashboard')
@section('title', 'Score Entry')
@section('content')

    <script>
        window.CAN_EDIT_RETAKE_SCORE = @json(auth()->user()->can('retake-score.edit'));
    </script>

    <x-core.page-header title="ការដាក់ពិន្ទុ (Score Entry)"
        subtitle="បញ្ចូលពិន្ទុសម្រាប់ការចុះឈ្មោះប្រឡងសង (Enter scores for retake registrations)" />

    <div class="space-y-4">
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
                <input id="retakeScoreSearchInput" type="text"
                    class="block w-full p-2.5 ps-10 text-sm text-neutral-900 border border-neutral-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-neutral-900 dark:border-white/10 dark:placeholder-neutral-400 dark:text-white transition-all"
                    placeholder="Search by student name/code..." autocomplete="off" />
            </div>

            <select id="retakeScoreTermFilter" class="text-sm rounded-xl border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">គ្រប់រយៈពេល (All terms)</option>
            </select>
            <select id="retakeScoreExamTypeFilter" class="text-sm rounded-xl border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">គ្រប់ប្រភេទ (All exam types)</option>
            </select>
            <select id="retakeScoreOutcomeFilter" class="text-sm rounded-xl border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">គ្រប់លទ្ធផល (All outcomes)</option>
                <option value="pending">រង់ចាំ (Pending)</option>
                <option value="passed">ជាប់ (Passed)</option>
                <option value="failed">ធ្លាក់ (Failed)</option>
                <option value="absent">អវត្តមាន (Absent)</option>
            </select>
        </div>

        <x-ui.data-table :headers="[
            'N.O',
            'Student',
            'Term',
            'Exam Type',
            'Subject',
            'Lecturer',
            'Outcome',
            'Score',
            ['label' => 'Actions', 'align' => 'right'],
        ]" body-id="retake-score-table-body" />
    </div>

    {{-- Enter Score --}}
    <x-ui.modal id="retakeScoreModal" card-id="retakeScoreModalCard" title-id="retakeScoreModalTitle"
        title="បញ្ចូលពិន្ទុ (Enter Score)" form-id="retakeScoreForm"
        close-fn="RetakeScoreModal" max-width="max-w-md">
        <div id="retakeScoreContext" class="text-sm font-semibold text-neutral-800 dark:text-neutral-100 bg-neutral-50 dark:bg-white/5 rounded-xl px-3 py-2.5"></div>
        <div>
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">ពិន្ទុ (Score, 0–100)</label>
            <input type="number" id="retakeScoreValue" min="0" max="100" step="0.01" required
                class="w-full text-sm p-2.5 rounded-xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">កំណត់ចំណាំ (Remark, optional)</label>
            <textarea id="retakeScoreRemark" rows="2"
                class="w-full text-sm p-2.5 rounded-xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
        </div>

        <x-slot:footer>
            <button type="button" onclick="RetakeScoreModal.toggle(false)"
                class="px-4 py-2.5 text-sm font-semibold text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/5 rounded-xl transition-colors">
                បោះបង់ (Cancel)
            </button>
            <button type="submit" form="retakeScoreForm"
                class="px-4 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/30 transition-all active:scale-95">
                រក្សាទុក (Save)
            </button>
        </x-slot:footer>
    </x-ui.modal>
@endsection

@push('scripts')
    @vite(['resources/js/retake-score/index.js'])
@endpush
