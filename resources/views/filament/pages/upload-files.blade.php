<x-filament-panels::page>
    <div
        class="mx-auto max-w-5xl"
        x-data="{
            selectedFile: null,
            uploadTitle: @entangle('uploadTitle').live,
            uploading: @entangle('isUploading').live,
            complete: @entangle('isComplete').live,
            progress: 0,
            uploadedBytes: 0,
            totalBytes: 0,
            timeRemaining: 'Calculating...',
            error: null,
            dragOver: false,
            abortController: null,
            uploadId: null,
            startTime: null,
            async startUpload() {
                if (! this.selectedFile) return;

                this.uploading = true;
                this.progress = 0;
                this.uploadedBytes = 0;
                this.totalBytes = this.selectedFile.size;
                this.error = null;
                this.abortController = new AbortController();
                this.uploadId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => { const r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16); });
                this.startTime = Date.now();

                const CHUNK_SIZE = 1024 * 1024;
                const totalChunks = Math.ceil(this.selectedFile.size / CHUNK_SIZE);
                const uploadUrl = @js(route('api.upload.chunk'));
                const csrfToken = document.querySelector('meta[name=csrf-token]').content;

                try {
                    for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                        if (this.abortController?.signal.aborted) throw new Error('Cancelled');

                        const start = chunkIndex * CHUNK_SIZE;
                        const end = Math.min(start + CHUNK_SIZE, this.selectedFile.size);
                        const chunk = this.selectedFile.slice(start, end);

                        const response = await fetch(uploadUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/octet-stream',
                                'Content-Range': 'bytes ' + start + '-' + (end - 1) + '/' + this.selectedFile.size,
                                'X-Upload-Id': this.uploadId,
                                'X-Chunk-Index': String(chunkIndex),
                                'X-Total-Chunks': String(totalChunks),
                                'X-Original-Name': btoa(unescape(encodeURIComponent(this.selectedFile.name))),
                                'X-Mime-Type': this.selectedFile.type || 'application/octet-stream',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: chunk,
                            signal: this.abortController.signal,
                        });

                        if (! response.ok) {
                            let message = 'Upload failed';
                            try {
                                const err = await response.json();
                                message = err.message || message;
                            } catch (_) {}

                            throw new Error(message);
                        }

                        const result = await response.json();
                        this.uploadedBytes = end;
                        this.progress = (end / this.selectedFile.size) * 100;

                        const elapsed = (Date.now() - this.startTime) / 1000;
                        const bps = this.uploadedBytes / Math.max(elapsed, 0.1);
                        const remaining = (this.totalBytes - this.uploadedBytes) / bps;
                        this.timeRemaining = remaining < 60 ? Math.ceil(remaining) + ' sec' : Math.ceil(remaining / 60) + ' min';

                        if (result.status === 'complete') {
                            await $wire.saveUpload(result.file_path, result.original_name, result.size, result.mime_type);
                        }
                    }
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        this.error = err.message;
                        this.uploading = false;
                    }
                }
            },
            cancelUpload() {
                if (this.abortController) {
                    this.abortController.abort();
                }

                this.uploading = false;
                this.progress = 0;
                this.uploadedBytes = 0;
            },
            resetAll() {
                this.selectedFile = null;
                this.uploadTitle = '';
                this.uploading = false;
                this.progress = 0;
                this.uploadedBytes = 0;
                this.totalBytes = 0;
                this.error = null;

                if (this.$refs.fileInput) {
                    this.$refs.fileInput.value = '';
                }

                $wire.resetUpload();
            },
        }"
        x-init="$watch('complete', value => { if (value) { uploading = false; } })"
        x-cloak
    >
        <x-filament::section
            heading="Chunked Upload"
            description="Send large files in resumable chunks without timing out."
            icon="heroicon-o-cloud-arrow-up"
        >
            <div class="flex flex-wrap items-center gap-3">
                <x-filament::badge color="primary" x-show="!uploading && !complete">
                    Ready
                </x-filament::badge>

                <x-filament::badge color="warning" x-show="uploading">
                    Uploading
                </x-filament::badge>

                <x-filament::badge color="success" x-show="complete">
                    Complete
                </x-filament::badge>
            </div>

            <x-filament::grid class="mt-6 gap-6" default="1" md="2">
                <div class="space-y-4">
                    <div
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="dragOver = false; if($event.dataTransfer.files.length > 0) { selectedFile = $event.dataTransfer.files[0]; error = null; }"
                        @click="$refs.fileInput.click()"
                        :class="{ 'ring-2 ring-primary-500 bg-primary-500/5': dragOver }"
                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white/60 p-6 text-center transition hover:border-primary-300 hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-500/70"
                    >
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                            <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-6 w-6" />
                        </div>

                        <p class="text-base font-semibold text-gray-900 dark:text-white">Drop a video file</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">or click to browse</p>

                        <input
                            type="file"
                            class="hidden"
                            x-ref="fileInput"
                            accept="video/*"
                            @change="if($event.target.files.length > 0) { selectedFile = $event.target.files[0]; error = null; }"
                        />
                    </div>

                    <div class="space-y-2" x-show="selectedFile">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                                    <x-filament::icon icon="heroicon-o-film" class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="selectedFile?.name"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="(selectedFile?.size / (1024*1024)).toFixed(2) + ' MB'"></p>
                                </div>
                            </div>

                            <x-filament::icon-button
                                color="gray"
                                icon="heroicon-o-x-mark"
                                class="rounded-full"
                                @click="selectedFile = null; $refs.fileInput.value = '';"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Title</p>
                        <x-filament::input
                            x-model="uploadTitle"
                            placeholder="Enter a title for your upload"
                            class="bg-white dark:bg-white/5"
                        />
                    </div>

                    <x-filament::button
                        color="primary"
                        size="lg"
                        class="w-full justify-center"
                        x-bind:disabled="!selectedFile || uploading"
                        @click="startUpload()"
                    >
                        <span x-show="!uploading">Start transfer</span>
                        <span x-show="uploading">Uploading...</span>
                    </x-filament::button>
                </div>

                <div class="space-y-4">
                    <x-filament::section heading="Progress" icon="heroicon-o-clock" compact>
                        <div class="space-y-2" x-show="uploading || complete">
                            <div class="relative h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                                <div
                                    class="h-full rounded-full bg-primary-500 transition-all"
                                    :style="`width: ${progress}%`"
                                ></div>
                            </div>

                            <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span x-text="Math.round(progress) + '%'"></span>
                                <span x-text="(uploadedBytes / (1024*1024)).toFixed(1) + ' / ' + (totalBytes / (1024*1024)).toFixed(1) + ' MB'"></span>
                            </div>

                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="timeRemaining + ' remaining'"></p>
                        </div>

                        <div class="flex items-center gap-2" x-show="!uploading && !complete">
                            <x-filament::badge color="gray">Awaiting file</x-filament::badge>
                        </div>

                        <div class="flex items-center gap-2" x-show="complete">
                            <x-filament::badge color="success">Saved</x-filament::badge>
                            <p class="text-sm text-gray-700 dark:text-gray-300">Your upload has been stored.</p>
                        </div>
                    </x-filament::section>

                    <div class="flex gap-3" x-show="uploading">
                        <x-filament::button color="gray" outlined class="flex-1 justify-center" @click="cancelUpload()">
                            Cancel
                        </x-filament::button>
                    </div>

                    <div
                        x-show="error"
                        class="flex items-start gap-3 rounded-xl border border-danger-200 bg-danger-50 p-3 text-sm text-danger-700 dark:border-danger-500/40 dark:bg-danger-500/10 dark:text-danger-200"
                    >
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-5 w-5" />
                        <span x-text="error"></span>
                    </div>

                    <div x-show="complete">
                        <x-filament::button color="primary" class="w-full justify-center" @click="resetAll()">
                            Upload another file
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::grid>
        </x-filament::section>
    </div>
</x-filament-panels::page>
