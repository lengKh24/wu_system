@extends('layouts.dashboard')
@section('title', 'Activity Log')
@section('content')

    <x-core.page-header title="កំណត់ត្រាសកម្មភាព (Activity Log)"
        subtitle="តាមដានថាអ្នកណាបានបង្កើត កែប្រែ ឬលុបអ្វី និងពេលណា (See who created, edited, or deleted what, and when)" />

    <div class="space-y-5">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="relative w-full lg:max-w-xs">
                <div class="absolute inset-y-0 left-0 flex items-center ps-3 pointer-events-none">
                    <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
                    </svg>
                </div>
                <input id="activitySearchInput" type="text" placeholder="Search description..."
                    class="block w-full p-2.5 ps-9 text-sm text-neutral-900 border border-neutral-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-neutral-900 dark:border-white/10 dark:placeholder-neutral-500 dark:text-white transition-all">
            </div>

            <select id="moduleFilter"
                class="w-full lg:w-48 px-3.5 py-2.5 text-sm bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">ម៉ូឌុលទាំងអស់ (All modules)</option>
            </select>

            <select id="userFilter"
                class="w-full lg:w-48 px-3.5 py-2.5 text-sm bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">បុគ្គលិកទាំងអស់ (All staff)</option>
            </select>

            <select id="eventFilter"
                class="w-full lg:w-40 px-3.5 py-2.5 text-sm bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">សកម្មភាពទាំងអស់ (All actions)</option>
                <option value="created">បង្កើត (Created)</option>
                <option value="updated">កែប្រែ (Updated)</option>
                <option value="deleted">លុប (Deleted)</option>
            </select>
        </div>

        <div id="loading-overlay" class="hidden py-10 items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>

        <div id="activity-list" class="space-y-2"></div>

        <div id="activity-pagination" class="flex items-center justify-between pt-2"></div>
    </div>

@endsection

@push('scripts')
    @vite(['resources/js/activity/index.js'])
@endpush
