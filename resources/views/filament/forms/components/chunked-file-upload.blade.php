@php
    $statePath = $getStatePath();
    $fieldId = $getId();
    $chunkSizeLabel = number_format($getChunkSize() / (1024 * 1024), 1);
@endphp

<div
    x-data="streamingFileUpload(@js([
        'chunkSize' => $getChunkSize(),
        'endpoint' => $getUploadEndpoint(),
        'statePath' => $statePath,
        'fieldId' => $fieldId,
        'initialFile' => basename((string) $getState()),
    ]))"
    x-cloak
    class="space-y-4 rounded-xl border border-gray-200/80 bg-white/70 p-4 shadow-sm dark:border-white/10 dark:bg-white/5"
>
    <div class="flex items-center justify-between gap-3">
        <div class="space-y-1">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                Chunked upload
            </p>
            <p class="text-xs text-gray-600 dark:text-gray-300">
                Large files are streamed in chunks to avoid timeouts.
            </p>
        </div>
        <x-filament::badge
            :color="\"primary\""
            x-text="progress >= 100 ? 'Ready' : (file ? 'Selected' : 'Waiting')"
        />
    </div>

    <div
        class="flex flex-col items-center justify-center rounded-lg border border-dashed border-gray-200 bg-white/80 p-4 text-center transition hover:border-primary-300 hover:bg-white dark:border-white/10 dark:bg-white/10 dark:hover:border-primary-500/60"
        :class="{ 'ring-2 ring-primary-500 bg-primary-50/50 dark:bg-primary-500/10': dragOver }"
        @dragover.prevent="dragOver = true"
        @dragleave.prevent="dragOver = false"
        @drop.prevent="dragOver = false; if ($event.dataTransfer.files.length > 0) { useFile($event.dataTransfer.files[0]); }"
        @click="selectFile()"
        x-show="!fileName"
    >
        <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="h-8 w-8 text-primary-500" />
        <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Drop a file or click to browse</p>
        <p class="text-xs text-gray-600 dark:text-gray-300">Uploads continue in {{ $chunkSizeLabel }}MB chunks</p>
        <input x-ref="fileInput" type="file" class="hidden" x-on:change="handleFileChosen">
    </div>

    <template x-if="fileName || initialFile">
        <div class="space-y-2 rounded-lg border border-gray-200/80 bg-gray-50/60 p-3 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                    <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 text-primary-500" />
                    <span x-text="fileName || initialFile"></span>
                </div>
                <span class="text-xs text-gray-600 dark:text-gray-300" x-text="`${progress}%`"></span>
            </div>

            <div class="relative h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                <div
                    class="h-full rounded-full bg-primary-500 transition-all"
                    :style="`width: ${progress}%;`"
                ></div>
            </div>

            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300">
                <span x-text="statusText"></span>
                <span x-text="speedText"></span>
            </div>

            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300">
                <span x-text="formatBytes(uploadedBytes)"></span>
                <span x-text="file ? formatBytes(file.size) : ''"></span>
            </div>
        </div>
    </template>

    <div
        class="flex items-center gap-2 text-sm text-danger-600 dark:text-danger-400"
        x-show="errorText"
    >
        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5" />
        <span x-text="errorText"></span>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-filament::button
            color="primary"
            type="button"
            x-show="fileName"
            x-bind:disabled="!file || isUploading"
            x-on:click="startUpload()"
        >
            <span x-show="isUploading">Uploading...</span>
            <span x-show="!isUploading && progress === 0">Start upload</span>
            <span x-show="!isUploading && progress > 0 && progress < 100">Resume upload</span>
            <span x-show="!isUploading && progress >= 100">Re-upload</span>
        </x-filament::button>

        {{-- <x-filament::button color="gray" type="button" outlined x-on:click="selectFile()">
            Choose different file
        </x-filament::button> --}}

        <x-filament::button
            color="gray"
            type="button"
            outlined
            x-show="fileName"
            x-on:click="resetSelection()"
        >
            Clear selection
        </x-filament::button>
    </div>
</div>

@include('filament.forms.components.partials.streaming-file-upload-script')
