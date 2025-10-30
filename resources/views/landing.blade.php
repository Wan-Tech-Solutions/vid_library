@extends('layouts.landing')

@section('content')
    @php
        $totalVideos = ($latest ? 1 : 0) + $others->count();
        $publishedVideos = ($latest && $latest->is_published ? 1 : 0) + $others->where('is_published', true)->count();
        $categories = ['Highlights', 'Tutorials', 'Events', 'Behind the Scenes', 'Announcements', 'Motivation'];
    @endphp

    {{-- HERO --}}
    <section class="relative bg-slate-950 text-white">
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-slate-950/80"></div>
            <img src="https://images.unsplash.com/photo-1525182008055-f88b95ff7980?auto=format&fit=crop&w=1600&q=80"
                alt="" class="w-full h-full object-cover opacity-60">
        </div>

        <div class="relative max-w-6xl mx-auto px-4 py-16 lg:py-20">
            <div class="grid lg:grid-cols-2 gap-10 items-center">
                <div class="space-y-6">
                    <span class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-white/10 text-sm tracking-wide">
                        <span class="text-lg">🎬</span> Fitness Video Channel
                    </span>
                    <h1 class="text-4xl lg:text-5xl font-bold leading-tight">
                        Watch and share impactful tutorials from our best instructors.
                    </h1>
                    <p class="text-slate-200 text-lg">
                        Discover fresh releases, binge timeless favourites, and get inspired by people pushing ideas
                        forward. Our library updates weekly—there is always something new to explore.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="col-span-2 bg-white/10 rounded-2xl p-5 border border-white/10 backdrop-blur">
                        <p class="text-xs uppercase tracking-wide text-white/70 mb-2">Library at a glance</p>
                        <div class="flex flex-wrap gap-6">
                            <div>
                                <p class="text-3xl font-bold">{{ $totalVideos }}</p>
                                <p class="text-xs uppercase text-white/60">Total videos</p>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold">{{ $publishedVideos }}</p>
                                <p class="text-xs uppercase text-white/60">Published</p>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold">{{ $others->where('created_at', '>=', now()->subWeek())->count() }}</p>
                                <p class="text-xs uppercase text-white/60">New this week</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                        <p class="text-xs uppercase text-white/70">Community</p>
                        <p class="text-lg font-semibold mt-1">Featured Categories</p>
                        <div class="mt-4 flex -space-x-3">
                            <span class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center text-lg">🎥</span>
                            <span class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center text-lg">📸</span>
                            <span class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center text-lg">🎤</span>
                        </div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                        <p class="text-xs uppercase text-white/70">Stay in the loop</p>
                        <p class="text-lg font-semibold mt-1">Weekly releases</p>
                        <p class="text-xs text-white/70 mt-3">
                            New series drop every week. Subscribe to notifications to never miss a moment.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- MAIN CONTENT --}}
    <div class="max-w-6xl mx-auto px-4 py-12 space-y-16">
        {{-- Latest Release --}}
        <section class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Latest Release</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">This week’s spotlight from our editorial team.</p>
                </div>
                <div class="flex flex-wrap gap-2 text-sm">
                    @foreach ($categories as $category)
                        <span
                            class="px-3 py-1 rounded-full border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-300">
                            {{ $category }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    @if ($latest)
                        @php
                            $latestThumb = media_url($latest->thumbnail_path);
                            $latestVideo = media_url($latest->video_path);
                        @endphp
                        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-md overflow-hidden">
                            <div class="bg-slate-100 dark:bg-slate-800 aspect-video">
                                @if ($latestVideo)
                                    <video controls preload="metadata"
                                        class="w-full h-full object-cover"
                                        @if ($latestThumb) poster="{{ $latestThumb }}" @endif>
                                        <source src="{{ $latestVideo }}" type="video/mp4">
                                    </video>
                                @elseif ($latestThumb)
                                    <img src="{{ $latestThumb }}" alt="{{ $latest->title }}"
                                        class="w-full h-full object-cover"
                                        loading="lazy">
                                @else
                                    <div class="flex items-center justify-center h-full text-slate-500 dark:text-slate-300">
                                        No media available.
                                    </div>
                                @endif
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center gap-3 text-xs text-slate-400">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 text-slate-600">
                                        New
                                    </span>
                                    <span>{{ $latest->created_at->diffForHumans() }}</span>
                                </div>
                                <h3 class="text-xl font-semibold text-slate-900 dark:text-white">
                                    {{ $latest->title }}
                                </h3>
                                <p class="text-slate-600 dark:text-slate-300">
                                    {{ $latest->description }}
                                </p>
                                @include('partials.share-buttons', ['video' => $latest])
                            </div>
                        </div>
                    @else
                        <div
                            class="bg-white dark:bg-slate-900 rounded-2xl shadow-md p-10 flex items-center justify-center text-slate-500 dark:text-slate-300">
                            No featured video yet. Check back soon!
                        </div>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-md p-5">
                        <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Up Next</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            A quick look at other videos trending this week.
                        </p>
                        <div class="mt-4 space-y-4">
                            @forelse ($others->take(4) as $video)
                                @php
                                    $thumb = media_url($video->thumbnail_path);
                                    $videoUrl = media_url($video->video_path);
                                @endphp
                                <div class="flex gap-3">
                                    <div class="w-20 h-14 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 shrink-0">
                                        @if ($videoUrl)
                                            <video preload="metadata" class="w-full h-full object-cover"
                                                @if ($thumb) poster="{{ $thumb }}" @endif>
                                                <source src="{{ $videoUrl }}" type="video/mp4">
                                            </video>
                                        @elseif ($thumb)
                                            <img src="{{ $thumb }}" alt="{{ $video->title }}"
                                                class="w-full h-full object-cover"
                                                loading="lazy">
                                        @else
                                            <div class="flex h-full items-center justify-center text-xs text-slate-400">
                                                No preview
                                            </div>
                                        @endif
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ $video->title }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ Str::limit($video->description, 60) }}
                                        </p>
                                        @include('partials.share-buttons', ['video' => $video])
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500 dark:text-slate-300">
                                    Add more videos to see recommendations here.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Library --}}
        <section class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Library</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Browse the full catalogue.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($others->skip(4) as $video)
                    @php
                        $thumb = media_url($video->thumbnail_path);
                        $videoUrl = media_url($video->video_path);
                    @endphp
                    <article
                        class="group bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition">
                        <div class="relative bg-slate-100 dark:bg-slate-800 aspect-video">
                            @if ($videoUrl)
                                <video controls preload="metadata"
                                    class="w-full h-full object-cover"
                                    @if ($thumb) poster="{{ $thumb }}" @endif>
                                    <source src="{{ $videoUrl }}" type="video/mp4">
                                </video>
                            @elseif ($thumb)
                                <img src="{{ $thumb }}" alt="{{ $video->title }}"
                                    class="w-full h-full object-cover"
                                    loading="lazy">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-sm text-slate-500">
                                    No media available.
                                </div>
                            @endif
                            <span
                                class="absolute top-3 right-3 px-2 py-1 rounded-full text-[10px] uppercase {{ $video->is_published ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600' }}">
                                {{ $video->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </div>
                        <div class="p-5 space-y-3">
                            <h3 class="font-semibold text-slate-900 dark:text-white truncate">{{ $video->title }}</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                {{ Str::limit($video->description, 90) }}
                            </p>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                                @include('partials.share-buttons', ['video' => $video])
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
@endsection

<!-- Alpine.js -->
<!-- Font for icons if using SVGs is too bulky -->
<script src="//unpkg.com/alpinejs" defer></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        tippy('[data-tippy-content]', {
            placement: 'top',
            animation: 'shift-away',
            theme: 'light-border',
        });
    });
</script>
@include('partials.footer')
