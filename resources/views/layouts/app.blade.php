<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Video Library') }}</title>


    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.lordicon.com/lordicon.js"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        @if (file_exists(public_path('css/app.css')))
            <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        @endif
        @if (file_exists(public_path('js/app.js')))
            <script src="{{ asset('js/app.js') }}" defer></script>
        @endif
    @endif
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

</head>

<body class="font-sans antialiased">
    <!-- Start Expiry pop up -->
    @php
        $expiryDate = \Carbon\Carbon::parse(config('app.expiry_date'));
        $now = \Carbon\Carbon::now();
        $remainingSeconds = $now->diffInSeconds($expiryDate, false);
    @endphp

    @if (auth()->check() && auth()->user()->is_admin)
        <div id="expiryModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md text-center border dark:border-gray-700">
                <h2 class="text-xl font-bold text-red-600 dark:text-red-400 mb-4">
                    ⏳ System Expiry Warning
                </h2>
                <p class="mb-4 text-gray-700 dark:text-gray-200">
                    You have
                    <span id="expiryCountdown" class="font-bold text-green-600 dark:text-green-400"></span>
                    left.<br>
                    To continue using this system beyond that period,<br>
                    contact <strong>Wan Tech Solutions</strong> on:<br>
                    <a href="tel:+233207006661" class="text-blue-600 dark:text-blue-400 hover:underline">
                        +233207006661
                    </a>.
                </p>
                <button onclick="closeExpiryModal()"
                    class="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2 px-4 rounded transition">
                    Close
                </button>
            </div>
        </div>

        <script>
            function closeExpiryModal() {
                document.getElementById('expiryModal').style.display = 'none';
                document.getElementById('navbarCountdown')?.classList.remove('hidden');
            }

            let remaining = {{ $remainingSeconds }};
            const expiryCountdown = document.getElementById('expiryCountdown');
            const navbarCountdown = document.getElementById('navbarCountdown');

            function updateCountdownDisplay() {
                if (remaining <= 0) return;
                const d = Math.floor(remaining / (3600 * 24));
                const h = Math.floor((remaining % (3600 * 24)) / 3600);
                const m = Math.floor((remaining % 3600) / 60);
                const s = remaining % 60;

                const countdownText = `${d}d ${h}h ${m}m ${s}s`;
                expiryCountdown.innerText = countdownText;
                if (navbarCountdown) navbarCountdown.innerText = countdownText;
                remaining--;
            }

            updateCountdownDisplay();
            setInterval(updateCountdownDisplay, 1000);
        </script>
    @endif

    <!-- End of Expiry Pop Up -->

    <div id="successToast"
        class="hidden fixed top-6 right-6 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-500">
        ✅ User updated successfully!
    </div>

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main class="py-4">
            <div class="container">
                @yield('content')
            </div>
        </main>
    </div>
    <!-- For toggling Dark and Light modes -->
    <script>
        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const formatDuration = (totalSeconds) => {
                if (!Number.isFinite(totalSeconds)) {
                    return '--:--';
                }
                const seconds = Math.max(0, Math.floor(totalSeconds));
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const remainder = seconds % 60;

                if (hours > 0) {
                    return `${hours}:${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
                }

                return `${minutes}:${String(remainder).padStart(2, '0')}`;
            };

            document.querySelectorAll('video[data-display-duration]').forEach((video) => {
                const container = video.closest('[data-video-container]');
                if (!container) {
                    return;
                }

                const badge = container.querySelector('[data-video-duration]');
                if (!badge) {
                    return;
                }

                const setDuration = () => {
                    if (Number.isFinite(video.duration) && video.duration > 0) {
                        badge.textContent = formatDuration(video.duration);
                    }
                };

                if (video.readyState >= 1) {
                    setDuration();
                } else {
                    video.addEventListener('loadedmetadata', setDuration, { once: true });
                }

                const showBadge = () => {
                    badge.style.opacity = '1';
                };

                const hideBadge = () => {
                    badge.style.opacity = '';
                };

                video.addEventListener('play', showBadge);
                video.addEventListener('pause', () => {
                    if (video.paused) {
                        hideBadge();
                    }
                });
                video.addEventListener('ended', hideBadge);

                container.addEventListener('mouseenter', showBadge);
                container.addEventListener('mouseleave', () => {
                    if (video.paused) {
                        hideBadge();
                    }
                });
            });
        });
    </script>
    @stack('scripts')
    @include('partials.footer')

    <!-- Success Toast message -->
    <script>
        function showToast(message = '✅ Update successful!') {
            const toast = document.getElementById('successToast');
            toast.textContent = message;
            toast.classList.remove('hidden', 'opacity-0');
            toast.classList.add('opacity-100');

            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => {
                    toast.classList.add('hidden');
                }, 300);
            }, 3000);
        }
    </script>
</body>

</html>

