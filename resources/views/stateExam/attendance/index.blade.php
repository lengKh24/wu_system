@extends('layouts.state.public')
@section('title', 'ការចូលរួមប្រឡង (Exam Attendance)')
@section('content')

<div class="text-center mb-14 fade-up">
    <!-- University logo — drop the real file at public/images/logo.png and this fills in automatically -->
    <div class="mx-auto mb-5 flex items-center justify-center w-40 h-40 rounded-2xl bg-white/80 dark:bg-white/5 backdrop-blur-sm border border-neutral-200/80 dark:border-white/10 shadow-md p-2.5">
        <img src="{{ asset('images/logo.png') }}" alt="Western University logo"
             class="w-full h-full object-contain"
             onerror="this.style.display='none'">
    </div>

    <div class="inline-flex items-center gap-2 px-3 py-1 mb-5 text-[11px] font-bold tracking-widest uppercase rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-200/70 dark:border-indigo-500/20">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        Official Attendance System
    </div>
    <h1 class="font-display text-3xl sm:text-4xl font-bold tracking-tight">ជ្រើសរើសសម័យប្រឡង</h1>
    <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400 max-w-md mx-auto">
        ជ្រើសរើសម៉ោងប្រឡងដើម្បីបញ្ចូលអវត្តមាន
        <span class="block text-xs text-neutral-400 dark:text-neutral-500 mt-0.5">Select a session below to record attendance for that time slot</span>
    </p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
    @foreach ($rounds as $i => $label)
        <a href="{{ route('state-exam.attendance.search', $i + 1) }}"
           style="animation-delay: {{ 120 + $i * 130 }}ms"
           class="fade-up group relative overflow-hidden p-8 text-center bg-white/80 dark:bg-neutral-900/70 backdrop-blur-sm border border-neutral-200/80 dark:border-white/10 rounded-3xl shadow-sm hover:shadow-2xl hover:shadow-indigo-500/10 hover:border-indigo-400/60 dark:hover:border-indigo-500/40 hover:-translate-y-1.5 transition-all duration-500">

            <!-- Corner shine sweep on hover -->
            <span class="pointer-events-none absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 bg-gradient-to-br from-indigo-500/[0.06] via-transparent to-amber-400/[0.06]"></span>

            <!-- Session number watermark -->
            <span class="pointer-events-none absolute -top-3 -right-2 font-display text-7xl font-bold text-neutral-900/[0.04] dark:text-white/[0.05] select-none">
                {{ $i + 1 }}
            </span>

            <div class="relative">
                <div class="mx-auto mb-5 flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-800 text-white shadow-lg shadow-indigo-500/25 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div class="text-xl font-bold text-neutral-900 dark:text-white">{{ $label }}</div>
                <div class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-widest text-amber-600 dark:text-amber-400">
                    Session {{ $i + 1 }}
                </div>

                <div class="mt-5 pt-5 border-t border-neutral-100 dark:border-white/5 flex items-center justify-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 opacity-0 group-hover:opacity-100 translate-y-1 group-hover:translate-y-0 transition-all duration-300">
                    ចូលបញ្ចូលទិន្នន័យ (Enter data)
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </div>
            </div>
        </a>
    @endforeach
</div>

@endsection
