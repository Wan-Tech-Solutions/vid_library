@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-xl">
            <div class="flex items-center gap-3 mb-6">
                <lord-icon src="https://cdn.lordicon.com/abdfjfnc.json" trigger="loop" colors="primary:#16a34a"
                    style="width:40px;height:40px">
                </lord-icon>
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">Upload Videos</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Select multiple MP4 files, remove any you do not need, then start the upload. You can cancel or resume
                        uploads at any time.
                    </p>
                </div>
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
                class="space-y-8">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Select Video Files
                        (.mp4)</label>
                    <input type="file" id="videoInput" accept="video/mp4" multiple
                        class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring focus:ring-green-400">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Tip: hold Ctrl (Cmd on Mac) or Shift to pick multiple videos at once.
                    </p>
                </div>

                <div id="emptyState"
                    class="flex flex-col items-center justify-center gap-3 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl py-12 text-center text-gray-500 dark:text-gray-400">
                    <span class="text-xl">[ ]</span>
                    <p class="font-medium">No videos selected yet</p>
                    <p class="text-sm">Choose videos to review before uploading. Thumbnails are captured automatically.</p>
                </div>

                <div id="selectedVideosContainer" class="space-y-6"></div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" id="startUploadButton"
                        class="px-5 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 focus:outline-none focus:ring focus:ring-green-400 disabled:opacity-50 disabled:cursor-not-allowed">
                        Upload Selected Videos
                    </button>
                    <button type="button" id="cancelAllButton"
                        class="px-5 py-2 rounded-lg bg-red-500 text-white font-semibold hover:bg-red-600 focus:outline-none focus:ring focus:ring-red-400 disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                        Cancel All Uploads
                    </button>
                    <span id="queueStatus" class="text-sm text-gray-500 dark:text-gray-400"></span>
                </div>

                <div id="uploadSummary" class="hidden">
                    <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                        <h3 class="text-sm font-semibold text-green-800 mb-2">Upload summary</h3>
                        <ul id="summaryList" class="space-y-1 text-sm"></ul>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@ffmpeg/ffmpeg@0.12.6/dist/ffmpeg.min.js"></script>
    <script src="{{ asset('js/video-editor.js') }}"></script>
    <script>
        (() => {
            const CHUNK_SIZE = 8 * 1024 * 1024;
            const MAX_PARALLEL_UPLOADS = 2;

            const form = document.getElementById('uploadForm');
            const videoInput = document.getElementById('videoInput');
            const selectedContainer = document.getElementById('selectedVideosContainer');
            const emptyState = document.getElementById('emptyState');
            const startButton = document.getElementById('startUploadButton');
            const cancelAllButton = document.getElementById('cancelAllButton');
            const summary = document.getElementById('uploadSummary');
            const summaryList = document.getElementById('summaryList');
            const queueStatus = document.getElementById('queueStatus');
            const csrfToken = form.querySelector('input[name="_token"]').value;

            const routes = {
                chunk: @json(route('videos.uploadChunk')),
                finalize: @json(route('videos.store')),
                cancel: @json(route('videos.cancelUpload')),
                index: @json(route('videos.index')),
            };

            const state = {
                entries: new Map(),
                isUploading: false,
            };

            form.addEventListener('submit', (event) => event.preventDefault());

            videoInput.addEventListener('change', (event) => {
                const files = Array.from(event.target.files || []);
                if (!files.length) {
                    return;
                }
                files.forEach(addEntry);
                event.target.value = '';
                refreshUI();
            });

            startButton.addEventListener('click', () => {
                if (state.isUploading) {
                    return;
                }
                startUploads();
            });

            cancelAllButton.addEventListener('click', () => {
                state.entries.forEach((entry) => {
                    if (entry.status === 'completed') {
                        return;
                    }
                    cancelEntry(entry);
                });
            });

            function addEntry(file) {
                if (file.type !== 'video/mp4') {
                    notifySummary(`${file.name} skipped (only MP4 files are supported).`, 'error');
                    return;
                }

                const uploadId = generateUploadId();

                const entry = {
                    uploadId,
                    file,
                    status: 'pending',
                    uploadedChunks: 0,
                    totalChunks: Math.max(1, Math.ceil(file.size / CHUNK_SIZE)),
                    cancelRequested: false,
                    abortController: null,
                    previewUrl: URL.createObjectURL(file),
                    elements: {},
                };

                renderEntry(entry);
                state.entries.set(uploadId, entry);
            }

            function renderEntry(entry) {
                const wrapper = document.createElement('div');
                wrapper.className = 'rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-5 shadow-sm';
                wrapper.dataset.uploadId = entry.uploadId;

                const header = document.createElement('div');
                header.className = 'flex items-start justify-between gap-3';

                const info = document.createElement('div');
                const nameEl = document.createElement('p');
                nameEl.className = 'text-lg font-semibold text-gray-800 dark:text-gray-100 break-words';
                nameEl.textContent = entry.file.name;

                const sizeEl = document.createElement('p');
                sizeEl.className = 'text-xs text-gray-500';
                sizeEl.textContent = formatFileSize(entry.file.size);

                info.append(nameEl, sizeEl);

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'text-sm font-medium text-red-600 hover:text-red-700';
                removeButton.textContent = 'Remove';
                removeButton.addEventListener('click', async () => {
                    if (entry.status === 'uploading') {
                        alert('Cancel this upload before removing it from the queue.');
                        return;
                    }
                    await cleanupPartial(entry);
                    URL.revokeObjectURL(entry.previewUrl);
                    if (wrapper.parentElement) {
                        wrapper.parentElement.removeChild(wrapper);
                    }
                    state.entries.delete(entry.uploadId);
                    refreshUI();
                });

                header.append(info, removeButton);

                const body = document.createElement('div');
                body.className = 'grid gap-4 md:grid-cols-2 mt-4';

                const videoSection = document.createElement('div');
                videoSection.classList.add('relative');
                const videoEl = document.createElement('video');
                videoEl.className = 'w-full h-48 rounded-xl border border-gray-200 dark:border-gray-700 object-cover';
                videoEl.controls = true;
                videoEl.muted = true;
                videoEl.preload = 'metadata';
                videoEl.src = entry.previewUrl;
                videoSection.appendChild(videoEl);

                const fieldsSection = document.createElement('div');
                fieldsSection.className = 'space-y-4';

                const titleWrapper = document.createElement('div');
                const titleLabel = document.createElement('label');
                titleLabel.className = 'block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1';
                titleLabel.textContent = 'Video Title';
                const titleInput = document.createElement('input');
                titleInput.type = 'text';
                titleInput.className = 'w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring focus:ring-green-400';
                titleInput.value = deriveDefaultTitle(entry.file.name);
                titleWrapper.append(titleLabel, titleInput);

                const descWrapper = document.createElement('div');
                const descLabel = document.createElement('label');
                descLabel.className = 'block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1';
                descLabel.textContent = 'Description (optional)';
                const descInput = document.createElement('textarea');
                descInput.rows = 3;
                descInput.className = 'w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring focus:ring-green-400';
                descWrapper.append(descLabel, descInput);

                fieldsSection.append(titleWrapper, descWrapper);

                body.append(videoSection, fieldsSection);

                const editorHost = document.createElement('div');
                editorHost.className = 'md:col-span-2';
                body.appendChild(editorHost);

                const progressWrapper = document.createElement('div');
                progressWrapper.className = 'mt-4';
                const progressLabel = document.createElement('p');
                progressLabel.className = 'text-sm text-gray-600 dark:text-gray-300 mb-2';
                progressLabel.textContent = 'Upload progress';
                const progressTrack = document.createElement('div');
                progressTrack.className = 'w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden';
                const progressBar = document.createElement('div');
                progressBar.className = 'h-full w-0 bg-green-500 rounded-full transition-all duration-300 ease-linear';
                progressTrack.appendChild(progressBar);
                const statusText = document.createElement('p');
                statusText.className = 'mt-2 text-sm text-gray-500';
                statusText.textContent = 'Pending upload';
                progressWrapper.append(progressLabel, progressTrack, statusText);

                const actions = document.createElement('div');
                actions.className = 'mt-4 flex flex-wrap gap-3';

                const cancelButton = document.createElement('button');
                cancelButton.type = 'button';
                cancelButton.className = 'hidden px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 focus:outline-none focus:ring focus:ring-red-400';
                cancelButton.textContent = 'Cancel Upload';
                cancelButton.addEventListener('click', () => cancelEntry(entry));

                const resumeButton = document.createElement('button');
                resumeButton.type = 'button';
                resumeButton.className = 'hidden px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring focus:ring-blue-300';
                resumeButton.textContent = 'Resume Upload';
                resumeButton.addEventListener('click', () => {
                    if (state.isUploading) {
                        return;
                    }
                    entry.status = 'pending';
                    entry.cancelRequested = false;
                    resumeButton.classList.add('hidden');
                    statusText.textContent = 'Queued for upload';
                    startUploads();
                });

                actions.append(cancelButton, resumeButton);

                wrapper.append(header, body, progressWrapper, actions);
                selectedContainer.appendChild(wrapper);

                entry.elements = {
                    wrapper,
                    nameEl,
                    sizeEl,
                    removeButton,
                    cancelButton,
                    resumeButton,
                    titleInput,
                    descInput,
                    progressBar,
                    statusText,
                };

                if (window.VideoEditorKit) {
                    const editorInstance = window.VideoEditorKit.attach({
                        videoElement: videoEl,
                        previewContainer: videoSection,
                        controlsContainer: editorHost,
                        getFile: () => entry.file,
                        setFile: (newFile) => {
                            const previousUrl = entry.previewUrl;
                            entry.file = newFile;
                            entry.previewUrl = URL.createObjectURL(newFile);
                            videoEl.src = entry.previewUrl;
                            videoEl.load();
                            if (previousUrl) {
                                URL.revokeObjectURL(previousUrl);
                            }
                            entry.totalChunks = Math.max(1, Math.ceil(newFile.size / CHUNK_SIZE));
                            entry.uploadedChunks = 0;
                            entry.elements.nameEl.textContent = entry.file.name;
                            entry.elements.sizeEl.textContent = formatFileSize(entry.file.size);
                            entry.elements.statusText.textContent = 'Edited video ready for upload';
                            updateProgress(entry, 0);
                            if (entry.editor && typeof entry.editor.rehydrate === 'function') {
                                entry.editor.rehydrate();
                            }
                            refreshUI();
                        },
                        onStatus: (message, type) => {
                            if (!entry.editor) {
                                return;
                            }
                            if (type === 'success') {
                                notifySummary(`${entry.file.name}: ${message}`, 'success');
                            } else if (type === 'error') {
                                notifySummary(`${entry.file.name}: ${message}`, 'error');
                            }
                        },
                    });
                    entry.editor = editorInstance;
                }

                refreshUI();
            }

            function refreshUI() {
                const entries = Array.from(state.entries.values());

                if (!entries.length) {
                    emptyState.classList.remove('hidden');
                } else {
                    emptyState.classList.add('hidden');
                }

                const pending = entries.filter((entry) =>
                    ['pending', 'failed', 'cancelled'].includes(entry.status)
                ).length;
                const uploading = entries.filter((entry) => entry.status === 'uploading').length;
                const completed = entries.filter((entry) => entry.status === 'completed').length;

                queueStatus.textContent = entries.length
                    ? `Pending: ${pending} · Uploading: ${uploading} · Completed: ${completed}`
                    : '';

                startButton.disabled = pending === 0 || state.isUploading;
                startButton.textContent = state.isUploading ? 'Uploading…' : 'Upload Selected Videos';
                cancelAllButton.disabled = !state.isUploading;
            }

            async function startUploads() {
                if (state.isUploading) {
                    return;
                }

                const queue = Array.from(state.entries.values()).filter((entry) =>
                    ['pending', 'failed', 'cancelled'].includes(entry.status)
                );

                if (!queue.length) {
                    return;
                }

                state.isUploading = true;
                refreshUI();

                const parallel = Math.min(MAX_PARALLEL_UPLOADS, queue.length);
                const workQueue = queue.slice();
                const workers = [];

                const worker = async () => {
                    while (workQueue.length) {
                        const entry = workQueue.shift();
                        if (!entry) {
                            break;
                        }

                        try {
                            await uploadEntry(entry);
                            entry.elements.resumeButton.classList.add('hidden');
                        } catch (error) {
                            if (error.name === 'AbortError' || error.message === 'cancelled') {
                                entry.status = 'cancelled';
                                entry.uploadedChunks = 0;
                                entry.elements.statusText.textContent = 'Upload cancelled';
                                entry.elements.resumeButton.classList.remove('hidden');
                                updateProgress(entry, 0);
                                await cleanupPartial(entry);
                            } else {
                                entry.status = 'failed';
                                const message = error.message || 'Upload failed';
                                entry.elements.statusText.textContent = `${message}. Click resume to retry.`;
                                entry.elements.resumeButton.classList.remove('hidden');
                                notifySummary(`${entry.file.name} failed: ${message}`, 'error');
                            }
                            entry.elements.cancelButton.classList.add('hidden');
                        } finally {
                            entry.cancelRequested = false;
                            entry.abortController = null;
                            entry.elements.removeButton.disabled = false;
                            refreshUI();
                        }
                    }
                };

            for (let index = 0; index < parallel; index += 1) {
                workers.push(worker());
            }

                await Promise.all(workers);

                state.isUploading = false;
                refreshUI();

                const hasPending = Array.from(state.entries.values()).some((entry) => entry.status !== 'completed');
                if (!hasPending && state.entries.size) {
                    window.location.href = routes.index;
                }
            }

            async function uploadEntry(entry) {
                entry.status = 'uploading';
                entry.cancelRequested = false;
                entry.elements.cancelButton.classList.remove('hidden');
                entry.elements.resumeButton.classList.add('hidden');
                entry.elements.removeButton.disabled = true;
                entry.elements.statusText.textContent = 'Preparing upload...';

                const totalChunks = Math.max(1, Math.ceil(entry.file.size / CHUNK_SIZE));
                entry.totalChunks = totalChunks;

                for (let index = entry.uploadedChunks; index < totalChunks; index += 1) {
                    if (entry.cancelRequested) {
                        throw new Error('cancelled');
                    }

                    const start = index * CHUNK_SIZE;
                    const end = Math.min(start + CHUNK_SIZE, entry.file.size);
                    const chunk = entry.file.slice(start, end);

                    const formData = new FormData();
                    formData.append('upload_id', entry.uploadId);
                    formData.append('chunk_index', index.toString());
                    formData.append('total_chunks', totalChunks.toString());
                    formData.append('file_name', entry.file.name);
                    formData.append('chunk', chunk, `${entry.file.name}.part${index}`);

                    const controller = new AbortController();
                    entry.abortController = controller;

                    const response = await fetch(routes.chunk, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        credentials: 'same-origin',
                        signal: controller.signal,
                    });

                    if (!response.ok) {
                        const message = await response.text();
                        throw new Error(message || 'Chunk upload failed');
                    }

                    entry.uploadedChunks = index + 1;
                    entry.elements.statusText.textContent = `Uploading chunk ${index + 1} of ${totalChunks}`;
                    updateProgress(entry, Math.round((entry.uploadedChunks / totalChunks) * 100));
                    entry.abortController = null;
                }

                if (entry.cancelRequested) {
                    throw new Error('cancelled');
                }

                await finalizeEntry(entry);

                entry.status = 'completed';
                entry.elements.cancelButton.classList.add('hidden');
                entry.elements.statusText.textContent = 'Upload complete';
                updateProgress(entry, 100);
                notifySummary(`${entry.file.name} uploaded successfully.`, 'success');
            }

            async function finalizeEntry(entry) {
                const formData = new FormData();
                formData.append('upload_id', entry.uploadId);
                formData.append('file_name', entry.file.name);
                formData.append('title', entry.elements.titleInput.value.trim() || deriveDefaultTitle(entry.file.name));
                formData.append('description', entry.elements.descInput.value.trim());
                formData.append('total_chunks', entry.totalChunks.toString());

                const response = await fetch(routes.finalize, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    const message = await response.text();
                    throw new Error(message || 'Failed to finalise upload');
                }
            }

            async function cancelEntry(entry) {
                entry.cancelRequested = true;

                if (entry.abortController) {
                    entry.abortController.abort();
                    return;
                }

                if (entry.status === 'completed') {
                    return;
                }

                entry.status = 'cancelled';
                entry.uploadedChunks = 0;
                entry.elements.cancelButton.classList.add('hidden');
                entry.elements.resumeButton.classList.remove('hidden');
                entry.elements.statusText.textContent = 'Upload cancelled';
                updateProgress(entry, 0);
                await cleanupPartial(entry);
                refreshUI();
            }

            async function cleanupPartial(entry) {
                const formData = new FormData();
                formData.append('upload_id', entry.uploadId);

                try {
                    await fetch(routes.cancel, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        credentials: 'same-origin',
                    });
                } catch (error) {
                    console.warn('Unable to clean up upload', error);
                }
            }

            function updateProgress(entry, percent) {
                const clamped = Math.max(0, Math.min(100, percent || 0));
                entry.elements.progressBar.style.width = `${clamped}%`;
            }

            function deriveDefaultTitle(name) {
                return name.replace(/\.[^/.]+$/, '').replace(/[_-]+/g, ' ').trim() || name;
            }

            function formatFileSize(bytes) {
                if (!Number.isFinite(bytes)) {
                    return '0 B';
                }
                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                const index = Math.min(units.length - 1, Math.floor(Math.log(bytes || 1) / Math.log(1024)));
                const size = bytes / Math.pow(1024, index);
                return `${size.toFixed(size < 10 && index > 0 ? 1 : 0)} ${units[index]}`;
            }

            function generateUploadId() {
                if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                    return window.crypto.randomUUID();
                }
                return `upload-${Date.now()}-${Math.random().toString(16).slice(2)}`;
            }

            function notifySummary(message, type = 'info') {
                if (!summaryList) {
                    return;
                }
                const item = document.createElement('li');
                item.textContent = message;
                if (type === 'success') {
                    item.className = 'text-green-700';
                } else if (type === 'error') {
                    item.className = 'text-red-600';
                } else {
                    item.className = 'text-gray-600';
                }

                if (summaryList.children.length >= 12) {
                    summaryList.removeChild(summaryList.firstChild);
                }

                summary.classList.remove('hidden');
                summaryList.appendChild(item);
            }

            refreshUI();
        })();
    </script>
@endpush
