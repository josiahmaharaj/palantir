<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-md"
         x-data="{
            selectedFile: null,
            title: @entangle('title'),
            dragOver: false,
            uploading: false,
            complete: @entangle('isComplete'),
            progress: 0,
            uploadedBytes: 0,
            totalBytes: 0,
            timeRemaining: 'Calculating...',
            error: null,
            abortController: null,
            uploadId: null,
            startTime: null,
            async startUpload() {
                if (!this.selectedFile) return;
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
                        if (this.abortController.signal.aborted) throw new Error('Cancelled');

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
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: chunk,
                            signal: this.abortController.signal
                        });

                        if (!response.ok) {
                            const err = await response.json();
                            throw new Error(err.message || 'Upload failed');
                        }

                        const result = await response.json();
                        this.uploadedBytes = end;
                        this.progress = (end / this.selectedFile.size) * 100;

                        const elapsed = (Date.now() - this.startTime) / 1000;
                        const bps = this.uploadedBytes / elapsed;
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
                if (this.abortController) this.abortController.abort();
                this.uploading = false;
                this.progress = 0;
                this.uploadedBytes = 0;
            },
            resetAll() {
                this.selectedFile = null;
                this.title = '';
                this.uploading = false;
                this.progress = 0;
                this.uploadedBytes = 0;
                this.totalBytes = 0;
                this.error = null;
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                $wire.resetUpload();
            }
         }"
         x-init="$watch('complete', value => { if(value) { uploading = false; } })">

        {{-- Upload Form --}}
        <div x-show="!uploading && !complete"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">

            <h2 class="mb-6 text-center text-xl font-semibold text-gray-900 dark:text-white">Upload Files</h2>

            {{-- Drop Zone --}}
            <div class="mb-6 grid grid-cols-2 gap-4">
                <label @dragover.prevent="dragOver = true"
                       @dragleave.prevent="dragOver = false"
                       @drop.prevent="dragOver = false; if($event.dataTransfer.files.length > 0) { selectedFile = $event.dataTransfer.files[0]; error = null; }"
                       :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/20': dragOver }"
                       class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 p-6 transition-all hover:border-blue-400 hover:bg-blue-50 dark:border-gray-600 dark:bg-gray-700/50 dark:hover:border-blue-500 dark:hover:bg-blue-900/20">
                    <div class="mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Add files</span>
                    <input type="file"
                           x-ref="fileInput"
                           @change="if($event.target.files.length > 0) { selectedFile = $event.target.files[0]; error = null; }"
                           class="hidden"
                           accept="video/*">
                </label>

                <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 p-6 opacity-50 dark:border-gray-600 dark:bg-gray-700/50">
                    <div class="mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-600">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-400">Add folders</span>
                </div>
            </div>

            {{-- Selected File Display --}}
            <template x-if="selectedFile">
                <div class="mb-4 flex items-center gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-700/50">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white" x-text="selectedFile.name"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="(selectedFile.size / (1024*1024)).toFixed(2) + ' MB'"></p>
                    </div>
                    <button @click="selectedFile = null; $refs.fileInput.value = '';" type="button" class="rounded-full p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>

            {{-- Title Input --}}
            <div class="mb-6">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                <input type="text"
                       x-model="title"
                       placeholder="Enter a title for your upload"
                       class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 dark:focus:border-blue-500">
            </div>

            {{-- Transfer Button --}}
            <button @click="startUpload()"
                    type="button"
                    :disabled="!selectedFile"
                    :class="{ 'opacity-50 cursor-not-allowed': !selectedFile }"
                    class="w-full rounded-xl bg-blue-600 px-6 py-4 text-base font-semibold text-white transition-all hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/30">
                Transfer
            </button>
        </div>

        {{-- Uploading State --}}
        <div x-show="uploading"
             x-cloak
             x-transition
             class="rounded-2xl bg-white p-8 text-center shadow-xl dark:bg-gray-800">

            <div class="relative mx-auto mb-6 h-40 w-40">
                <svg class="h-full w-full -rotate-90 transform" viewBox="0 0 100 100">
                    <circle class="stroke-gray-200 dark:stroke-gray-700" stroke-width="8" fill="none" r="42" cx="50" cy="50"/>
                    <circle class="stroke-blue-600 transition-all duration-300" stroke-width="8" fill="none" r="42" cx="50" cy="50" stroke-linecap="round" stroke-dasharray="264" :stroke-dashoffset="264 - (264 * progress / 100)"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-4xl font-bold text-gray-900 dark:text-white" x-text="Math.round(progress)"></span>
                    <span class="text-lg text-gray-400">%</span>
                </div>
            </div>

            <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">Transferring ...</h3>
            <p class="mb-1 text-sm text-blue-600 dark:text-blue-400">Sending 1 file</p>
            <p class="mb-1 text-sm text-gray-600 dark:text-gray-400">
                <span x-text="(uploadedBytes / (1024*1024)).toFixed(1) + ' MB'"></span> of <span x-text="(totalBytes / (1024*1024)).toFixed(1) + ' MB'"></span> uploaded
            </p>
            <p class="mb-6 text-sm text-gray-500" x-text="timeRemaining + ' remaining'"></p>

            <button @click="cancelUpload()"
                    type="button"
                    class="rounded-xl border-2 border-blue-600 px-8 py-3 text-sm font-semibold text-blue-600 transition-all hover:bg-blue-50 dark:border-blue-500 dark:text-blue-500 dark:hover:bg-blue-900/20">
                Cancel
            </button>
        </div>

        {{-- Complete State --}}
        <div x-show="complete"
             x-cloak
             x-transition
             class="rounded-2xl bg-white p-8 text-center shadow-xl dark:bg-gray-800">

            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                <svg class="h-10 w-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">Upload Complete!</h3>
            <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">Your file has been successfully uploaded.</p>

            <button @click="resetAll()"
                    type="button"
                    class="w-full rounded-xl bg-blue-600 px-6 py-4 text-base font-semibold text-white transition-all hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/30">
                Upload Another File
            </button>
        </div>

        {{-- Error State --}}
        <div x-show="error"
             x-cloak
             x-transition
             class="mt-4 rounded-xl bg-red-50 p-4 dark:bg-red-900/20">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-red-600 dark:text-red-400" x-text="error"></p>
            </div>
        </div>
    </div>
</div>
