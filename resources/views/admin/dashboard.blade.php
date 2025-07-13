@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto py-10 px-6">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-8">Admin Dashboard</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <!-- All Videos -->
            <a href="{{ route('videos.index') }}"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-xl transition-all hover:scale-105">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Total Videos</h3>
                <p class="text-4xl mt-2 text-blue-500">{{ $videoCount }}</p>
            </a>

            <!-- Published Videos -->
            <a href="{{ route('videos.index', ['filter' => 'published']) }}"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-xl transition-all hover:scale-105">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Published</h3>
                <p class="text-4xl mt-2 text-green-500">{{ $publishedCount }}</p>
            </a>

            <!-- Hidden Videos -->
            <a href="{{ route('videos.index', ['filter' => 'hidden']) }}"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-xl transition-all hover:scale-105">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Hidden</h3>
                <p class="text-4xl mt-2 text-red-500">{{ $hiddenCount }}</p>
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-10">
            <h4 class="text-xl font-semibold mb-4 text-gray-700 dark:text-white">Recent Activity Logs</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead>
                        <tr class="text-gray-600 dark:text-gray-300 border-b dark:border-gray-700">
                            <th class="py-2 px-4">User</th>
                            <th class="py-2 px-4">Action</th>
                            <th class="py-2 px-4">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLogs as $log)
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="py-2 px-4 text-gray-800 dark:text-gray-100">
                                    {{ optional($log->causer)->name ?? 'System' }}</td>
                                <td class="py-2 px-4 text-gray-800 dark:text-gray-100">{{ $log->description }}</td>
                                <td class="py-2 px-4 text-gray-800 dark:text-gray-100">
                                    {{ $log->created_at->format('d M, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-gray-500 dark:text-gray-400">No activity
                                    logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap justify-center gap-4 mt-8">
            <a href="{{ route('videos.create') }}"
                class="px-6 py-2 bg-green-600 text-white font-semibold rounded hover:bg-green-700 transition">
                Upload Video
            </a>

            <a href="{{ route('videos.index') }}"
                class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded hover:bg-indigo-700 transition">
                Show All Videos
            </a>

            <a href="{{ route('admin.users.index') }}"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">
                Manage Users
            </a>

            <a href="{{ route('admin.logs.index') }}"
                class="px-6 py-2 bg-gray-600 text-white font-semibold rounded hover:bg-gray-700 transition">
                View Logs
            </a>
        </div>
    </div>
@endsection
