@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-10">
        <div class="bg-white dark:bg-gray-900 shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">🎬 Edit Video</h2>

            @if (session('success'))
                <div class="mb-4 px-4 py-3 rounded bg-green-100 text-green-800 text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('videos.update', $video->id) }}" method="POST" enctype="multipart/form-data"
                x-data="videoUploadHandler()" @submit.prevent="submitForm">
                @csrf
                @method('PUT')

                <!-- Title -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Title</label>
                    <input type="text" name="title"
                        class="mt-1 w-full px-4 py-2 rounded border focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-white"
                        value="{{ old('title', $video->title) }}" required>
                    @error('title')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Description</label>
                    <textarea name="description" rows="4"
                        class="mt-1 w-full px-4 py-2 rounded border focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">{{ old('description', $video->description) }}</textarea>
                    @error('description')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Current Video -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Current Video</label>
                    <div
                        class="rounded-md overflow-hidden shadow-md transform transition duration-300 hover:scale-105 w-60">
                        <video width="100%" class="rounded" controls>
                            <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                        </video>
                    </div>
                </div>
                @if ($video->thumbnail_path)
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Current
                            Thumbnail</label>
                        <img src="{{ asset('storage/' . $video->thumbnail_path) }}" alt="Current Thumbnail"
                            class="w-60 h-auto rounded shadow-md hover:scale-105 transition-transform duration-300" />
                    </div>
                @endif

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Replace Thumbnail
                        (optional)</label>
                    <input type="file" name="thumbnail" accept="image/*"
                        class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-green-600 file:text-white hover:file:bg-green-700">
                    <small class="text-gray-500 dark:text-gray-400">Leave empty to keep current thumbnail.</small>
                    @error('thumbnail')
                        <span class="text-red-600 text-sm block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Upload New Video -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Replace Video
                        (optional)</label>
                    <input type="file" name="video" accept="video/mp4"
                        class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700"
                        @change="handleFileChange">

                    <small class="text-gray-500 dark:text-gray-400">Leave empty to keep current video.</small>
                    @error('video')
                        <span class="text-red-600 text-sm block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Progress bar -->
                <div x-show="isUploading" class="mb-5">
                    <div class="text-sm text-gray-700 mb-1">Uploading: <span x-text="uploadProgress + '%'"></span></div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-blue-600 h-3 rounded-full transition-all duration-300"
                            :style="'width:' + uploadProgress + '%'"></div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('videos.index') }}"
                        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</a>
                    <button type="submit"
                        class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                        :disabled="isUploading">
                        Update Video
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function videoUploadHandler() {
            return {
                isUploading: false,
                uploadProgress: 0,
                handleFileChange(event) {
                    const file = event.target.files[0];
                    if (file && file.type !== 'video/mp4') {
                        alert('Only MP4 videos are allowed.');
                        event.target.value = '';
                    }
                },
                submitForm(event) {
                    this.isUploading = true;
                    this.uploadProgress = 0;

                    const form = event.target;
                    const data = new FormData(form);

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: data,
                    }).then(async response => {
                        this.isUploading = false;
                        if (!response.ok) throw new Error('Upload failed');
                        window.location.href = "{{ route('videos.index') }}";
                    }).catch(error => {
                        this.isUploading = false;
                        alert("Upload failed. Please try again.");
                        console.error(error);
                    });

                    // Fake progress animation
                    const interval = setInterval(() => {
                        if (this.uploadProgress < 98) {
                            this.uploadProgress += 2;
                        } else {
                            clearInterval(interval);
                        }
                    }, 150);
                }
            };
        }
    </script>
@endpush
