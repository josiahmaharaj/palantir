<?php

use App\Filament\Resources\VideoLogResource\Pages\CreateVideoLog;
use App\Models\User;
use App\Models\VideoLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('stores chunked upload into media and links video log', function () {
    Storage::fake('local');
    Storage::fake('public');

    $filePath = 'videos/sample.mp4';
    Storage::disk('local')->put($filePath, 'demo');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateVideoLog::class)
        ->fillForm([
            'log_id' => 123,
            'title' => 'Test video',
            'broadcaster' => 'MTM',
            'due_date' => now()->toDateString(),
            'status' => 'Todo',
            'related_log_id' => null,
            'upload_path' => $filePath,
        ])
        ->call('handleStreamingUploadCompleted', [
            'file_path' => $filePath,
            'original_name' => 'sample.mp4',
            'mime_type' => 'video/mp4',
            'size' => 4,
        ]);

    $videoLog = VideoLog::first();
    expect($videoLog)->not->toBeNull();
    expect($videoLog->media_id)->not->toBeNull();

    $media = $videoLog->videoMedia();
    expect($media)->not->toBeNull();
    expect($media->collection_name)->toBe('videos');

    Storage::disk($media->disk)->assertExists($media->getPathRelativeToRoot());
});
