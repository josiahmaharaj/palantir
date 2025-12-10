@php
    $statePath = $getStatePath();
    $fieldId = $getId();
@endphp

<div
    x-data="streamingFileUpload(@js([
        'chunkSize' => $getChunkSize(),
        'endpoint' => $getUploadEndpoint(),
        'statePath' => $statePath,
        'fieldId' => $fieldId,
        'initialFile' => basename((string) $getState()),
    ]))"
    class="space-y-3"
>
    <div class="flex items-center gap-3">
        {{-- <button type="button" class="fi-btn fi-btn-primary" x-on:click="selectFile()">
            <span>Select file</span>
        </button> --}}
        <x-filament::button type="button" x-on:click="selectFile()">
            <span>Select file</span>
        </x-filament::button>
    </div>
    <div class="text-sm text-gray-700 dark:text-gray-300" x-text="fileName || 'No file selected'"></div>

    <input x-ref="fileInput" type="file" class="hidden" x-on:change="handleFileChosen">

    <template x-if="fileName">
        <div class="space-y-2">
            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300">
                <span x-text="statusText"></span>
                <span x-text="speedText"></span>
            </div>

            <div class="flex items-center gap-3">
            <div style="width: 100%;">
                <div style="width: 100%; background-color: #e0e0e0; padding: 3px; border-radius: 3px; box-shadow: inset 0 1px 3px rgba(0, 0, 0, .2);">
                    <span :style="`display: block; height: 22px; background-color: #659cef; border-radius: 3px; transition: width 500ms ease-in-out; width: ${progress}%;`"></span>
                </div>
            </div>
                <div class="flex items-center justify-end text-xs text-gray-600 dark:text-gray-300">
                    <span x-text="`${progress}%`"></span>
                </div>
            </div>
        </div>
    </template>

    <div class="text-xs text-danger-600 dark:text-danger-400" x-text="errorText" x-show="errorText"></div>
</div>

