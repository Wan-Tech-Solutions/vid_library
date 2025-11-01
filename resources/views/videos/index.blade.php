@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-6 py-10">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-6">
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white">🎬 Uploaded Videos</h2>
            <a href="{{ route('videos.create') }}"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow transition">
                + Upload New Video
            </a>
        </div>

        {{-- Search / Filter Form --}}
        <form method="GET" action="{{ route('videos.index') }}" class="mb-6 flex flex-col md:flex-row items-center gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title..."
                class="w-full md:w-1/3 px-4 py-2 rounded border border-gray-300 dark:bg-gray-800 dark:text-white" />

            <select name="status"
                class="w-full md:w-1/4 px-4 py-2 rounded border border-gray-300 dark:bg-gray-800 dark:text-white">
                <option value="">All Statuses</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Published</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Hidden</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow">
                Filter
            </button>
        </form>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded shadow">
                {{ session('success') }}
            </div>
        @endif

        {{-- Video Table --}}
        <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded shadow-lg">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase">
                    <tr>
                        <th>Preview</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Link</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="video-table" class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-800 dark:text-white">
                    @forelse ($videos as $video)
                        <tr data-id="{{ $video->id }}">
                            <td class="px-4 py-3">
                                @php
                                    $videoUrl = media_url($video->video_path);
                                @endphp
                                <div class="relative group w-[160px] md:w-[180px]" data-video-container>
                                    @if ($videoUrl)
                                        <video controls preload="metadata"
                                            class="w-full h-[90px] md:h-[110px] rounded shadow bg-black object-cover"
                                            controlslist="nodownload noremoteplayback"
                                            playsinline
                                            data-display-duration>
                                            <source src="{{ $videoUrl }}" type="video/mp4">
                                        </video>
                                        <span data-video-duration
                                            class="pointer-events-none absolute bottom-2 right-2 rounded bg-black/80 px-2 py-1 text-[10px] font-semibold text-white opacity-0 transition group-hover:opacity-100">--:--</span>
                                    @else
                                        <div
                                            class="flex h-[120px] items-center justify-center rounded bg-gray-100 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                            No video available
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3 max-w-[240px]">
                                <div class="break-words text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $video->title }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($video->is_published)
                                    <span
                                        class="inline-block px-3 py-1 text-xs bg-green-200 text-green-800 rounded-full">Published</span>
                                @else
                                    <span
                                        class="inline-block px-3 py-1 text-xs bg-gray-300 text-gray-700 rounded-full">Hidden</span>
                                @endif
                            </td>
                            @php
                                $shareUrl = route('landing', ['v' => $video->id]);
                            @endphp
                            <td class="px-4 py-3">
                                <a href="{{ $shareUrl }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:underline dark:text-blue-300 break-all">
                                    {{ $shareUrl }}
                                </a>
                            </td>
                            <td>{{ $video->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $isPublished = $video->is_published;
                                    $btnLabel = $isPublished ? 'Hide' : 'Publish';
                                    $publishClass = $isPublished
                                        ? 'text-yellow-700 hover:bg-yellow-50 dark:text-yellow-300 dark:hover:bg-yellow-500/10'
                                        : 'text-blue-700 hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-500/10';
                                @endphp
                                <div class="relative inline-block text-left" data-action-menu>
                                    <button type="button" data-toggle-menu
                                        class="flex items-center gap-1 rounded border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Actions
                                        <svg class="h-3 w-3" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 1L5 5L1 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                    <div data-menu
                                        class="absolute right-0 z-20 mt-2 w-40 origin-top-right rounded-md border border-gray-200 bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:border-gray-700 dark:bg-gray-800 hidden">
                                        <form action="{{ route('videos.togglePublish', $video->id) }}" method="POST"
                                            class="border-b border-gray-100 dark:border-gray-700">
                                            @csrf
                                            <button type="submit"
                                                class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium transition {{ $publishClass }}">
                                                <span>{{ $btnLabel }}</span>
                                            </button>
                                        </form>
                                        <a href="{{ route('videos.edit', $video->id) }}"
                                            class="block px-3 py-2 text-xs text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                            Edit
                                        </a>
                                        <form action="{{ route('videos.destroy', $video->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this video?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-6 text-gray-500">No videos found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $videos->appends(request()->query())->links() }}
        </div>
    </div>

    @push('scripts')
    <script>
        $(function() {
            $('#video-table').sortable({
                update: function(event, ui) {
                    const order = [];
                    $('#video-table tr').each(function(index, element) {
                        order.push({
                            id: $(element).data('id'),
                            position: index + 1
                        });
                    });

                    $.ajax({
                        url: '{{ route('videos.reorder') }}',
                        method: 'POST',
                        data: {
                            order: order,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function() {
                            console.log('Reordered');
                        },
                        error: function() {
                            alert('Failed to reorder. Try again.');
                        }
                    });
                }
            });

            function closeMenus() {
                $('[data-action-menu] [data-menu]').addClass('hidden');
            }

            $(document).on('click', '[data-toggle-menu]', function(e) {
                e.preventDefault();
                const menu = $(this).closest('[data-action-menu]').find('[data-menu]');
                const isHidden = menu.hasClass('hidden');
                closeMenus();
                if (isHidden) {
                    menu.removeClass('hidden');
                }
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('[data-action-menu]').length) {
                    closeMenus();
                }
            });

            $(document).on('click', '[data-action-menu] [data-menu] button, [data-action-menu] [data-menu] a', function() {
                closeMenus();
            });
        });
    </script>
@endpush
@endsection


