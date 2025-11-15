@extends('layouts.app')

@push('styles')
    <style>
        @media (max-width: 640px) {
            .upload-actions {
                position: sticky;
                bottom: 1rem;
                width: 100%;
                background: rgba(255, 255, 255, 0.9);
                padding: 0.75rem;
                border-radius: 9999px;
                border: 1px solid rgba(209, 213, 219, 0.9);
                box-shadow: 0 10px 25px rgba(16, 185, 129, 0.15);
                backdrop-filter: blur(18px);
                gap: 0.75rem;
                z-index: 30;
            }

            .dark .upload-actions {
                background: rgba(17, 24, 39, 0.92);
                border-color: rgba(55, 65, 81, 0.8);
            }

            .upload-actions button {
                flex: 1 1 100%;
            }

            .upload-actions #queueStatus,
            .upload-actions #networkHint {
                text-align: center;
            }
        }
    </style>
@endpush

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-xl">
            <div class="flex items-center gap-3 mb-6">
                <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#16a34a"
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Select Video Files (.mp4, .mov, .avi, .mkv, .webm)</label>
                    <input type="file" id="videoInput"
                        accept="video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm"
                        capture="environment"
                        multiple
                        class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:ring focus:ring-green-400">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Tip: hold Ctrl (Cmd on Mac) or Shift to pick multiple videos at once.
                    </p>
                </div>

                <div id="emptyState"
                    class="flex flex-col items-center justify-center gap-3 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl py-12 text-center text-gray-500 dark:text-gray-400">
                    <span class="text-xl">[ ]</span>
                    <p class="font-medium">No videos selected yet</p>
                    <p class="text-sm">Choose videos to review before uploading. You can preview them before sending.</p>
                </div>

                <div id="selectedVideosContainer" class="space-y-6"></div>

                <div class="flex flex-wrap items-center gap-3 upload-actions">
                    <button type="button" id="startUploadButton"
                        class="px-5 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 focus:outline-none focus:ring focus:ring-green-400 disabled:opacity-50 disabled:cursor-not-allowed">
                        Upload Selected Videos
                    </button>
                    <button type="button" id="cancelAllButton"
                        class="px-5 py-2 rounded-lg bg-red-500 text-white font-semibold hover:bg-red-600 focus:outline-none focus:ring focus:ring-red-400 disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                        Cancel All Uploads
                    </button>
                    <span id="queueStatus" class="text-sm text-gray-500 dark:text-gray-400" aria-live="polite"></span>
                    <p id="networkHint" class="text-xs text-gray-400 dark:text-gray-500 w-full"></p>
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
    <script>
        (() => {
            const runtimeSettings = determineRuntimeSettings();
            const CHUNK_SIZE = runtimeSettings.chunkSize;
            const MAX_PARALLEL_UPLOADS = runtimeSettings.parallelUploads;
            const CHUNK_CONCURRENCY = runtimeSettings.chunkConcurrency;
            const MAX_CHUNK_RETRIES = 3;
            const STATUS_PLACEHOLDER = '__UPLOAD_ID__';
            const STORAGE_KEY = 'video-uploader:v1';
            const SUPPORTED_MIME_TYPES = new Set([
                'video/mp4',
                'video/quicktime',
                'video/x-msvideo',
                'video/x-matroska',
                'video/webm',
                'video/mpeg',
                'video/3gpp',
                'video/3gpp2',
            ]);
            const SUPPORTED_EXTENSIONS = new Set([
                'mp4',
                'mov',
                'm4v',
                'avi',
                'mkv',
                'webm',
                'mpg',
                'mpeg',
                '3gp',
                '3g2',
            ]);

            const form = document.getElementById('uploadForm');
            const videoInput = document.getElementById('videoInput');
            const selectedContainer = document.getElementById('selectedVideosContainer');
            const emptyState = document.getElementById('emptyState');
            const startButton = document.getElementById('startUploadButton');
            const cancelAllButton = document.getElementById('cancelAllButton');
            const summary = document.getElementById('uploadSummary');
            const summaryList = document.getElementById('summaryList');
            const queueStatus = document.getElementById('queueStatus');
            const networkHint = document.getElementById('networkHint');
            const csrfToken = form.querySelector('input[name="_token"]').value;
            const persistence = createPersistence(STORAGE_KEY);

            const routes = {
                chunk: @json(route('videos.uploadChunk')),
                finalize: @json(route('videos.store')),
                cancel: @json(route('videos.cancelUpload')),
                index: @json(route('videos.index')),
                status: @json(route('videos.uploadStatus', ['uploadId' => '__UPLOAD_ID__'])),
            };
            const statusUrlFor = (uploadId) => routes.status.replace(
                STATUS_PLACEHOLDER,
                encodeURIComponent(uploadId || '')
            );

            const state = {
                entries: new Map(),
                isUploading: false,
                runtime: runtimeSettings,
            };
            let wakeLock = null;

            if (networkHint && runtimeSettings.hint) {
                networkHint.textContent = runtimeSettings.hint;
            }

            form.addEventListener('submit', (event) => event.preventDefault());

            async function requestWakeLock() {
                if (!('wakeLock' in navigator)) {
                    return;
                }

                try {
                    wakeLock = await navigator.wakeLock.request('screen');
                    const handleRelease = () => {
                        wakeLock = null;
                    };
                    if (wakeLock && typeof wakeLock.addEventListener === 'function') {
                        wakeLock.addEventListener('release', handleRelease, { once: true });
                    } else if (wakeLock) {
                        wakeLock.onrelease = handleRelease;
                    }
                } catch (error) {
                    console.warn('Unable to keep the screen awake', error);
                    wakeLock = null;
                }
            }

            async function releaseWakeLock() {
                if (!wakeLock) {
                    return;
                }

                try {
                    await wakeLock.release();
                } catch (error) {
                    console.warn('Unable to release wake lock', error);
                } finally {
                    wakeLock = null;
                }
            }

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && state.isUploading) {
                    requestWakeLock();
                }
            });

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
                if (!isSupportedVideo(file)) {
                    notifySummary(
                        `${file.name} skipped (unsupported format). Try MP4, MOV, AVI, MKV, or WEBM.`,
                        'error'
                    );
                    return;
                }

                const fingerprint = createFingerprint(file);
                const cachedSession = persistence.get(fingerprint);
                const cachedChunkSize =
                    cachedSession && Number.isFinite(cachedSession.chunkSize) && cachedSession.chunkSize > 0
                        ? cachedSession.chunkSize
                        : CHUNK_SIZE;
                const uploadId =
                    cachedSession && cachedSession.uploadId ? cachedSession.uploadId : generateUploadId();
                const totalChunks = Math.max(1, Math.ceil(file.size / cachedChunkSize));
                const cachedUploadedChunks =
                    cachedSession && Number.isFinite(cachedSession.uploadedChunks)
                    ? Math.min(cachedSession.uploadedChunks, totalChunks)
                    : 0;

                const entry = {
                    uploadId,
                    file,
                    status: 'pending',
                    uploadedChunks: cachedUploadedChunks,
                    totalChunks,
                    cancelRequested: false,
                    chunkControllers: new Map(),
                    partialProgress: new Map(),
                    pendingCompletion: new Set(),
                    previewUrl: URL.createObjectURL(file),
                    elements: {},
                    fingerprint,
                    chunkSize: cachedChunkSize,
                    needsRemoteSync: Boolean(cachedSession),
                };

                state.entries.set(uploadId, entry);
                renderEntry(entry);
                persistEntry(entry);
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

                if (entry.needsRemoteSync) {
                    entry.elements.statusText.textContent = 'Checking uploaded data...';
                } else if (entry.uploadedChunks > 0) {
                    const resumePercent = entry.totalChunks
                        ? Math.min(99, Math.round((entry.uploadedChunks / entry.totalChunks) * 100))
                        : 0;
                    updateProgress(entry, resumePercent);
                    entry.elements.statusText.textContent = `Ready to resume ${resumePercent}%`;
                }

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
                            entry.totalChunks = Math.max(1, Math.ceil(newFile.size / getChunkSize(entry)));
                            entry.uploadedChunks = 0;
                            entry.partialProgress = new Map();
                            entry.pendingCompletion = new Set();
                            entry.chunkControllers = new Map();
                            entry.elements.nameEl.textContent = entry.file.name;
                            entry.elements.sizeEl.textContent = formatFileSize(entry.file.size);
                            entry.elements.statusText.textContent = 'Edited video ready for upload';
                            updateProgress(entry, 0);
                            if (entry.editor && typeof entry.editor.rehydrate === 'function') {
                                entry.editor.rehydrate();
                            }
                            refreshUI();
                            persistEntry(entry);
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
                    ? `Pending: ${pending} | Uploading: ${uploading} | Completed: ${completed}`
                    : '';

                startButton.disabled = pending === 0 || state.isUploading;
                startButton.textContent = state.isUploading ? 'Uploading...' : 'Upload Selected Videos';
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
                requestWakeLock();
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
                            await syncEntryStatus(entry);
                            await uploadEntry(entry);
                            entry.elements.resumeButton.classList.add('hidden');
                        } catch (error) {
                            if (entry.cancelRequested) {
                                entry.status = 'cancelled';
                                entry.uploadedChunks = 0;
                                entry.elements.statusText.textContent = 'Upload cancelled';
                                entry.elements.resumeButton.classList.remove('hidden');
                                updateProgress(entry, 0);
                                await cleanupPartial(entry);
                            } else {
                                entry.status = 'failed';
                                const message =
                                    error && error.message && error.message !== 'cancelled'
                                        ? error.message
                                        : 'Connection interrupted';
                                const percent = entry.totalChunks
                                    ? Math.round((entry.uploadedChunks / entry.totalChunks) * 100)
                                    : 0;
                                entry.elements.statusText.textContent = `${message}. Tap resume to continue.`;
                                entry.elements.resumeButton.classList.remove('hidden');
                                if (percent > 0) {
                                    updateProgress(entry, Math.min(percent, 99));
                                }
                                notifySummary(`${entry.file.name} paused: ${message}`, 'error');
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
                releaseWakeLock();

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



                const chunkStride = getChunkSize(entry);

                const totalChunks = Math.max(1, Math.ceil(entry.file.size / chunkStride));

                entry.totalChunks = totalChunks;

                entry.partialProgress = entry.partialProgress instanceof Map ? entry.partialProgress : new Map();

                entry.pendingCompletion =

                    entry.pendingCompletion instanceof Set ? entry.pendingCompletion : new Set();

                entry.chunkControllers =

                    entry.chunkControllers instanceof Map ? entry.chunkControllers : new Map();



                const cappedUploadedChunks = Math.max(0, Math.min(entry.uploadedChunks, totalChunks));

                entry.uploadedChunks = cappedUploadedChunks;

                const startingPercent = Math.round((cappedUploadedChunks / totalChunks) * 100);

                const initialDisplay = cappedUploadedChunks ? Math.min(startingPercent, 99) : 0;

                if (entry.elements && entry.elements.statusText) {

                    entry.elements.statusText.textContent = cappedUploadedChunks

                        ? `Resuming ${initialDisplay}%`

                        : 'Preparing upload...';

                }

                updateProgress(entry, initialDisplay);



                if (cappedUploadedChunks >= totalChunks) {

                    await finalizeEntry(entry);

                    entry.status = 'completed';

                    entry.elements.cancelButton.classList.add('hidden');

                    entry.elements.statusText.textContent = 'Upload complete';

                    updateProgress(entry, 100);

                    clearEntry(entry);

                    notifySummary(`${entry.file.name} uploaded successfully.`, 'success');

                    return;

                }



                let nextChunkIndex = cappedUploadedChunks;

                let encounteredError = null;

                let stopWorkers = false;

                const takeNextChunk = () => {

                    if (nextChunkIndex >= totalChunks) {

                        return null;

                    }

                    const index = nextChunkIndex;

                    nextChunkIndex += 1;

                    return index;

                };



                const worker = async () => {

                    while (true) {

                        if (entry.cancelRequested) {

                            throw new Error('cancelled');

                        }



                        if (stopWorkers) {

                            break;

                        }



                        const index = takeNextChunk();

                        if (index === null) {

                            break;

                        }



                        const start = index * chunkStride;

                        const end = Math.min(start + chunkStride, entry.file.size);

                        const chunk = entry.file.slice(start, end);



                        const createChunkFormData = () => {

                            const formData = new FormData();

                            formData.append('upload_id', entry.uploadId);

                            formData.append('chunk_index', index.toString());

                            formData.append('total_chunks', totalChunks.toString());

                            formData.append('file_name', entry.file.name);

                            formData.append('chunk', chunk, `${entry.file.name}.part${index}`);

                            formData.append('_token', csrfToken);

                            return formData;

                        };



                        try {
                            await uploadChunkWithRetry(entry, createChunkFormData, index, totalChunks, chunk.size);
                        } catch (error) {
                            stopWorkers = true;
                            throw error;
                        }

                        registerChunkCompletion(entry, index);

                    }

                };



                const workers = [];

                const maxWorkers = Math.max(

                    1,

                    Math.min(CHUNK_CONCURRENCY, totalChunks - cappedUploadedChunks)

                );



                for (let w = 0; w < maxWorkers; w += 1) {

                    workers.push(

                        worker().catch((error) => {

                            if (!encounteredError) {

                                encounteredError = error;

                            }

                            stopWorkers = true;

                        })

                    );

                }



                await Promise.all(workers);



                if (entry.cancelRequested) {

                    throw new Error('cancelled');

                }



                if (encounteredError) {

                    throw encounteredError;

                }



                await finalizeEntry(entry);



                entry.status = 'completed';

                entry.elements.cancelButton.classList.add('hidden');

                entry.elements.statusText.textContent = 'Upload complete';

                updateProgress(entry, 100);

                clearEntry(entry);

                notifySummary(`${entry.file.name} uploaded successfully.`, 'success');

            }

            async function finalizeEntry(entry) {
                const formData = new FormData();
                formData.append('upload_id', entry.uploadId);
                formData.append('file_name', entry.file.name);
                formData.append('title', entry.elements.titleInput.value.trim() || deriveDefaultTitle(entry.file.name));
                formData.append('description', entry.elements.descInput.value.trim());
                formData.append('total_chunks', entry.totalChunks.toString());
                formData.append('_token', csrfToken);

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

                if (entry.chunkControllers instanceof Map && entry.chunkControllers.size) {
                    abortActiveChunks(entry);
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
                refreshLiveProgress(entry);
                refreshUI();
            }

            function sendChunk(entry, formData, chunkIndex, totalChunks, chunkSize) {
                return new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', routes.chunk, true);
                    xhr.responseType = 'text';
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    xhr.withCredentials = true;

                    xhr.upload.onprogress = (event) => {
                        if (!event.lengthComputable || entry.cancelRequested) {
                            return;
                        }
                        recordChunkProgress(entry, chunkIndex, Math.min(event.loaded, chunkSize));
                    };

                    registerChunkController(entry, chunkIndex, {
                        abort: () => xhr.abort(),
                    });
                    recordChunkProgress(entry, chunkIndex, 0);

                    const cleanup = () => {
                        releaseChunkController(entry, chunkIndex);
                        clearChunkProgress(entry, chunkIndex);
                        refreshLiveProgress(entry);
                    };

                    xhr.onload = () => {
                        cleanup();
                        if (xhr.status >= 200 && xhr.status < 300) {
                            resolve();
                        } else {
                            reject(new Error(xhr.responseText || 'Chunk upload failed'));
                        }
                    };

                    xhr.onerror = () => {
                        cleanup();
                        reject(new Error('Network error'));
                    };
                    xhr.onabort = () => {
                        cleanup();
                        reject(new Error('cancelled'));
                    };

                    xhr.send(formData);
                });
            }

            async function uploadChunkWithRetry(
                entry,
                formDataFactory,
                chunkIndex,
                totalChunks,
                chunkSize,
                attempt = 1
            ) {
                try {
                    await sendChunk(entry, formDataFactory(), chunkIndex, totalChunks, chunkSize);
                } catch (error) {
                    releaseChunkController(entry, chunkIndex);
                    clearChunkProgress(entry, chunkIndex);

                    if (entry.cancelRequested) {
                        throw error;
                    }

                    if (attempt >= MAX_CHUNK_RETRIES) {
                        throw error;
                    }

                    const nextAttempt = attempt + 1;
                    const waitMs = Math.min(2000, 400 * attempt);
                    entry.elements.statusText.textContent = `Connection lost, retrying (attempt ${nextAttempt} of ${MAX_CHUNK_RETRIES})`;
                    if (attempt === 1) {
                        notifySummary(
                            `${entry.file.name} lost connection. Retrying chunk ${chunkIndex + 1}.`,
                            'error'
                        );
                    }
                    await delay(waitMs);
                    return uploadChunkWithRetry(
                        entry,
                        formDataFactory,
                        chunkIndex,
                        totalChunks,
                        chunkSize,
                        nextAttempt
                    );
                }
            }

            async function cleanupPartial(entry) {
                const formData = new FormData();
                formData.append('upload_id', entry.uploadId);
                formData.append('_token', csrfToken);

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
                } finally {
                    clearEntry(entry);
                }
            }

            function delay(ms) {
                return new Promise((resolve) => {
                    setTimeout(resolve, Math.max(0, ms || 0));
                });
            }

            function updateProgress(entry, percent) {
                const clamped = Math.max(0, Math.min(100, percent || 0));
                entry.elements.progressBar.style.width = `${clamped}%`;
            }

            function extractExtension(name = '') {
                const match = String(name).toLowerCase().match(/\.([a-z0-9]+)$/i);
                return match ? match[1] : '';
            }

            function isSupportedVideo(file) {
                if (!file) {
                    return false;
                }
                if (file.type && SUPPORTED_MIME_TYPES.has(file.type.toLowerCase())) {
                    return true;
                }
                return SUPPORTED_EXTENSIONS.has(extractExtension(file.name));
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

            function registerChunkCompletion(entry, chunkIndex) {
                if (!(entry.pendingCompletion instanceof Set)) {
                    entry.pendingCompletion = new Set();
                }
                entry.pendingCompletion.add(chunkIndex);
                let advanced = false;
                while (entry.pendingCompletion.has(entry.uploadedChunks)) {
                    entry.pendingCompletion.delete(entry.uploadedChunks);
                    entry.uploadedChunks += 1;
                    advanced = true;
                }
                if (advanced) {
                    persistEntry(entry);
                }
                refreshLiveProgress(entry);
            }

            function recordChunkProgress(entry, chunkIndex, loaded) {
                if (!(entry.partialProgress instanceof Map)) {
                    entry.partialProgress = new Map();
                }
                entry.partialProgress.set(chunkIndex, Math.max(0, loaded || 0));
                refreshLiveProgress(entry);
            }

            function clearChunkProgress(entry, chunkIndex) {
                if (entry.partialProgress instanceof Map) {
                    entry.partialProgress.delete(chunkIndex);
                }
            }

            function refreshLiveProgress(entry) {
                if (!entry.elements || !entry.elements.progressBar || !entry.file) {
                    return;
                }
                const chunkStride = getChunkSize(entry);
                const contiguousBytes = Math.min(entry.file.size, entry.uploadedChunks * chunkStride);
                let inflight = 0;
                if (entry.partialProgress instanceof Map) {
                    entry.partialProgress.forEach((value) => {
                        inflight += value || 0;
                    });
                }
                const totalBytes = Math.min(entry.file.size, contiguousBytes + inflight);
                const percent = entry.file.size ? Math.round((totalBytes / entry.file.size) * 100) : 0;
                const display = Math.max(1, Math.min(percent, 99));
                if (entry.status === 'completed') {
                    updateProgress(entry, 100);
                    return;
                }
                updateProgress(entry, display);
                if (entry.elements.statusText) {
                    entry.elements.statusText.textContent =
                        entry.uploadedChunks >= entry.totalChunks
                            ? 'Finalising...'
                            : `Uploading ${display}%`;
                }
            }

            function registerChunkController(entry, chunkIndex, controller) {
                if (!(entry.chunkControllers instanceof Map)) {
                    entry.chunkControllers = new Map();
                }
                entry.chunkControllers.set(chunkIndex, controller);
            }

            function releaseChunkController(entry, chunkIndex) {
                if (entry.chunkControllers instanceof Map) {
                    entry.chunkControllers.delete(chunkIndex);
                }
            }

            function abortActiveChunks(entry) {
                if (!(entry.chunkControllers instanceof Map)) {
                    return;
                }
                entry.chunkControllers.forEach((controller) => {
                    try {
                        controller.abort();
                    } catch (error) {
                        // ignore
                    }
                });
                entry.chunkControllers.clear();
            }

            function getChunkSize(entry) {
                if (entry && Number.isFinite(entry.chunkSize) && entry.chunkSize > 0) {
                    return entry.chunkSize;
                }
                return CHUNK_SIZE;
            }

            function createFingerprint(file) {
                if (!file) {
                    return '';
                }
                return [file.name || 'unknown', file.size || 0, file.lastModified || 0].join(':');
            }

            function persistEntry(entry) {
                if (!entry || !entry.fingerprint) {
                    return;
                }
                persistence.remember(entry.fingerprint, {
                    uploadId: entry.uploadId,
                    chunkSize: getChunkSize(entry),
                    totalChunks: entry.totalChunks,
                    uploadedChunks: entry.uploadedChunks || 0,
                });
            }

            function clearEntry(entry) {
                if (!entry) {
                    return;
                }
                if (entry.chunkControllers instanceof Map) {
                    entry.chunkControllers.clear();
                }
                if (entry.partialProgress instanceof Map) {
                    entry.partialProgress.clear();
                }
                if (entry.pendingCompletion instanceof Set) {
                    entry.pendingCompletion.clear();
                }
                if (entry.fingerprint) {
                    persistence.forget(entry.fingerprint);
                }
            }

            async function syncEntryStatus(entry) {
                if (!entry || !entry.needsRemoteSync || !entry.uploadId) {
                    return;
                }

                if (entry.elements && entry.elements.statusText) {
                    entry.elements.statusText.textContent = 'Checking uploaded data...';
                }

                try {
                    const response = await fetch(statusUrlFor(entry.uploadId), {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('Status lookup failed');
                    }

                    const payload = await response.json();
                    const contiguous = Number(payload && payload.contiguous_count) || 0;
                    const uploadedBytes =
                        Number(payload && payload.uploaded_bytes) || contiguous * getChunkSize(entry);

                    if (contiguous > 0) {
                        entry.uploadedChunks = Math.min(contiguous, entry.totalChunks);
                        const percent = entry.file && entry.file.size
                            ? Math.min(99, Math.round((uploadedBytes / entry.file.size) * 100))
                            : Math.min(99, Math.round((entry.uploadedChunks / entry.totalChunks) * 100));
                        updateProgress(entry, percent);
                        if (entry.elements && entry.elements.statusText) {
                            entry.elements.statusText.textContent = `Resuming ${percent}%`;
                        }
                    } else if (entry.elements && entry.elements.statusText) {
                        entry.elements.statusText.textContent = 'Queued for upload';
                        updateProgress(entry, 0);
                    }
                } catch (error) {
                    console.warn('Unable to sync upload progress', error);
                    if (entry.elements && entry.elements.statusText) {
                        entry.elements.statusText.textContent =
                            'Unable to read previous progress. Resuming from last saved chunk.';
                    }
                } finally {
                    entry.needsRemoteSync = false;
                    persistEntry(entry);
                }
            }

            function createPersistence(key) {
                const hasStorage = (() => {
                    try {
                        if (typeof window === 'undefined' || !('localStorage' in window)) {
                            return false;
                        }
                        const probeKey = `${key}::probe`;
                        window.localStorage.setItem(probeKey, '1');
                        window.localStorage.removeItem(probeKey);
                        return true;
                    } catch (error) {
                        return false;
                    }
                })();

                let cache = {};
                if (hasStorage) {
                    try {
                        const raw = window.localStorage.getItem(key);
                        cache = raw ? JSON.parse(raw) : {};
                    } catch (error) {
                        cache = {};
                    }
                }

                const flush = () => {
                    if (!hasStorage) {
                        return;
                    }
                    try {
                        window.localStorage.setItem(key, JSON.stringify(cache));
                    } catch (error) {
                        // ignore quota errors silently
                    }
                };

                return {
                    get: (fingerprint) => cache[fingerprint],
                    remember: (fingerprint, payload) => {
                        if (!fingerprint) {
                            return;
                        }
                        cache[fingerprint] = { ...(cache[fingerprint] || {}), ...payload };
                        flush();
                    },
                    forget: (fingerprint) => {
                        if (!fingerprint || !cache[fingerprint]) {
                            return;
                        }
                        delete cache[fingerprint];
                        flush();
                    },
                };
            }

        function determineRuntimeSettings() {
            const DESKTOP = 6 * 1024 * 1024;
            const MOBILE = 2 * 1024 * 1024;
            const SLOW = 1 * 1024 * 1024;
            const connection =
                navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            const effectiveType = connection && connection.effectiveType ? connection.effectiveType : '';
            const saveData = Boolean(connection && connection.saveData);
            const isMobileViewport = window.matchMedia
                ? window.matchMedia('(max-width: 640px)').matches
                : false;

            const settings = {
                chunkSize: DESKTOP,
                parallelUploads: 2,
                chunkConcurrency: 3,
                hint: 'Using accelerated desktop upload settings.',
            };

            if (saveData || ['slow-2g', '2g'].includes(effectiveType)) {
                settings.chunkSize = SLOW;
                settings.parallelUploads = 1;
                settings.chunkConcurrency = 1;
                settings.hint = 'Low-bandwidth mode enabled for reliable mobile uploads.';
                return settings;
            }

            if (['3g'].includes(effectiveType) || isMobileViewport) {
                settings.chunkSize = MOBILE;
                settings.parallelUploads = 1;
                settings.chunkConcurrency = 1;
                settings.hint = 'Optimised chunking for mobile connections.';
                return settings;
            }

            if (effectiveType === '4g' || effectiveType === 'wifi') {
                settings.chunkSize = 5 * 1024 * 1024;
                settings.parallelUploads = 2;
                settings.chunkConcurrency = 2;
                settings.hint = 'Balanced upload mode for typical Wi-Fi connections.';
                return settings;
            }

            return settings;
        }

            refreshUI();
        })();
    </script>
@endpush



