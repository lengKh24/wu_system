@extends('layouts.state.public')
@section('title', 'ការចុះឈ្មោះប្រឡងសង (Retake Exam Registration)')
@section('content')

<div class="text-center mb-12 fade-up">
    <div class="mx-auto mb-5 flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-800 text-white shadow-lg shadow-indigo-500/25">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
    </div>

    <div class="inline-flex items-center gap-2 px-3 py-1 mb-5 text-[11px] font-bold tracking-widest uppercase rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-200/70 dark:border-indigo-500/20">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        Student Self-Service
    </div>
    <h1 class="font-display text-3xl sm:text-4xl font-bold tracking-tight">ការចុះឈ្មោះប្រឡងសង</h1>
    <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400 max-w-md mx-auto">
        បញ្ចូលកូដនិស្សិតរបស់អ្នកដើម្បីមើល និងបញ្ជាក់ការចុះឈ្មោះប្រឡងសង
        <span class="block text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">Enter your student code to see and confirm your retake registration</span>
    </p>
</div>

{{-- Lookup --}}
<div id="retakeLookupCard" class="max-w-md mx-auto fade-up" style="animation-delay:120ms">
    <div class="bg-white/80 dark:bg-neutral-900/70 backdrop-blur-sm border border-neutral-200/80 dark:border-white/10 rounded-3xl shadow-sm p-6 sm:p-7">
        <label for="retakeCode" class="block text-xs font-bold uppercase tracking-widest text-neutral-400 mb-2">
            កូដនិស្សិត (Student Code)
        </label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center ps-4 pointer-events-none">
                <svg class="w-4.5 h-4.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
            </div>
            <input id="retakeCode" type="text" placeholder="e.g. ST-IT8800" autocomplete="off"
                class="block w-full p-4 ps-11 text-base sm:text-sm bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-400 dark:focus:border-indigo-500/50 dark:placeholder-neutral-500 outline-none transition-all" />
        </div>
        <button id="retakeLookupBtn" type="button"
            class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-3.5 text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 rounded-2xl shadow-lg shadow-indigo-500/25 active:scale-[0.98] transition-all duration-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
            </svg>
            ស្វែងរក (Look Up)
        </button>
        <p id="retakeLookupError" class="hidden mt-3 text-center text-xs font-bold text-rose-500"></p>
    </div>
</div>

{{-- Result --}}
<div id="retakeResult" class="hidden max-w-2xl mx-auto mt-10 space-y-6 fade-up">

    {{-- Student identity --}}
    <div class="flex items-center gap-4 bg-white/80 dark:bg-neutral-900/70 backdrop-blur-sm border border-neutral-200/80 dark:border-white/10 rounded-2xl shadow-sm px-5 py-4">
        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-800 text-white font-bold text-lg shrink-0" id="retakeStudentInitial">?</div>
        <div class="min-w-0">
            <div id="retakeStudentName" class="font-bold text-neutral-900 dark:text-white truncate">—</div>
            <div id="retakeStudentCode" class="text-xs text-neutral-400 font-mono">—</div>
        </div>
    </div>

    {{-- Pending batches --}}
    <div id="retakePendingBatches" class="space-y-5"></div>

    <div id="retakeConfirmSection" class="hidden space-y-3">
        <div class="flex flex-col sm:flex-row gap-3">
            <button id="retakeSaveBtn" type="button"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3.5 text-sm font-bold text-neutral-700 dark:text-neutral-200 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm hover:bg-neutral-50 dark:hover:bg-white/5 active:scale-[0.98] transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                រក្សាទុកជម្រើស (Save Selections)
            </button>
            <button id="retakeConfirmBtn" type="button"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3.5 text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 rounded-2xl shadow-lg shadow-emerald-500/25 active:scale-[0.98] transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                បញ្ជាក់ការចុះឈ្មោះ (Confirm Registration)
            </button>
        </div>
        <p class="text-center text-xs text-neutral-400 dark:text-neutral-500 max-w-md mx-auto">
            រក្សាទុកជម្រើសមិនចាក់សោអ្វីទេ — អ្នកអាចផ្លាស់ប្តូរ និងស្វែងរកម្តងទៀតនៅពេលក្រោយ។ ការបញ្ជាក់នឹងចាក់សោជម្រើសបច្ចុប្បន្ន ហើយមិនអាចត្រឡប់វិញបានទេ សូមទាក់ទងការិយាល័យកិច្ចការនិស្សិតដើម្បីបង់ប្រាក់។
            <span class="block mt-1 text-neutral-400/80 dark:text-neutral-600">Saving does not lock anything in. Confirming locks in what's checked above and cannot be undone here — please pay at the Student Affairs office afterward.</span>
        </p>
    </div>

    {{-- Confirmed --}}
    <div id="retakeConfirmedSection" class="hidden">
        <h2 class="flex items-center gap-2 text-sm font-bold text-neutral-700 dark:text-neutral-200 mb-3">
            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            បានបញ្ជាក់រួច (Already Confirmed)
        </h2>
        <div id="retakeConfirmedList" class="space-y-2"></div>
    </div>
</div>

@endsection

@push('modals')
{{-- Toast stack — same reasoning as state-exam's public pages: a `fixed`
     element nested inside <main>'s stacking context can't paint above the
     footer, so this lives outside it, in the layout's 'modals' stack. --}}
<div id="retakeToastStack" class="fixed top-5 right-5 z-[70] flex flex-col gap-2 w-[calc(100%-2.5rem)] max-w-sm"></div>
@endpush

@push('scripts')
    @vite(['resources/js/retake-exam-public/index.js'])
@endpush
