<div x-data="sideMenu()" x-init="init()" x-cloak @keydown.escape.window="closeOnMobile()"
    @click.outside="closeOnMobile()">
    <header x-show="!isDesktop && !isOpen" x-transition
        class="fixed top-0 left-0 right-0 z-30 flex items-center justify-between h-16 px-4 transition-colors duration-300 bg-white border-b shadow-sm dark:bg-neutral-900 border-neutral-200 dark:border-white/10 sm:hidden">
        <div class="flex items-center space-x-3">
            <div
                class="flex items-center justify-center w-8 h-8 text-xs font-bold text-white rounded-lg bg-gradient-to-tr from-slate-900 to-indigo-950 dark:from-indigo-600 dark:to-violet-500">
                R
            </div>
            <span class="font-bold text-md text-slate-900 dark:text-white">Registrar</span>
        </div>

        <button @click="toggle()" type="button"
            class="p-2 transition-colors rounded-xl text-neutral-500 hover:bg-neutral-100 dark:hover:bg-white/5 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </header>

    <aside :class="isOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform duration-300 ease-in-out -translate-x-full"
        aria-label="Sidebar">

        <div
            class="flex flex-col justify-between h-full px-4 py-6 overflow-hidden transition-colors duration-300 bg-white shadow-2xl dark:bg-neutral-900 border-e border-neutral-200 dark:border-white/10">
            <div class="flex flex-col flex-1 min-h-0">
                <div class="flex items-center justify-between px-2 pb-2 mb-6 border-b border-transparent">
                    <div class="flex items-center cursor-pointer group">
                        <div
                            class="relative flex items-center justify-center overflow-hidden transition-all duration-500 ease-out border shadow-xl w-11 h-11 bg-gradient-to-tr from-slate-900 to-indigo-950 dark:from-indigo-600 dark:to-violet-500 rounded-xl shadow-indigo-500/10 dark:shadow-indigo-500/20 border-white/10 group-hover:scale-105 group-hover:shadow-indigo-500/30">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full animate-[shimmer_2.5s_infinite] ease-in-out">
                            </div>
                            <svg class="w-5 h-5 text-white transition-transform duration-500 ease-out transform group-hover:rotate-12"
                                fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <span
                                class="absolute bottom-1.5 right-1.5 w-1.5 h-1.5 bg-emerald-400 rounded-full animate-ping opacity-75"></span>
                            <span class="absolute bottom-1.5 right-1.5 w-1.5 h-1.5 bg-emerald-400 rounded-full"></span>
                        </div>

                        <div class="flex flex-col ms-3.5 tracking-tight">
                            <div class="flex items-center space-x-1">
                                <span
                                    class="text-xl font-extrabold transition-colors duration-300 text-slate-900 dark:text-white bg-clip-text">
                                    Registrar
                                </span>
                            </div>
                            <span
                                class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 -mt-0.5">
                                Official System
                            </span>
                        </div>
                    </div>

                    <button @click="toggle()" type="button"
                        class="p-2 transition-all rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 dark:hover:text-white dark:hover:bg-white/10">
                        <span class="sr-only">Close sidebar</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 pr-1 overflow-y-auto custom-scrollbar">
                    <ul class="space-y-2 font-medium">
                        <x-sidebar-link route="dashboard">
                            <x-slot name="icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </x-slot>
                            ទំព័រដើម
                        </x-sidebar-link>

                        @can('alert.view')
                        <li class="pt-4 pb-1">
                            <span
                                class="px-3 text-xs font-semibold tracking-wider uppercase text-neutral-400 dark:text-neutral-500">Notification</span>
                        </li>
                        <x-sidebar-link route="alert.index">
                            <x-slot name="icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                            </x-slot>
                            ការជូនដំណឹង (Alerts)
                        </x-sidebar-link>
                        @endcan

                        @can('state-exam.view')
                            <li class="pt-4 pb-1">

                                <span
                                    class="px-3 text-xs font-semibold tracking-wider uppercase text-neutral-400 dark:text-neutral-500">Exam</span>
                            </li>
                            <li x-data="{ open: {{ request()->routeIs('state-exam.*') ? 'true' : 'false' }} }">
                                <button type="button" @click="open = !open"
                                    class="flex items-center justify-between w-full px-3 py-2.5 rounded-xl transition-all duration-200 group
                                    {{ request()->routeIs('state-exam.*')
                                        ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 shadow-sm'
                                        : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-white/5 hover:text-neutral-900 dark:hover:text-white border border-transparent' }}">
                                    <span class="flex items-center">
                                        <span class="transition-transform duration-200 group-hover:scale-110">
                                            <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M3 8l9-5 9 5-9 5-9-5z" />
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M5 10v5c0 1.5 3.1 3 7 3s7-1.5 7-3v-5" />
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M19 10v6" />
                                            </svg>
                                        </span>
                                        <span class="ms-3 font-semibold">ប្រឡងបញ្ចប់ការសិក្សា (StateExam)</span>
                                    </span>
                                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200"
                                        :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <ul x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                    class="mt-1 ms-5 ps-4 border-l border-neutral-200 dark:border-white/10 space-y-1">

                                    <x-sidebar-link route="state-exam.index" :exact="true">
                                        <x-slot name="icon">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 8l9-5 9 5-9 5-9-5z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5 10v5c0 1.5 3.1 3 7 3s7-1.5 7-3v-5" />
                                            </svg>
                                        </x-slot>
                                        បន្ទប់ប្រឡង (Exam Rooms)
                                    </x-sidebar-link>

                                    <x-sidebar-link route="state-exam.report" :exact="true">
                                        <x-slot name="icon">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                            </svg>
                                        </x-slot>
                                        របាយការណ៍ (Report)
                                    </x-sidebar-link>

                                    <x-sidebar-link route="state-exam.attendance.index" :exact="true">
                                        <x-slot name="icon">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </x-slot>
                                        អវត្តមានប្រឡង (Attendance)
                                    </x-sidebar-link>

                                    <x-sidebar-link route="state-exam.invigilators.index" :exact="true">
                                        <x-slot name="icon">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                            </svg>
                                        </x-slot>
                                        អនុរក្ស (Invigilator)
                                    </x-sidebar-link>
                                </ul>
                            </li>
                        @endcan

                        @can('certificate.view')
                            <li class="pt-4 pb-1">
                                <span
                                    class="px-3 text-xs font-semibold tracking-wider uppercase text-neutral-400 dark:text-neutral-500">Certificate</span>

                            </li>
                            <x-sidebar-link route="certificate.index">
                                <x-slot name="icon">
                                    <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd"
                                            d="M7 2a2 2 0 0 0-2 2v1a1 1 0 0 0 0 2v1a1 1 0 0 0 0 2v1a1 1 0 1 0 0 2v1a1 1 0 1 0 0 2v1a1 1 0 1 0 0 2v1a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H7Zm3 8a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm-1 7a3 3 0 0 1 3-3h2a3 3 0 0 1 3 3 1 1 0 0 1-1 1h-6a1 1 0 0 1-1-1Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </x-slot>
                                បណ្ដោះអាសន្ន (Probisional)
                            </x-sidebar-link>
                        @endcan

                        @can('student.view')
                            <li class="pt-4 pb-1">
                                <span
                                    class="px-3 text-xs font-semibold tracking-wider uppercase text-neutral-400 dark:text-neutral-500">Statistic</span>
                            </li>
                            <x-sidebar-link route="student.index">
                                <x-slot name="icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </x-slot>
                                និស្សិត (Student)
                            </x-sidebar-link>
                        @endcan

                        @canany(['faculty.view', 'major.view', 'batch.view', 'shift.view', 'group.view',
                            'app-status.view'])
                            <li class="pt-4 pb-1">
                                <span
                                    class="px-3 text-xs font-semibold tracking-wider uppercase text-neutral-400 dark:text-neutral-500">Academics</span>
                            </li>
                        @endcanany

                        @can('faculty.view')
                            <x-sidebar-link route="faculty.index">
                                <x-slot name="icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </x-slot>
                                មហាវិទ្យាល័យ (Faculty)
                            </x-sidebar-link>
                        @endcan

                        @can('major.view')
                            <x-sidebar-link route="major.index">
                                <x-slot name="icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </x-slot>
                                ជំនាញ (Major)
                            </x-sidebar-link>
                        @endcan

                        @can('batch.view')
                            <x-sidebar-link route="batch.index">
                                <x-slot name="icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 14l9-5-9-5-9 5 9 5z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                    </svg>
                                </x-slot>
                                ជំនាន់ (Batch)
                            </x-sidebar-link>
                        @endcan

                        @can('shift.view')
                            <x-sidebar-link route="shift.index">
                                <x-slot name="icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </x-slot>
                                វេន (Shift)
                            </x-sidebar-link>
                        @endcan

                        @can('group.view')
                            <x-sidebar-link route="group.index">
                                <x-slot name="icon">
                                    <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                        viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                            d="M4.5 17H4a1 1 0 0 1-1-1 3 3 0 0 1 3-3h1m0-3.05A2.5 2.5 0 1 1 9 5.5M19.5 17h.5a1 1 0 0 0 1-1 3 3 0 0 0-3-3h-1m0-3.05a2.5 2.5 0 1 0-2-4.45m.5 13.5h-7a1 1 0 0 1-1-1 3 3 0 0 1 3-3h3a3 3 0 0 1 3 3 1 1 0 0 1-1 1Zm-1-9.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z" />
                                    </svg>
                                </x-slot>
                                ក្រុមសិក្សា (Group)
                            </x-sidebar-link>
                        @endcan

                        @can('app-status.view')
                            <x-sidebar-link route="app-status.index">
                                <x-slot name="icon">
                                    <svg class="w-[19px] h-[19px] text-gray-800 dark:text-white" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                        viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-width="2"
                                            d="M11.083 5.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.556 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.03 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 20.8c-.764.446-1.688-.248-1.474-1.106l1.03-4.119A1 1 0 0 0 6.8 14.56l-3.33-2.723c-.698-.571-.342-1.702.557-1.771l4.462-.342a1 1 0 0 0 .84-.597l1.753-4.022Z" />
                                    </svg>


                                </x-slot>
                                ស្ថានភាព (Status)
                            </x-sidebar-link>
                        @endcan

                        {{-- @can('state-exam.view')
                        <x-ui.side-link title="Sample" :options="[
                            ['text' => 'Exam Schedule', 'icon' => 'calendar-check', 'route' => 'exam.schedule'],
                            // ['text' => 'Student', 'icon' => 'user', 'route' => 'student.index'],
                        ]" />
                        @endcan --}}

                        @can('role.view')
                            <li class="pt-4 pb-1">
                                <span
                                    class="px-3 text-xs font-semibold tracking-wider uppercase text-neutral-400 dark:text-neutral-500">Administration</span>
                            </li>
                            <x-sidebar-link route="role.index">
                                <x-slot name="icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12a3 3 0 100-6 3 3 0 000 6zM17.804 21c.512-.75.79-1.638.79-2.556C18.594 15.36 15.964 13 12.75 13H12a4.5 4.5 0 00-4.5 4.5c0 .918.278 1.806.79 2.556M15 6a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </x-slot>
                                តួនាទី និងសិទ្ធិ (Roles & Permissions)
                            </x-sidebar-link>
                        @endcan

                        @can('activity.view')
                            <x-sidebar-link route="activity.index">
                                <x-slot name="icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </x-slot>
                                កំណត់ត្រាសកម្មភាព (Activity Log)
                            </x-sidebar-link>
                        @endcan

                        @can('role.view')
                            <x-sidebar-link route="register">
                                <x-slot name="icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                                    </svg>
                                </x-slot>
                                បង្កើតគណនីថ្មី (Create Account)
                            </x-sidebar-link>
                        @endcan

                    </ul>
                </div>
            </div>

            <div class="pt-4 mt-2 space-y-4 border-t border-neutral-200 dark:border-white/10">
                <!-- Theme Toggle Button -->
                <button @click="toggleTheme()"
                    class="flex items-center justify-between w-full px-4 py-2.5 bg-neutral-100 dark:bg-white/5 hover:bg-neutral-200 dark:hover:bg-white/10 rounded-xl transition-all duration-300 group">
                    <div class="flex items-center">
                        <!-- Moon Icon (Visible in Light Mode, hidden in Dark Mode) -->
                        <svg id="theme-icon-moon"
                            class="w-5 h-5 text-indigo-600 transition-transform dark:hidden group-hover:-rotate-12"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>

                        <!-- Sun Icon (Hidden in Light Mode, visible in Dark Mode) -->
                        <svg id="theme-icon-sun"
                            class="hidden w-5 h-5 transition-transform text-amber-500 dark:block group-hover:rotate-45"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                        </svg>

                        <span class="text-sm font-medium ms-3 text-neutral-700 dark:text-neutral-300">
                            <span class="dark:hidden">ងងឹត (Dark)</span>
                            <span class="hidden dark:inline">ភ្លឺ (Light)</span>
                        </span>
                    </div>

                    <!-- Status Dot indicator -->
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 dark:bg-amber-500 shadow-sm"></span>
                </button>

                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                    @csrf
                    <button type="submit"
                        class="flex items-center w-full px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all duration-200 group">
                        <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="ms-3">ចាកចេញ (Logout)</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <button x-show="isDesktop && !isOpen" @click="toggle()" type="button" style="display: none;"
        class="fixed left-0 z-30 items-center justify-center hidden w-6 transition-all -translate-y-1/2 bg-white border shadow-md sm:flex top-1/2 h-14 dark:bg-neutral-900 border-neutral-200 dark:border-white/10 rounded-r-xl text-neutral-500 hover:text-indigo-600 hover:w-7">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
    </button>
</div>
