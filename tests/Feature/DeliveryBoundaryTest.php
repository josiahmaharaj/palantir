<?php

use App\Events\DownloadLinkOpened;
use App\Events\FileDownloaded;
use App\Models\DownloadLink;
use App\Models\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

foreach ([
    'App\Http\Controllers\DownloadLinkController',
    'App\Services\DownloadLinkService',
    'App\Mail\FileDownloadLink',
    'App\Mail\DownloadLinkActivity',
    'App\Models\DownloadLink',
    'App\Events\DownloadLinkOpened',
    'App\Events\FileDownloaded',
    'App\Listeners\SendDownloadActivityAlert',
    'App\Livewire\FileUploadPage',
] as $deliveryClass) {
    arch($deliveryClass.' does not depend on the video log')
        ->expect($deliveryClass)
        ->not->toUse([
            'App\Models\VideoLog',
            'App\Models\VideoLogAuditLog',
            'App\Filament\Resources\VideoLogResource',
            'App\Services\VideoLogService',
        ]);
}

it('announces the first open and first download of a link once each', function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::fake('media');
    Storage::disk('local')->put('videos/sample.mp4', 'demo');
    $media = FileUpload::create(['title' => 'x', 'original_filename' => 'sample.mp4', 'file_size' => 4, 'mime_type' => 'video/mp4', 'status' => 'completed'])
        ->addMedia(Storage::disk('local')->path('videos/sample.mp4'))
        ->toMediaCollection('files');
    $link = DownloadLink::create([
        'email' => 'a@example.com', 'title' => 'x', 'media_id' => $media->id,
        'link' => 'tok', 'status' => 'sent', 'expires_at' => now()->addDay(),
    ]);
    Event::fake([DownloadLinkOpened::class, FileDownloaded::class]);

    $this->get($link->getDownloadUrl())->assertSuccessful();
    $this->get($link->getDownloadUrl())->assertSuccessful();
    $this->post(route('download-link.file', 'tok'))->assertSuccessful();
    $this->post(route('download-link.file', 'tok'))->assertSuccessful();

    Event::assertDispatchedTimes(DownloadLinkOpened::class, 1);
    Event::assertDispatchedTimes(FileDownloaded::class, 1);
    Event::assertDispatched(FileDownloaded::class, fn ($event) => $event->downloadLink->is($link));
});
