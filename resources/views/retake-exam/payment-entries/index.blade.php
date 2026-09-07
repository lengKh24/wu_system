@extends('layouts.dashboard')
@section('title', 'Payment Reconciliation')
@section('content')

    <x-core.page-header title="ការផ្គូផ្គងបង់ប្រាក់ (Payment Reconciliation)"
        subtitle="កត់ត្រាការផ្គូផ្គងវិក័យបត្រជាមួយប្រព័ន្ធគណនេយ្យ (Record reconciliation entries against SA's payment batches)" />

    <div class="space-y-4">
        <x-ui.data-table :headers="[
            'N.O',
            'Student',
            'Proof',
            'Paid At',
            'Reconciliation',
            ['label' => 'Actions', 'align' => 'right'],
        ]" body-id="retake-entry-table-body" />
    </div>

    {{-- Add Reconciliation Entry --}}
    <x-ui.modal id="retakeEntryModal" card-id="retakeEntryModalCard" title-id="retakeEntryModalTitle"
        title="បញ្ចូលធាតុផ្គូផ្គង (Add Reconciliation Entry)" form-id="retakeEntryForm"
        close-fn="RetakeEntryModal" max-width="max-w-md">
        <div id="retakeEntryContext" class="text-sm text-neutral-800 dark:text-neutral-100 bg-neutral-50 dark:bg-white/5 rounded-xl px-3 py-2.5"></div>
        <div>
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">លេខបង់ប្រាក់ (Payment number)</label>
            <input type="text" id="retakeEntryNumber" placeholder="Bank/system reference number"
                class="w-full text-sm p-2.5 rounded-xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white dark:placeholder-neutral-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">កំណត់ចំណាំ (Payment note)</label>
            <textarea id="retakeEntryNote" rows="3" placeholder="Reconciliation notes"
                class="w-full text-sm p-2.5 rounded-xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
        </div>

        <x-slot:footer>
            <button type="button" onclick="RetakeEntryModal.toggle(false)"
                class="px-4 py-2.5 text-sm font-semibold text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/5 rounded-xl transition-colors">
                បោះបង់ (Cancel)
            </button>
            <button type="submit" form="retakeEntryForm"
                class="px-4 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/30 transition-all active:scale-95">
                រក្សាទុក (Save)
            </button>
        </x-slot:footer>
    </x-ui.modal>
@endsection

@push('scripts')
    @vite(['resources/js/retake-payment-entry/index.js'])
@endpush
