@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">📋 Activity Logs</h2>

        <!-- Filters and Export -->
        <form method="GET" class="flex flex-wrap items-end gap-4 mb-6 bg-gray-50 dark:bg-gray-800 p-4 rounded shadow">
            <div class="flex flex-col">
                <label class="text-sm text-gray-600 dark:text-gray-300">User</label>
                <input type="text" name="user" value="{{ request('user') }}" class="form-input rounded">
            </div>
            <div class="flex flex-col">
                <label class="text-sm text-gray-600 dark:text-gray-300">Description</label>
                <input type="text" name="description" value="{{ request('description') }}" class="form-input rounded">
            </div>
            <div class="flex flex-col">
                <label class="text-sm text-gray-600 dark:text-gray-300">Date</label>
                <input type="date" name="date" value="{{ request('date') }}" class="form-input rounded">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                <a href="{{ route('admin.logs.index') }}"
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Reset</a>
            </div>
            <div class="ml-auto flex gap-2">
                <a href="{{ route('admin.logs.export') }}"
                    class="inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded mb-4">
                    ⬇ Export to Excel
                </a>
                <a href="{{ route('admin.logs.index', array_merge(request()->all(), ['export' => 'pdf'])) }}"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Export PDF</a>
            </div>
        </form>

        <!-- Logs Table -->
        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold">📅 Date</th>
                        <th class="px-6 py-3 text-left font-semibold">📝 Description</th>
                        <th class="px-6 py-3 text-left font-semibold">🙍 User</th>
                        <th class="px-6 py-3 text-left font-semibold">🧾 Properties</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-800 dark:text-white">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4">{{ ucfirst($log->description) }}</td>
                            <td class="px-6 py-4">{{ $log->causer?->name ?? 'System' }}</td>
                            <td class="px-6 py-4 text-xs">
                                <ul class="text-xs space-y-1">
                                    @foreach ($log->properties->toArray() as $key => $value)
                                        <li><span class="font-medium">{{ ucfirst($key) }}:</span>
                                            {{ is_array($value) ? json_encode($value) : $value }}</li>
                                    @endforeach
                                </ul>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No activity logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $logs->links('pagination::tailwind') }}
        </div>
    </div>
@endsection
