<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
        if (localStorage.theme === 'dark' ||
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">

    <title>@yield('title', 'Exam Attendance')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @ddfsnStyles

    <style>
        :root {
            --gold: 38 92% 50%;
        }

        body {
            font-family: 'Kantumruy Pro', system-ui, sans-serif;
        }

        .font-display {
            font-family: 'Playfair Display', 'Kantumruy Pro', serif;
        }

        /* ---------- Formal background: infinite drifting curve pattern + soft glows ---------- */
        .state-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            background-color: #f8fafc;
        }

        html.dark .state-bg { background-color: #05070f; }

        .state-waves {
            position: absolute;
            inset: -20% -10%;
            background-repeat: repeat-x;
            background-size: 480px 100%;
            will-change: background-position;
        }

        .state-waves.layer-1 {
            top: 4%;
            height: 46%;
            opacity: .5;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 480 220'%3E%3Cpath d='M0 130 C 60 70, 120 190, 180 130 S 300 70, 360 130 S 480 190, 480 130' fill='none' stroke='%234f46e5' stroke-opacity='0.16' stroke-width='1.6'/%3E%3C/svg%3E");
            animation: waveDriftA 46s linear infinite;
        }

        .state-waves.layer-2 {
            top: 34%;
            height: 46%;
            opacity: .4;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 480 220'%3E%3Cpath d='M0 100 C 60 160, 120 40, 180 100 S 300 160, 360 100 S 480 40, 480 100' fill='none' stroke='%23f59e0b' stroke-opacity='0.14' stroke-width='1.4'/%3E%3C/svg%3E");
            animation: waveDriftB 60s linear infinite;
        }

        .state-waves.layer-3 {
            top: 62%;
            height: 46%;
            opacity: .3;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 480 220'%3E%3Cpath d='M0 130 C 60 70, 120 190, 180 130 S 300 70, 360 130 S 480 190, 480 130' fill='none' stroke='%236366f1' stroke-opacity='0.12' stroke-width='1.2'/%3E%3C/svg%3E");
            animation: waveDriftA 75s linear infinite reverse;
        }

        @keyframes waveDriftA {
            from { background-position-x: 0; }
            to   { background-position-x: -480px; }
        }

        @keyframes waveDriftB {
            from { background-position-x: 0; }
            to   { background-position-x: 480px; }
        }

        .state-glow {
            position: absolute;
            border-radius: 9999px;
            filter: blur(100px);
            opacity: .28;
            animation: driftGlow 24s ease-in-out infinite;
        }

        .state-glow.gold {
            width: 28rem; height: 28rem;
            top: -12%; right: -8%;
            background: radial-gradient(circle, hsla(var(--gold), .5), transparent 70%);
            animation-delay: -6s;
        }

        .state-glow.indigo {
            width: 32rem; height: 32rem;
            bottom: -16%; left: -10%;
            background: radial-gradient(circle, rgba(79,70,229,.45), transparent 70%);
        }

        @keyframes driftGlow {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(4%, 3%) scale(1.08); }
        }

        @media (prefers-reduced-motion: reduce) {
            .state-glow, .state-waves { animation: none; }
        }

        /* ---------- Entrance animations ---------- */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .fade-up {
            animation: fadeUp .6s cubic-bezier(.16,1,.3,1) both;
        }

        /* ---------- Scroll-reveal (fade + zoom in) — plain IntersectionObserver, no library ---------- */
        .scroll-reveal {
            opacity: 0;
            transform: scale(.75);
            transition: opacity 1.2s cubic-bezier(.16,1,.3,1), transform 1.2s cubic-bezier(.16,1,.3,1);
        }

        .scroll-reveal.in-view {
            opacity: 1;
            transform: scale(1);
        }

        @media (prefers-reduced-motion: reduce) {
            .scroll-reveal { opacity: 1; transform: none; transition: none; }
        }

        /* ---------- Hover brightness — logos, icons, and links light up on hover so
           they're easier to pick out, especially against the dark-mode background ---------- */
        .hover-brighten {
            transition: filter .25s ease-out;
        }

        .hover-brighten:hover {
            filter: brightness(1.25);
        }

        html.dark .hover-brighten:hover {
            filter: brightness(1.5);
        }
    </style>
</head>

