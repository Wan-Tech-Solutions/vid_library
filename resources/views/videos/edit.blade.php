@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-10">
        <div class="bg-white dark:bg-gray-900 shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">?? Edit Video</h2>

            @php
                $currentVideoUrl = media_url($video->video_path);
                $currentThumbnailUrl = media_url($video->thumbnail_path);
            @endphp

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
                        @if ($currentVideoUrl)
                            <video width="100%" class="rounded" controls preload="metadata"
                                @if ($currentThumbnailUrl) poster="{{ $currentThumbnailUrl }}" @endif>
                                <source src="{{ $currentVideoUrl }}" type="video/mp4">
                            </video>
                        @else
                            <div class="flex items-center justify-center h-32 text-sm text-gray-500 dark:text-gray-300">
                                Video file unavailable.
                            </div>
                        @endif
                    </div>
                </div>

                @if ($currentThumbnailUrl || $currentVideoUrl)
                    @php
                        $thumbVideoClasses = 'absolute inset-0 w-full h-full object-cover rounded transition-opacity duration-300';
                        $thumbVideoClasses .= $currentThumbnailUrl
                            ? ' opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto'
                            : ' opacity-100';
                    @endphp
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Current
                            Thumbnail</label>
                        <div
                            class="relative w-60 h-36 rounded shadow-md overflow-hidden bg-gray-100 dark:bg-gray-800 group">
                            @if ($currentThumbnailUrl)
                                <img src="{{ $currentThumbnailUrl }}" alt="Current Thumbnail"
                                    class="w-full h-full object-cover rounded transition-opacity duration-300 group-hover:opacity-0"
                                    onerror="const container=this.parentElement; this.remove(); const videoEl=container.querySelector('video'); if(videoEl){ videoEl.classList.remove('opacity-0','pointer-events-none'); videoEl.classList.add('opacity-100'); } const fallbackEl=container.querySelector('[data-fallback]'); if(fallbackEl){ fallbackEl.classList.remove('hidden'); }" />
                            @endif

                            @if ($currentVideoUrl)
                                <video src="{{ $currentVideoUrl }}" muted playsinline preload="metadata"
                                    class="{{ $thumbVideoClasses }}" @if ($currentThumbnailUrl) poster="{{ $currentThumbnailUrl }}" @endif
                                    loop></video>
                            @endif

                            <div data-fallback
                                class="absolute inset-0 flex items-center justify-center text-gray-500 text-xs italic {{ $currentThumbnailUrl || $currentVideoUrl ? 'hidden' : '' }}">
                                No Preview
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Current
                            Thumbnail</label>
                        <div
                            class="w-60 h-36 rounded shadow-md overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-sm text-gray-500 dark:text-gray-300">
                            No thumbnail uploaded.
                        </div>
                    </div>
                @endif

                <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                    Thumbnails are generated automatically 15 seconds into the video. Uploading a new video will refresh the
                    thumbnail.
                </p>

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

                <div x-show="hasNewVideo" x-cloak class="mb-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div id="replacementPreview" class="relative rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-gray-50 dark:bg-gray-900"></div>
                    </div>
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
                hasNewVideo: false,
                selectedFile: null,
                previewUrl: null,
                handleFileChange(event) {
                    const files = event.target.files || [];
                    const file = files.length ? files[0] : null;
                    if (file && file.type !== 'video/mp4') {
                        alert('Only MP4 videos are allowed.');
                        event.target.value = '';
                        return;
                    }

                    if (!file) {
                        this.resetSelection();
                        return;
                    }

                    this.selectedFile = file;
                    this.hasNewVideo = true;

                    if (this.previewUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                    }

                    this.previewUrl = URL.createObjectURL(file);
                    this.$nextTick(() => this.renderPreview());
                },
                renderPreview() {
                    const previewContainer = document.getElementById('replacementPreview');
                    if (!previewContainer) {
                        return;
                    }
                    previewContainer.innerHTML = '';
                    if (!this.previewUrl) {
                        return;
                    }
                    const video = document.createElement('video');
                    video.controls = true;
                    video.preload = 'metadata';
                    video.src = this.previewUrl;
                    video.className = 'w-full rounded-xl object-cover bg-black';
                    previewContainer.appendChild(video);
                },
                resetSelection() {
                    this.hasNewVideo = false;
                    this.selectedFile = null;
                    if (this.previewUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                        this.previewUrl = null;
                    }
                    const previewContainer = document.getElementById('replacementPreview');
                    if (previewContainer) {
                        previewContainer.innerHTML = '';
                    }
                },
                submitForm(event) {
                    this.isUploading = true;
                    this.uploadProgress = 0;

                    const form = event.target;
                    const data = new FormData(form);

                    if (this.hasNewVideo && this.selectedFile) {
                        data.delete('video');
                        data.append('video', this.selectedFile, this.selectedFile.name);
                    }

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: data,
                        credentials: 'same-origin',
                    }).then(async response => {
                        this.isUploading = false;
                        if (!response.ok) {
                            throw new Error('Upload failed');
                        }
                        window.location.href = "{{ route('videos.index') }}";
                    }).catch(error => {
                        this.isUploading = false;
                        alert('Upload failed. Please try again.');
                        console.error(error);
                    });

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


