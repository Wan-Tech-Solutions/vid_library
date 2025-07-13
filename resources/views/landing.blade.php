@extends('layouts.landing')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">
        {{-- Elegant Title Bar --}}
        <div class="relative mb-10 text-center">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800 dark:text-white tracking-tight">
                OUR VIDEO LIBRARY
            </h1>
            <div
                class="mt-3 mx-auto w-24 h-1 bg-gradient-to-r from-emerald-500 via-lime-400 to-emerald-500 rounded-full animate-pulse">
            </div>
            <p class="mt-2 text-gray-500 dark:text-gray-300 text-sm md:text-base">
                Explore our growing collection of amazing videos curated just for you.
            </p>
        </div>
        <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-white">🎥 Latest Videos</h1>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-12 min-h-[500px]">
            {{-- Left Column: Featured Video --}}
            <div class="md:col-span-8 flex flex-col h-full">
                @if ($latest)
                    <div class="bg-white dark:bg-gray-900 p-4 rounded-xl shadow-lg h-full flex flex-col">
                        <h3 class="text-2xl font-semibold mb-2 text-gray-800 dark:text-white">
                            {{ $latest->title }}
                        </h3>
                        <div class="rounded-md overflow-hidden max-h-[400px] mb-2">
                            <video controls
                                class="w-full h-full object-cover rounded-lg transition-all hover:scale-105 duration-300">
                                <source src="{{ asset('storage/' . $latest->video_path) }}" type="video/mp4">
                            </video>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 mb-2 flex-grow">{{ $latest->description }}</p>
                        @include('partials.share-buttons', ['video' => $latest])
                    </div>
                @endif
            </div>

            {{-- Right Column: Next 2 Videos --}}
            <div class="md:col-span-4 flex flex-col gap-4 h-full">
                @foreach ($others->take(2) as $video)
                    <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-md flex flex-col flex-grow">
                        <h5 class="text-md font-semibold mb-1 text-gray-800 dark:text-white truncate">
                            {{ $video->title }}
                        </h5>
                        <video controls class="w-full h-32 object-cover rounded-md mb-1">
                            <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                        </video>
                        <p class="text-sm mt-1 text-gray-600 dark:text-gray-300 line-clamp-2 flex-grow">
                            {{ Str::limit($video->description, 80) }}
                        </p>
                        @include('partials.share-buttons', ['video' => $video])
                    </div>
                @endforeach
            </div>
        </div>


        {{-- Grid of Other Videos --}}
        <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">Other Videos</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($others->skip(3) as $video)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition">
                    <video controls class="w-full h-48 object-cover rounded-t-xl">
                        <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                    </video>
                    <div class="p-4">
                        <h5 class="font-semibold text-gray-800 dark:text-white truncate">{{ $video->title }}</h5>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            {{ Str::limit($video->description, 100) }}
                        </p>
                        <div class="mt-2">
                            @include('partials.share-buttons', ['video' => $video])
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
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
@endsection
