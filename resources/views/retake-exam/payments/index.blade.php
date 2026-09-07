@extends('layouts.dashboard')
@section('title', 'Payments')
@section('content')

    <x-core.page-header title="ការបង់ប្រាក់ប្រឡងសង (Retake Exam Payments)"
        subtitle="កត់ត្រាការបង់ប្រាក់ និងអញ្ជើញចូលក្រុម Telegram (Record payment and invite to the Telegram group)" />

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
                <input id="retakePaymentSearchInput" type="text"
                    class="block w-full p-2.5 ps-10 text-sm text-neutral-900 border border-neutral-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-neutral-900 dark:border-white/10 dark:placeholder-neutral-400 dark:text-white transition-all"
                    placeholder="Search by student name/code..." autocomplete="off" />
            </div>

            <select id="retakePaymentTermFilter" class="text-sm rounded-xl border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">គ្រប់រយៈពេល (All terms)</option>
            </select>
            <select id="retakePaymentExamTypeFilter" class="text-sm rounded-xl border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">គ្រប់ប្រភេទ (All exam types)</option>
            </select>
            <select id="retakePaymentStatusFilter" class="text-sm rounded-xl border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="unpaid" selected>មិនទាន់បង់ (Unpaid)</option>
                <option value="paid">បង់ (Paid)</option>
                <option value="">គ្រប់ (All)</option>
            </select>
        </div>

        {{-- Bulk mark-paid bar --}}
        <div class="flex items-center justify-between gap-3 px-4 py-3 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 rounded-xl">
            <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                <span id="retakeSelectedCountLabel">0</span> ជ្រើសរើស (selected) — ជ្រើសរើសបានតែនិស្សិតម្នាក់ក្នុងពេលតែមួយ (one student at a time)
            </span>
            <button type="button" id="retakeMarkPaidSelectedBtn" disabled
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 rounded-lg shadow-sm hover:bg-emerald-700 active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                </svg>
                បង់ជាបណ្តុំ (Mark Paid Selected)
            </button>
        </div>

        <x-ui.data-table :headers="[
            '',
            'N.O',
            'Student',
            'Term',
            'Exam Type',
            'Subject',
            'Registered At',
            'Payment',
            'Telegram',
            ['label' => 'Actions', 'align' => 'right'],
        ]" body-id="retake-payment-table-body" />
    </div>

    {{-- Mark Paid --}}
    <x-ui.modal id="retakePayModal" card-id="retakePayModalCard" title-id="retakePayModalTitle"
        title="កត់ត្រាការបង់ប្រាក់ (Record Payment)" form-id="retakePayForm"
        close-fn="RetakePayModal" max-width="max-w-md">
        <div id="retakePayContext" class="text-sm text-neutral-800 dark:text-neutral-100 bg-neutral-50 dark:bg-white/5 rounded-xl px-3 py-2.5"></div>

        <div>
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">
                រូបភាពបញ្ជាក់ការបង់ប្រាក់ (Payment proof image)
            </label>

            {{-- Click to browse, drag a file in, or paste (Ctrl+V) an image
                 copied from elsewhere (a screenshot, a chat app, etc). --}}
            <div id="retakePayDropzone" tabindex="0"
                class="relative flex flex-col items-center justify-center gap-2 px-4 py-8 text-center border-2 border-dashed border-neutral-300 dark:border-white/15 rounded-xl cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500/50 hover:bg-indigo-50/40 dark:hover:bg-indigo-500/5 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-colors">
                <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                </svg>
                <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                    ចុចដើម្បីជ្រើសរើសរូបភាព ឬអូសមក ឬ Paste (Ctrl+V)
                    <span class="block text-neutral-400 dark:text-neutral-500 font-normal mt-0.5">Click to browse, drag a file in, or paste an image</span>
                </p>
                <img id="retakePayPreview" class="hidden max-h-40 rounded-lg border border-neutral-200 dark:border-white/10 object-contain" />
            </div>
            <input type="file" id="retakePayFile" accept="image/*" class="hidden">
            <div class="flex items-center justify-between mt-1.5">
                <p id="retakePayFileName" class="text-xs text-neutral-400 truncate"></p>
                <button type="button" id="retakePayClearBtn" class="hidden text-xs font-semibold text-rose-500 hover:text-rose-600">
                    លុបរូបភាព (Remove)
                </button>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">កំណត់ចំណាំ (Remark, optional)</label>
            <textarea id="retakePayRemark" rows="2"
                class="w-full text-sm p-2.5 rounded-xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
        </div>

        <x-slot:footer>
            <button type="button" onclick="RetakePayModal.toggle(false)"
                class="px-4 py-2.5 text-sm font-semibold text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/5 rounded-xl transition-colors">
                បោះបង់ (Cancel)
            </button>
            <button type="submit" form="retakePayForm"
                class="px-4 py-2.5 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-lg shadow-emerald-500/30 transition-all active:scale-95">
                កត់ត្រាការបង់ប្រាក់ (Record Payment)
            </button>
        </x-slot:footer>
    </x-ui.modal>
@endsection

@push('scripts')
    @vite(['resources/js/retake-payment/index.js'])
@endpush
