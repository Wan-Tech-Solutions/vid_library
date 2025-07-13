@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-8">
        <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-xl">
            <div class="flex items-center gap-3 mb-6">
                <lord-icon src="https://cdn.lordicon.com/abdfjfnc.json" trigger="loop" colors="primary:#16a34a"
                    style="width:40px;height:40px">
                </lord-icon>
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">Upload a New Video</h2>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-800 dark:text-red-200">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="uploadForm" method="POST" action="{{ route('videos.store') }}" enctype="multipart/form-data"
                class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Video Title</label>
                    <input type="text" name="title"
                        class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring focus:ring-green-400"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Video File (.mp4)</label>
                    <input type="file" name="video" id="videoInput" accept="video/mp4"
                        class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring focus:ring-green-400"
                        required>
                </div>

                <div class="hidden" id="videoPreviewContainer">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Video Preview</label>
                    <video id="videoPreview" class="w-full rounded-md shadow-md transition-all duration-700 hover:scale-105"
                        controls></video>
                </div>

                <div id="autoThumbContainer" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Auto-Generated
                        Thumbnail</label>
                    <canvas id="videoThumbnailCanvas" class="rounded mt-2 max-w-xs"></canvas>
                </div>

                <p class="text-sm text-gray-500 mt-2">Or upload your preferred image as a thumbnail:</p>

                <div>
                    <input type="file" name="thumbnail" id="thumbInput" accept="image/*"
                        class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring focus:ring-green-400">
                    <img id="thumbPreview" src="" alt="Thumbnail Preview" class="hidden rounded mt-2 max-w-xs" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Video Description</label>
                    <textarea name="description" rows="4"
                        class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring focus:ring-green-400"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Upload Progress</label>
                    <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div id="progressBar" class="h-full bg-green-500 w-0 transition-all duration-500 ease-in-out"></div>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition">
                    Upload Video
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <script>
        const form = document.getElementById('uploadForm');
        const progressBar = document.getElementById('progressBar');
        const videoInput = document.getElementById('videoInput');
        const videoPreview = document.getElementById('videoPreview');
        const previewContainer = document.getElementById('videoPreviewContainer');
        const autoThumbContainer = document.getElementById('autoThumbContainer');
        const canvas = document.getElementById('videoThumbnailCanvas');
        const thumbInput = document.getElementById('thumbInput');
        const thumbPreview = document.getElementById('thumbPreview');

        videoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const url = URL.createObjectURL(file);
                videoPreview.src = url;
                previewContainer.classList.remove('hidden');

                videoPreview.addEventListener('loadeddata', function() {
                    setTimeout(() => {
                        const ctx = canvas.getContext('2d');
                        const width = 320;
                        const height = 180;

                        canvas.width = width;
                        canvas.height = height;

                        ctx.drawImage(videoPreview, 0, 0, width, height);
                        autoThumbContainer.classList.remove('hidden');
                    }, 1000);
                }, {
                    once: true
                });
            } else {
                previewContainer.classList.add('hidden');
                videoPreview.src = '';
            }
        });

        thumbInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                thumbPreview.src = URL.createObjectURL(file);
                thumbPreview.classList.remove('hidden');
            } else {
                thumbPreview.src = '';
                thumbPreview.classList.add('hidden');
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);

            if (!thumbInput.files.length) {
                canvas.toBlob(blob => {
                    formData.append('autogenerated_thumbnail', blob, 'thumbnail.jpg');
                    sendForm(formData);
                }, 'image/jpeg', 0.95);
            } else {
                sendForm(formData);
            }
        });

        function sendForm(formData) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                }
            });

            xhr.onload = function() {
                if (xhr.status === 200) {
                    progressBar.style.width = '100%';
                    progressBar.classList.add('bg-green-600');

                    const msg = document.createElement('div');
                    msg.className = 'mt-4 p-3 rounded bg-green-100 text-green-800 font-medium';
                    msg.innerHTML = '✅ Video uploaded successfully!';
                    form.after(msg);

                    setTimeout(() => {
                        msg.remove();
                        form.reset();
                        progressBar.style.width = '0%';
                        previewContainer.classList.add('hidden');
                        autoThumbContainer.classList.add('hidden');
                        videoPreview.src = '';
                        thumbPreview.classList.add('hidden');
                    }, 3000);
                } else {
                    console.error(xhr.responseText);
                    alert('❌ Upload failed. Check console for errors.');
                }
            };

            xhr.send(formData);
        }
    </script>
@endpush
