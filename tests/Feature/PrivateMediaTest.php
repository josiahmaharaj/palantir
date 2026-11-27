<?php

use App\Models\User;
use App\Models\VideoLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::fake('media');
    Storage::disk('local')->put('videos/sample.mp4', 'demo');
});

function videoLogWithVideo(): VideoLog
{
    $videoLog = VideoLog::create([
        'log_id' => 7, 'title' => 'Private video', 'broadcaster' => 'MTM',
        'due_date' => now()->toDateString(), 'status' => 'Todo',
    ]);
    $videoLog->replaceVideoFromPath('videos/sample.mp4', 'sample.mp4');

    return $videoLog->refresh();
}

it('stores videos outside the public disk', function () {
    $media = videoLogWithVideo()->videoMedia();

    expect($media->disk)->toBe('media')
        ->and(config('filesystems.disks.media.root'))->toBe(storage_path('app/private/media'))
        ->and(config('filesystems.disks.media'))->not->toHaveKey('url');
    Storage::disk('media')->assertExists($media->getPathRelativeToRoot());
    Storage::disk('public')->assertMissing($media->getPathRelativeToRoot());
});

it('lets only a signed in user download a video from the log', function () {
    $videoLog = videoLogWithVideo();
    $url = route('video-logs.video', $videoLog);

    $this->get($url)->assertRedirect(route('filament.admin.auth.login'));
    $this->actingAs(User::factory()->create())->get($url)->assertSuccessful()->assertDownload('sample.mp4');
});

it('returns not found when a log has no video', function () {
    $videoLog = VideoLog::create([
        'log_id' => 8, 'title' => 'Empty', 'broadcaster' => 'MTM',
        'due_date' => now()->toDateString(), 'status' => 'Todo',
    ]);

    $this->actingAs(User::factory()->create())->get(route('video-logs.video', $videoLog))->assertNotFound();
});

it('moves existing public files to private storage and can be run again', function () {
    config(['media-library.disk_name' => 'public']);
    $media = videoLogWithVideo()->videoMedia();
    $path = $media->getPathRelativeToRoot();
    Storage::disk('public')->assertExists($path);

    $this->artisan('media:make-private')->assertSuccessful();
    $this->artisan('media:make-private')->assertSuccessful();

    expect($media->refresh()->disk)->toBe('media')->and($media->conversions_disk)->toBe('media');
    Storage::disk('media')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
});

it('does not overwrite a file already in private storage', function () {
    config(['media-library.disk_name' => 'public']);
    $media = videoLogWithVideo()->videoMedia();
    Storage::disk('media')->put($media->getPathRelativeToRoot(), 'something else');

    $this->artisan('media:make-private')->assertFailed();

    expect($media->refresh()->disk)->toBe('public');
    Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
    expect(Storage::disk('media')->get($media->getPathRelativeToRoot()))->toBe('something else');
});
