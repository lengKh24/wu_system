@extends('layouts.dashboard')
@section('title', 'Faculties')
@section('content')

    <x-core.page-header title="គ្រប់គ្រងមហាវិទ្យាល័យ" />
    <div class="space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="relative w-full md:w-96 group">
                <div class="absolute inset-y-0 left-0 flex items-center ps-3 pointer-events-none">
                    <svg class="w-4 h-4 text-neutral-500 group-focus-within:text-indigo-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
                    </svg>
                </div>

                <form id="facultySearchForm" method="GET" action="{{ route('faculty.index') }}"
                    class="relative w-full md:w-96 group">
                    <div class="absolute inset-y-0 left-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-neutral-500 group-focus-within:text-indigo-500 transition-colors"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
                        </svg>
                    </div>

                    <input id="facultySearchInput" type="text" name="search" value="{{ request('search') }}"
                        class="block w-full p-2.5 ps-10 text-sm text-neutral-900 border border-neutral-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-neutral-900 dark:border-white/10 dark:placeholder-neutral-400 dark:text-white transition-all"
                        placeholder="Search Invoice number, type, status..." autocomplete="off" />
                </form>

            </div>
            <!-- Button -->
            <div class="flex items-center gap-2">
                <!-- <button type="button" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-neutral-700 bg-white border border-neutral-200 rounded-xl hover:bg-neutral-50 dark:bg-neutral-900 dark:text-neutral-300 dark:border-white/10 dark:hover:bg-white/5 transition-all">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v2.586a1 1 0 0 1-.293.707l-6.414 6.414a1 1 0 0 0-.293.707V17l-4 4v-6.586a1 1 0 0 0-.293-.707L3.293 7.293A1 1 0 0 1 3 6.586V4Z"/></svg>
                    Filters
                </button> -->
                <button type="button" id="facultyImportBtn"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                    <svg class="w-4 h-4 mr-2 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    នាំចូល (Import)
                </button>
                <button type="button" id="facultyExportBtn" title="Export the current filtered list to Excel"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                    <svg class="w-4 h-4 mr-2 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    នាំចេញ (Export)
                </button>
                <button type="button" onclick="AppModal.toggle(true)"
                class="inline-flex items-center px-4 py-2.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all active:scale-95">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Create New Faculty
            </button>
                @can('faculty.delete')
                <button type="button" id="facultyDestroyAllBtn" title="Permanently delete ALL faculties — cannot be undone"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-500/20 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    លុបទាំងអស់ (Destroy All)
                </button>
                @endcan

            </div>
        </div>

            <div
                class="relative overflow-hidden bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-2xl shadow-sm transition-colors duration-300">

                <div id="loading-overlay"
                    class="hidden absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-neutral-900/50 backdrop-blur-[2px]">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
                </div>

                    <div class="w-full max-h-[600px] overflow-y-auto overflow-x-hidden md:overflow-x-auto scrollbar-thin scrollbar-thumb-neutral-200 dark:scrollbar-thumb-white/10">
            <!-- Changed to a grid block on mobile, switches back to regular table layouts on desktop (md:) -->
            <table class="w-full text-sm text-left text-neutral-500 dark:text-neutral-400 border-collapse block md:table">
                <!-- Hidden on small screens, shown as proper table-header-group on desktop -->
                <thead class="hidden md:table-header-group sticky top-0 z-20 text-xs text-neutral-700 uppercase bg-neutral-50 dark:bg-neutral-800/50 dark:text-neutral-300 backdrop-blur-md">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-bold tracking-wider">
                            <div class="flex items-center cursor-pointer group hover:text-indigo-600 transition-colors">
                                N.O
                                <svg class="w-3 h-3 ms-1.5 opacity-0 group-hover:opacity-100 transition-opacity" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.574 11.024h6.852a2.075 2.075 0 0 0 1.847-1.086 1.9 1.9 0 0 0-.11-1.986L13.736 2.9a2.122 2.122 0 0 0-3.472 0L6.837 7.952a1.9 1.9 0 0 0-.11 1.986 2.074 2.074 0 0 0 1.847 1.086Z" />
                                </svg>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-4">Name Khmer</th>
                        <th scope="col" class="px-6 py-4">Name English</th>
                        <th scope="col" class="px-6 py-4">Shortcut</th>
                        <th scope="col" class="px-6 py-4">Remark</th>
                        <th scope="col" class="px-6 py-4">Create At</th>
                        <th scope="col" class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <!-- tbody acts as a flexible grid on mobile screens -->
                <tbody id="faculty-table-body" class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 md:p-0 md:table-row-group md:divide-y md:divide-neutral-200 md:dark:divide-white/5">
                    <tr class="block md:table-row text-center py-10">
                        <td class="block md:table-cell p-6 text-neutral-500">
                            <span>Loading data...</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="pagination-container" class="px-6 py-4 border-t border-neutral-200 dark:border-white/5"></div>

        </div>
    </div>
    </div>

    @include('faculty.partials.facultyModal')
    @include('faculty.partials.importModal')
    @endsection

@push('scripts')
  @vite(['resources/js/faculty/faculty.js'])
@endpush