<body class="relative min-h-screen text-neutral-900 dark:text-white transition-colors">

    <div class="state-bg">
        <div class="state-waves layer-1"></div>
        <div class="state-waves layer-2"></div>
        <div class="state-waves layer-3"></div>
        <div class="state-glow gold"></div>
        <div class="state-glow indigo"></div>
    </div>

    <!-- Official header bar -->
    <header class="relative z-10 border-b border-neutral-200/70 dark:border-white/10 bg-white/70 dark:bg-neutral-950/60 backdrop-blur-xl">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3 hover-brighten">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-800 text-white shadow-lg shadow-indigo-500/30 shrink-0">
                      <!-- University logo — drop the real file at public/images/logo.png and this fills in automatically -->
                <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-xl bg-white/80 dark:bg-white/5 backdrop-blur-sm border border-neutral-200/80 dark:border-white/10 shadow-sm overflow-hidden p-1">
                    <img src="{{ asset('images/logo.png') }}" alt="Western University logo"
                         class="w-full h-full object-contain"
                         onerror="this.style.display='none'">
                </div>
                </div>
                <div class="leading-tight">
                    <div class="text-sm font-bold tracking-wide">សាកលវិទ្យាល័យវេស្ទើន</div>
                    <div class="text-[11px] font-medium uppercase tracking-[0.14em] text-amber-600 dark:text-amber-400">Western University &middot; Official Exam Portal</div>
                </div>
            </div>

            <button onclick="toggleDarkMode()" title="Toggle light / dark mode"
                class="hover-brighten flex items-center justify-center w-10 h-10 bg-white dark:bg-white/5 border border-neutral-200 dark:border-white/10 rounded-xl shadow-sm hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-500/40 transition-all">
                <svg class="hidden w-4.5 h-4.5 text-amber-500 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                </svg>
                <svg class="w-4.5 h-4.5 text-indigo-600 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
        </div>
    </header>

    <main id="geoContent" class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        @yield('content')
    </main>

    <footer id="geoFooter" class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 pb-10 text-center">
        <p class="text-[11px] tracking-wide text-neutral-400 dark:text-neutral-600">
            &copy; {{ date('Y') }} Western University &middot; Official Exam Attendance Portal &middot; Authorized on-site staff use only
        </p>
    </footer>

    <footer id="roumdoulFooter" pAnimateOnScroll enterClass="animate-enter fade-in-10 zoom-in-75 animate-duration-1200"
        class="scroll-reveal mt-20 px-4 mb-32">
        <div class="flex flex-col items-center justify-center space-y-3">

            <div class="text-center group transition-all duration-500 hover:scale-105">
                <h3 class="text-gray-800 dark:text-neutral-400 tracking-[0.4em] text-[10px] uppercase mb-4">
                    Premium Services
                </h3>
                <a href="https://t.me/roumdol_invite" target="_blank"
                    class="hover-brighten mx-auto mb-3 flex items-center justify-center w-16 h-16 rounded-2xl bg-white dark:bg-white/5 border border-neutral-200/80 dark:border-white/10 shadow-md shadow-pink-900/10 p-2 transition-transform duration-500 group-hover:scale-105">
                    <img src="{{ asset('images/Roumdoul_Logo.png') }}" alt="Roumdoul logo" class="w-full h-full object-contain">
                </a>
                <div class="flex flex-col items-center">
                    <span class="my-3 text-3xl text-pink-900 dark:text-pink-400 font-bold">
                        <a href="https://t.me/roumdol_invite" target="_blank" class="hover-brighten hover:text-pink-700 dark:hover:text-pink-300 transition-colors">
                            រំដួល </a>
                    </span>
                    <span class="text-lg font-medium text-pink-900 dark:text-pink-400 tracking-widest uppercase">
                        <a href="https://t.me/roumdol_invite" target="_blank" class="hover-brighten hover:text-pink-700 dark:hover:text-pink-300 transition-colors">
                            ROUMDOUL </a>
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-6 py-6">
                <a href="https://www.facebook.com/share/1AoKyVb4t3/?mibextid=wwXIfr" target="_blank"
                    class="hover-brighten flex items-center justify-center w-10 h-10 rounded-full dark:bg-white/5 transition-all duration-300 opacity-60 hover:opacity-100 hover:-translate-y-1 hover:scale-110">
                    <img src="https://img.icons8.com/material-rounded/48/831843/facebook-f.png" class="w-6 h-6"
                        alt="Facebook">
                </a>
                <a href="https://www.instagram.com/roumd_oul" target="_blank"
                    class="hover-brighten flex items-center justify-center w-10 h-10 rounded-full dark:bg-white/5 transition-all duration-300 opacity-60 hover:opacity-100 hover:-translate-y-1 hover:scale-110">
                    <img src="https://img.icons8.com/material-rounded/48/831843/instagram-new.png" class="w-6 h-6"
                        alt="Instagram">
                </a>
                <a href="https://www.tiktok.com/@roum_doul?_r=1&_t=ZS-92Nr8NVeJhE" target="_blank"
                    class="hover-brighten flex items-center justify-center w-10 h-10 rounded-full dark:bg-white/5 transition-all duration-300 opacity-60 hover:opacity-100 hover:-translate-y-1 hover:scale-110">
                    <img src="https://img.icons8.com/material-rounded/48/831843/tiktok.png" class="w-6 h-6" alt="TikTok">
                </a>
                <a href="https://t.me/roumdoul_official" target="_blank"
                    class="hover-brighten flex items-center justify-center w-10 h-10 rounded-full dark:bg-white/5 transition-all duration-300 opacity-60 hover:opacity-100 hover:-translate-y-1 hover:scale-110">
                    <img src="https://img.icons8.com/material-rounded/48/831843/telegram-app.png" class="w-6 h-6"
                        alt="Telegram">
                </a>
            </div>

            <div class="text-center">
                <p class="text-[14px] text-gray-800 dark:text-neutral-300 uppercase tracking-tighter leading-relaxed">
                    (+855) 71 260 0078 <br />
                    (+855) 15 57 87 07
                </p>
            </div>

            <p class="text-[9px] text-gray-700 dark:text-neutral-500 uppercase mt-5 tracking-tighter">
                All Rights Reserved
            </p>
        </div>
    </footer>

    {{-- Page-level modals render here — a direct child of <body>, outside
         <main>'s stacking context, so `position: fixed` + z-index on a modal
         actually paints above the footer instead of being trapped under it. --}}
    @stack('modals')

    <script>
        window.addEventListener('load', function () {
            var start = function () {
                var el = document.getElementById('roumdoulFooter');
                if (!el || el.dataset.revealBound) return;
                el.dataset.revealBound = '1';

                if (!('IntersectionObserver' in window)) {
                    el.classList.add('in-view');
                    return;
                }

                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('in-view');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15 });

                observer.observe(el);
            };

            // Wait for webfonts too — a late font swap can shift layout
            // enough to falsely trigger (or miss) the intersection check.
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(start);
            } else {
                start();
            }
        });
    </script>

    @stack('scripts')
</body>

</html>
