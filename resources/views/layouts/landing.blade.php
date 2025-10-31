<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name', 'Video Library') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.lordicon.com/lordicon.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100">
    <main class="py-8">
        <div class="container mx-auto px-4">
            @yield('content')
        </div>
    </main>

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
</body>

</html>
