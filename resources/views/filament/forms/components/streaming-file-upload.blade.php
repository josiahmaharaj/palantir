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

@include('filament.forms.components.partials.streaming-file-upload-script')