<script>
(function() {
    if (window.__streamingFileUploadRegistered) return;
    window.__streamingFileUploadRegistered = true;

    const generateUuid = () => {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        if (window.crypto && window.crypto.getRandomValues) {
            const buffer = new Uint8Array(16);
            window.crypto.getRandomValues(buffer);
            // Adapted UUID v4 fallback
            buffer[6] = (buffer[6] & 0x0f) | 0x40;
            buffer[8] = (buffer[8] & 0x3f) | 0x80;
            const toHex = (n) => n.toString(16).padStart(2, '0');
            return (
                toHex(buffer[0]) +
                toHex(buffer[1]) +
                toHex(buffer[2]) +
                toHex(buffer[3]) + '-' +
                toHex(buffer[4]) +
                toHex(buffer[5]) + '-' +
                toHex(buffer[6]) +
                toHex(buffer[7]) + '-' +
                toHex(buffer[8]) +
                toHex(buffer[9]) + '-' +
                toHex(buffer[10]) +
                toHex(buffer[11]) +
                toHex(buffer[12]) +
                toHex(buffer[13]) +
                toHex(buffer[14]) +
                toHex(buffer[15])
            );
        }

        return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    };

    const registerStreamingUpload = (Alpine) => {
        Alpine.data('streamingFileUpload', (config) => ({
            file: null,
            fileName: '',
            progress: 0,
            statusText: 'Waiting to start',
            speedText: '',
            errorText: '',
            uploadId: generateUuid(),
            nextChunk: 0,
            startTime: null,
            uploadedBytes: 0,
            initialFile: config.initialFile || '',

            init() {
                if (this.initialFile) {
                    this.fileName = this.initialFile;
                    this.progress = 100;
                    this.statusText = 'Existing file attached';
                }

                this.restoreState();
            },

            cacheKey() {
                return `stream-upload-${config.statePath}`;
            },

            persistState() {
                if (! this.file) {
                    localStorage.removeItem(this.cacheKey());

                    return;
                }

                localStorage.setItem(this.cacheKey(), JSON.stringify({
                    uploadId: this.uploadId,
                    nextChunk: this.nextChunk,
                    fileName: this.file.name,
                    size: this.file.size,
                }));
            },

            restoreState() {
                const cached = localStorage.getItem(this.cacheKey());
                if (! cached) {
                    return;
                }

                try {
                    const parsed = JSON.parse(cached);
                    this.uploadId = parsed.uploadId;
                    this.nextChunk = parsed.nextChunk;
                    this.fileName = parsed.fileName;
                    this.statusText = 'Ready to resume';
                } catch {
                    localStorage.removeItem(this.cacheKey());
                }
            },

            selectFile() {
                this.$refs.fileInput.click();
            },

            handleFileChosen(event) {
                const chosen = event.target.files?.[0];
                if (! chosen) {
                    return;
                }

                this.file = chosen;
                this.fileName = chosen.name;
                this.progress = 0;
                this.errorText = '';
                this.speedText = '';
                this.statusText = 'Preparing upload';
                this.uploadId = generateUuid();
                this.nextChunk = 0;
                this.persistState();

                this.startUpload();
            },

            async startUpload() {
                if (! this.file) {
                    return;
                }

                this.startTime = performance.now();
                this.uploadedBytes = this.nextChunk * config.chunkSize;
                this.statusText = 'Uploading...';

                while (this.nextChunk * config.chunkSize < this.file.size) {
                    try {
                        const response = await this.sendChunk(this.nextChunk);

                        if (response.status === 'complete') {
                            this.handleCompleted(response);

                            return;
                        }

                        this.nextChunk = response.next_chunk;
                        this.persistState();
                    } catch (error) {
                        this.errorText = error?.message ?? 'Upload failed';
                        this.statusText = 'Paused after error';
                        this.persistState();

                        return;
                    }
                }
            },

            sendChunk(index, attempt = 1) {
                return new Promise((resolve, reject) => {
                    const chunkStart = index * config.chunkSize;
                    const chunkEnd = Math.min(chunkStart + config.chunkSize, this.file.size);
                    const chunk = this.file.slice(chunkStart, chunkEnd);
                    const xhr = new XMLHttpRequest();

                    xhr.open('POST', config.endpoint, true);
                    xhr.responseType = 'json';
                    xhr.withCredentials = true;

                    xhr.setRequestHeader('Content-Range', `bytes ${chunkStart}-${chunkEnd - 1}/${this.file.size}`);
                    xhr.setRequestHeader('X-Upload-Id', this.uploadId);
                    xhr.setRequestHeader('X-Chunk-Index', index.toString());
                    xhr.setRequestHeader('X-Total-Chunks', Math.ceil(this.file.size / config.chunkSize).toString());
                    xhr.setRequestHeader('X-Original-Name', encodeURIComponent(this.file.name));
                    xhr.setRequestHeader('X-Mime-Type', this.file.type || 'application/octet-stream');
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');

                    xhr.upload.onprogress = (event) => {
                        if (! event.lengthComputable) {
                            return;
                        }

                        const absoluteUploaded = chunkStart + event.loaded;
                        this.uploadedBytes = absoluteUploaded;
                        this.progress = Math.min(100, Math.round((absoluteUploaded / this.file.size) * 100));

                        const elapsedSeconds = (performance.now() - this.startTime) / 1000;
                        if (elapsedSeconds > 0) {
                            const speed = this.uploadedBytes / elapsedSeconds;
                            this.speedText = `${this.formatBytes(speed)}/s`;
                        }
                    };

                    xhr.onerror = () => {
                        if (attempt < 3) {
                            resolve(this.sendChunk(index, attempt + 1));
                        } else {
                            reject(new Error('Network error while uploading.'));
                        }
                    };

                    xhr.onload = () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            const payload = xhr.response ?? {};
                            if (payload.next_chunk && payload.next_chunk < index) {
                                this.progress = Math.round((payload.next_chunk * config.chunkSize / this.file.size) * 100);
                            }

                            resolve(payload);
                        } else if (xhr.status === 429 && attempt < 6) {
                            const backoff = 500 * attempt;
                            setTimeout(() => resolve(this.sendChunk(index, attempt + 1)), backoff);
                        } else if (attempt < 3) {
                            resolve(this.sendChunk(index, attempt + 1));
                        } else {
                            const message = xhr.response?.message ?? xhr.responseText ?? 'Upload failed.';
                            reject(new Error(message));
                        }
                    };

                    xhr.send(chunk);
                });
            },

            handleCompleted(payload) {
                this.progress = 100;
                this.statusText = 'Upload complete';
                this.speedText = '';
                this.errorText = '';
                localStorage.removeItem(this.cacheKey());
                this.nextChunk = 0;

                this.$wire.$set(config.statePath, payload.file_path);
                this.$wire.$dispatch('streaming-upload-completed', {
                    file_path: payload.file_path,
                    original_name: payload.original_name,
                    size: payload.size,
                    mime_type: payload.mime_type,
                });
            },

            formatBytes(bytes) {
                if (bytes === 0) return '0 B';
                const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                const i = Math.floor(Math.log(bytes) / Math.log(1024));

                return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
            },
        }));
    };

    const boot = () => registerStreamingUpload(window.Alpine);

    if (window.Alpine) {
        boot();
    } else {
        document.addEventListener('alpine:init', boot, { once: true });
    }
})();
</script>
