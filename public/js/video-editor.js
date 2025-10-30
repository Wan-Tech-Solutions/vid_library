/**
 * VideoEditorKit - lightweight client-side editing helpers powered by FFmpeg.wasm.
 * Features: trimming, basic color filters, movable text overlay, re-encoding.
 *
 * Expected global: window.FFmpeg (loaded via @ffmpeg/ffmpeg CDN bundle)
 */
(function () {
    if (window.VideoEditorKit) {
        return;
    }

    if (!window.FFmpeg) {
        console.error('FFmpeg.wasm is required before loading VideoEditorKit.');
        return;
    }

    const { createFFmpeg, fetchFile } = window.FFmpeg;
    const DEFAULT_FONT_URL = 'https://raw.githubusercontent.com/google/fonts/main/apache/roboto/Roboto-Regular.ttf';
    const FONT_FILE_NAME = 'overlay-font.ttf';

    const ffmpegState = {
        instance: null,
        loadingPromise: null,
        fontLoaded: false,
    };

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function formatSeconds(seconds) {
        if (!Number.isFinite(seconds)) {
            return '0.0';
        }
        return seconds.toFixed(seconds >= 10 ? 1 : 2);
    }

    function escapeDrawtextValue(value) {
        return (value || '')
            .replace(/\\/g, '\\\\')
            .replace(/:/g, '\\:')
            .replace(/'/g, "\\'");
    }

    async function ensureFFmpeg() {
        if (ffmpegState.instance) {
            return ffmpegState.instance;
        }
        if (ffmpegState.loadingPromise) {
            return ffmpegState.loadingPromise;
        }
        ffmpegState.loadingPromise = (async () => {
            const ffmpeg = createFFmpeg({ log: false });
            await ffmpeg.load();
            ffmpegState.instance = ffmpeg;
            return ffmpeg;
        })();
        return ffmpegState.loadingPromise;
    }

    async function ensureFontLoaded(ffmpeg, fontUrl) {
        if (ffmpegState.fontLoaded) {
            return;
        }
        const url = fontUrl || DEFAULT_FONT_URL;
        const response = await fetch(url, { mode: 'cors' });
        if (!response.ok) {
            throw new Error('Unable to download font for text overlay.');
        }
        const buffer = new Uint8Array(await response.arrayBuffer());
        await ffmpeg.writeFile(FONT_FILE_NAME, buffer);
        ffmpegState.fontLoaded = true;
    }

    function buildEditingUI(state) {
        const container = document.createElement('div');
        container.className = 'mt-4 border-t border-gray-200 dark:border-gray-700 pt-4 space-y-5';
        container.innerHTML = `
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 uppercase tracking-wide">Editing Tools</h4>
                <div class="flex items-center gap-2">
                    <button type="button" data-role="reset"
                        class="px-3 py-1 text-xs rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                        Reset
                    </button>
                    <button type="button" data-role="apply"
                        class="px-3 py-1 text-xs rounded-md bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed">
                        Apply Edits
                    </button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Trim Start (s)</label>
                        <input type="range" min="0" value="${state.trimStart}" step="0.1" data-role="trim-start-range"
                            class="w-full accent-indigo-600">
                        <input type="number" min="0" value="${state.trimStart}" step="0.1" data-role="trim-start-input"
                            class="mt-1 w-full rounded border border-gray-300 px-2 py-1 text-sm dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Trim End (s)</label>
                        <input type="range" min="0" value="${state.trimEnd}" step="0.1" data-role="trim-end-range"
                            class="w-full accent-indigo-600">
                        <input type="number" min="0" value="${state.trimEnd}" step="0.1" data-role="trim-end-input"
                            class="mt-1 w-full rounded border border-gray-300 px-2 py-1 text-sm dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Brightness</label>
                        <input type="range" min="-0.5" max="0.5" step="0.05" value="${state.brightness}" data-role="brightness"
                            class="w-full accent-indigo-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Contrast</label>
                        <input type="range" min="0.5" max="1.5" step="0.05" value="${state.contrast}" data-role="contrast"
                            class="w-full accent-indigo-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Saturation</label>
                        <input type="range" min="0" max="2" step="0.05" value="${state.saturation}" data-role="saturation"
                            class="w-full accent-indigo-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Hue (°)</label>
                        <input type="range" min="-180" max="180" step="5" value="${state.hue}" data-role="hue"
                            class="w-full accent-indigo-600">
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Text Overlay</label>
                <input type="text" placeholder="Add overlay text..." value="${state.text}"
                    data-role="text-value"
                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100"
                >
                <div class="flex flex-wrap items-center gap-3">
                    <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                        Color
                        <input type="color" value="${state.textColor}" data-role="text-color" class="h-7 w-12 border-0 p-0 bg-transparent">
                    </label>
                    <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                        Size
                        <input type="range" min="18" max="96" step="2" value="${state.fontSize}" data-role="font-size"
                            class="accent-indigo-600">
                        <span data-role="font-size-value" class="text-xs font-semibold text-gray-500 dark:text-gray-300">${Math.round(state.fontSize)}px</span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Drag the text on the video preview to reposition it.
                </p>
            </div>

            <p data-role="status" class="text-xs text-gray-500 dark:text-gray-400 italic">No edits applied.</p>
        `;

        return {
            container,
            applyButton: container.querySelector('[data-role="apply"]'),
            resetButton: container.querySelector('[data-role="reset"]'),
            trimStartRange: container.querySelector('[data-role="trim-start-range"]'),
            trimStartInput: container.querySelector('[data-role="trim-start-input"]'),
            trimEndRange: container.querySelector('[data-role="trim-end-range"]'),
            trimEndInput: container.querySelector('[data-role="trim-end-input"]'),
            brightness: container.querySelector('[data-role="brightness"]'),
            contrast: container.querySelector('[data-role="contrast"]'),
            saturation: container.querySelector('[data-role="saturation"]'),
            hue: container.querySelector('[data-role="hue"]'),
            textValue: container.querySelector('[data-role="text-value"]'),
            textColor: container.querySelector('[data-role="text-color"]'),
            fontSize: container.querySelector('[data-role="font-size"]'),
            fontSizeValue: container.querySelector('[data-role="font-size-value"]'),
            status: container.querySelector('[data-role="status"]'),
        };
    }

    function createOverlayElement() {
        const overlay = document.createElement('div');
        overlay.className =
            'absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-white font-semibold drop-shadow-lg cursor-grab select-none hidden';
        overlay.style.pointerEvents = 'auto';
        overlay.style.whiteSpace = 'pre-wrap';
        overlay.style.textAlign = 'center';
        overlay.dataset.role = 'video-text-overlay';
        return overlay;
    }

    function updateOverlayFromState(overlay, state) {
        if (!overlay) {
            return;
        }
        if (!state.text) {
            overlay.classList.add('hidden');
            return;
        }
        overlay.classList.remove('hidden');
        overlay.textContent = state.text;
        overlay.style.fontSize = `${state.fontSize}px`;
        overlay.style.color = state.textColor;
        overlay.style.left = `${state.textX * 100}%`;
        overlay.style.top = `${state.textY * 100}%`;
        overlay.style.transform = 'translate(-50%, -50%)';
    }

    function applyCssFilters(videoElement, state) {
        if (!videoElement) {
            return;
        }
        const brightness = 1 + state.brightness;
        const contrast = state.contrast;
        const saturation = state.saturation;
        const hue = state.hue;
        videoElement.style.filter = `brightness(${brightness.toFixed(2)}) contrast(${contrast.toFixed(
            2,
        )}) saturate(${saturation.toFixed(2)}) hue-rotate(${hue.toFixed(0)}deg)`;
    }

    function attach(options) {
        const {
            videoElement,
            previewContainer,
            controlsContainer,
            getFile,
            setFile,
            onStatus,
            fontUrl,
        } = options;

        if (!(videoElement instanceof HTMLVideoElement)) {
            throw new Error('VideoEditorKit.attach requires a <video> element.');
        }

        if (!previewContainer || !(previewContainer instanceof HTMLElement)) {
            throw new Error('VideoEditorKit.attach requires a preview container element.');
        }

        const state = {
            duration: 0,
            trimStart: 0,
            trimEnd: 0,
            brightness: 0,
            contrast: 1,
            saturation: 1,
            hue: 0,
            text: '',
            textColor: '#ffffff',
            fontSize: 48,
            textX: 0.5,
            textY: 0.8,
            isProcessing: false,
        };

        previewContainer.classList.add('relative');
        previewContainer.style.userSelect = 'none';

        const overlay = createOverlayElement();
        previewContainer.appendChild(overlay);

        const elements = buildEditingUI(state);
        controlsContainer.appendChild(elements.container);

        function updateStatus(message, type) {
            if (elements.status) {
                elements.status.textContent = message;
                elements.status.dataset.state = type || 'info';
                elements.status.className =
                    'text-xs mt-1 ' +
                    (type === 'error'
                        ? 'text-red-500 dark:text-red-400'
                        : type === 'success'
                        ? 'text-green-600 dark:text-green-400'
                        : 'text-gray-500 dark:text-gray-400');
            }
            if (onStatus) {
                onStatus(message, type || 'info');
            }
        }

        function syncTrimInputs() {
            const max = state.duration || 0;
            const start = clamp(state.trimStart, 0, Math.max(0, max - 0.1));
            const end = clamp(state.trimEnd, start + 0.1, max || start + 0.1);

            state.trimStart = start;
            state.trimEnd = end;

            elements.trimStartRange.max = max;
            elements.trimStartInput.max = max;
            elements.trimEndRange.max = max;
            elements.trimEndInput.max = max;

            elements.trimStartRange.value = start;
            elements.trimStartInput.value = formatSeconds(start);
            elements.trimEndRange.value = end;
            elements.trimEndInput.value = formatSeconds(end);
        }

        function setHue(deg) {
            state.hue = clamp(deg, -180, 180);
            elements.hue.value = state.hue;
            applyCssFilters(videoElement, state);
        }

        function setBrightness(value) {
            state.brightness = clamp(value, -0.5, 0.5);
            elements.brightness.value = state.brightness;
            applyCssFilters(videoElement, state);
        }

        function setContrast(value) {
            state.contrast = clamp(value, 0.5, 1.5);
            elements.contrast.value = state.contrast;
            applyCssFilters(videoElement, state);
        }

        function setSaturation(value) {
            state.saturation = clamp(value, 0, 2);
            elements.saturation.value = state.saturation;
            applyCssFilters(videoElement, state);
        }

        function setText(value) {
            state.text = value.slice(0, 120);
            elements.textValue.value = state.text;
            updateOverlayFromState(overlay, state);
        }

        function setTextColor(color) {
            state.textColor = color || '#ffffff';
            elements.textColor.value = state.textColor;
            updateOverlayFromState(overlay, state);
        }

        function setFontSize(size) {
            state.fontSize = clamp(size, 18, 96);
            elements.fontSize.value = state.fontSize;
            elements.fontSizeValue.textContent = `${Math.round(state.fontSize)}px`;
            updateOverlayFromState(overlay, state);
        }

        function setTrimStart(value) {
            state.trimStart = clamp(value, 0, Math.max(0, state.duration - 0.1));
            if (state.trimStart >= state.trimEnd) {
                state.trimEnd = clamp(state.trimStart + 0.1, 0.1, state.duration);
            }
            syncTrimInputs();
        }

        function setTrimEnd(value) {
            state.trimEnd = clamp(value, 0.1, state.duration || value);
            if (state.trimEnd <= state.trimStart) {
                state.trimStart = clamp(state.trimEnd - 0.1, 0, Math.max(0, state.duration - 0.1));
            }
            syncTrimInputs();
        }

        function resetControls() {
            state.trimStart = 0;
            state.trimEnd = state.duration;
            state.brightness = 0;
            state.contrast = 1;
            state.saturation = 1;
            state.hue = 0;
            state.text = '';
            state.textColor = '#ffffff';
            state.fontSize = 48;
            state.textX = 0.5;
            state.textY = 0.8;
            applyCssFilters(videoElement, state);
            updateOverlayFromState(overlay, state);
            syncTrimInputs();
            setTextColor(state.textColor);
            setFontSize(state.fontSize);
            updateStatus('Controls reset. Remember to re-apply edits.', 'info');
        }

        function updateDurationFromVideo() {
            const duration = Number.isFinite(videoElement.duration) ? videoElement.duration : 0;
            state.duration = duration;
            if (!Number.isFinite(state.trimEnd) || state.trimEnd === 0) {
                state.trimEnd = duration;
            } else {
                state.trimEnd = Math.min(state.trimEnd, duration);
            }
            syncTrimInputs();
        }

        function handleDrag(event) {
            if (state.isProcessing) {
                return;
            }
            const rect = previewContainer.getBoundingClientRect();
            const relativeX = clamp((event.clientX - rect.left) / rect.width, 0.02, 0.98);
            const relativeY = clamp((event.clientY - rect.top) / rect.height, 0.05, 0.95);
            state.textX = relativeX;
            state.textY = relativeY;
            updateOverlayFromState(overlay, state);
        }

        let draggingPointerId = null;

        overlay.addEventListener('pointerdown', (event) => {
            draggingPointerId = event.pointerId;
            overlay.setPointerCapture(draggingPointerId);
            overlay.classList.replace('cursor-grab', 'cursor-grabbing');
        });

        overlay.addEventListener('pointermove', (event) => {
            if (draggingPointerId === null || event.pointerId !== draggingPointerId) {
                return;
            }
            handleDrag(event);
        });

        const stopDragging = (event) => {
            if (draggingPointerId === null || event.pointerId !== draggingPointerId) {
                return;
            }
            overlay.releasePointerCapture(draggingPointerId);
            draggingPointerId = null;
            overlay.classList.remove('cursor-grabbing');
            overlay.classList.add('cursor-grab');
        };

        overlay.addEventListener('pointerup', stopDragging);
        overlay.addEventListener('pointercancel', stopDragging);

        const allTrimInputs = [
            [elements.trimStartRange, setTrimStart],
            [elements.trimStartInput, (value) => setTrimStart(parseFloat(value) || 0)],
            [elements.trimEndRange, setTrimEnd],
            [elements.trimEndInput, (value) => setTrimEnd(parseFloat(value) || 0)],
        ];

        allTrimInputs.forEach(([el, setter]) => {
            el.addEventListener('input', (event) => {
                setter(parseFloat(event.target.value) || 0);
            });
        });

        elements.brightness.addEventListener('input', (event) => {
            setBrightness(parseFloat(event.target.value) || 0);
        });

        elements.contrast.addEventListener('input', (event) => {
            setContrast(parseFloat(event.target.value) || 1);
        });

        elements.saturation.addEventListener('input', (event) => {
            setSaturation(parseFloat(event.target.value) || 1);
        });

        elements.hue.addEventListener('input', (event) => {
            setHue(parseFloat(event.target.value) || 0);
        });

        elements.textValue.addEventListener('input', (event) => {
            setText(event.target.value || '');
        });

        elements.textColor.addEventListener('input', (event) => {
            setTextColor(event.target.value || '#ffffff');
        });

        elements.fontSize.addEventListener('input', (event) => {
            setFontSize(parseFloat(event.target.value) || 48);
        });

        elements.resetButton.addEventListener('click', () => {
            resetControls();
        });

        async function applyEdits() {
            if (state.isProcessing) {
                return;
            }

            const file = typeof getFile === 'function' ? getFile() : null;
            if (!file) {
                updateStatus('No video selected for editing.', 'error');
                return;
            }

            const duration = state.trimEnd - state.trimStart;
            if (!(duration > 0.2)) {
                updateStatus('Trim duration must be greater than 0.2 seconds.', 'error');
                return;
            }

            state.isProcessing = true;
            elements.applyButton.disabled = true;
            updateStatus('Preparing FFmpeg...', 'info');

            try {
                const ffmpeg = await ensureFFmpeg();
                await ensureFontLoaded(ffmpeg, fontUrl);

                updateStatus('Applying edits. This may take a moment…', 'info');

                const inputName = `${crypto.randomUUID()}.mp4`;
                const outputName = `${crypto.randomUUID()}-edited.mp4`;

                await ffmpeg.writeFile(inputName, await fetchFile(file));

                const filters = [];
                filters.push(
                    `eq=brightness=${state.brightness.toFixed(2)},contrast=${state.contrast.toFixed(
                        2,
                    )},saturation=${state.saturation.toFixed(2)}`,
                );

                    if (state.hue !== 0) {
                    filters.push(`hue=h=${state.hue.toFixed(2)}`);
                }

                if (state.text) {
                    const escapedText = escapeDrawtextValue(state.text);
                    const xExpr = `(${state.textX.toFixed(3)}*W-text_w/2)`;
                    const yExpr = `(${state.textY.toFixed(3)}*H-text_h/2)`;
                    filters.push(
                        `drawtext=text='${escapedText}':fontfile=${FONT_FILE_NAME}:fontsize=${Math.round(
                            state.fontSize,
                        )}:fontcolor=${state.textColor}:x=${xExpr}:y=${yExpr}:borderw=2:bordercolor=#000000aa`,
                    );
                }

                const args = ['-i', inputName, '-ss', state.trimStart.toFixed(2), '-t', duration.toFixed(2)];

                if (filters.length) {
                    args.push('-vf', filters.join(','));
                }

                args.push(
                    '-c:v',
                    'libx264',
                    '-preset',
                    'veryfast',
                    '-movflags',
                    'faststart',
                    '-c:a',
                    'copy',
                    outputName,
                );

                await ffmpeg.exec(args);

                const fileData = await ffmpeg.readFile(outputName);
                const blob = new Blob([fileData], { type: 'video/mp4' });
                const editedFile = new File([blob], generateEditedFilename(file.name), { type: 'video/mp4' });

                if (typeof setFile === 'function') {
                    setFile(editedFile);
                }

                updateStatus('Edits applied. Preview has been updated.', 'success');

                await ffmpeg.deleteFile(inputName);
                await ffmpeg.deleteFile(outputName);
            } catch (error) {
                console.error(error);
                updateStatus(error.message || 'Failed to apply video edits.', 'error');
            } finally {
                elements.applyButton.disabled = false;
                state.isProcessing = false;
            }
        }

        elements.applyButton.addEventListener('click', () => {
            applyEdits();
        });

        videoElement.addEventListener('loadedmetadata', () => {
            updateDurationFromVideo();
            applyCssFilters(videoElement, state);
        });

        // Initialize UI with defaults.
        resetControls();
        updateOverlayFromState(overlay, state);

        return {
            state,
            elements,
            overlay,
            updateStatus,
            rehydrate() {
                updateDurationFromVideo();
                applyCssFilters(videoElement, state);
                updateOverlayFromState(overlay, state);
                updateStatus('Video source updated. Adjust settings and apply again if needed.', 'info');
            },
        };
    }

    function generateEditedFilename(originalName) {
        const parts = originalName.split('.');
        if (parts.length < 2) {
            return `${originalName}-edited.mp4`;
        }
        const extension = parts.pop();
        const base = parts.join('.');
        return `${base}-edited.${extension}`;
    }

    window.VideoEditorKit = {
        attach,
    };
})();
