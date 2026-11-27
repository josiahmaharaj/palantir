<?php

use App\Filament\Resources\VideoLogResource\Pages\CreateVideoLog;
use App\Filament\Resources\VideoLogResource\Pages\EditVideoLog;
use App\Mail\VideoDownloadLink;
use App\Models\Contact;
use App\Models\DownloadLink;
use App\Models\User;
use App\Models\VideoLog;
use App\Models\VideoLogAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeVideoLog(): VideoLog
{
    return VideoLog::create([
        'log_id' => 123,
        'title' => 'Test video',
        'broadcaster' => 'MTM',
        'due_date' => now()->toDateString(),
        'status' => 'Todo',
    ]);
}

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::fake('media');
    Mail::fake();
});

it('only shows the video uploader on the edit page', function () {
    $user = User::factory()->create();
    $videoLog = makeVideoLog();

    Livewire::actingAs($user)->test(CreateVideoLog::class)->assertFormFieldIsHidden('upload_path');
    Livewire::actingAs($user)->test(EditVideoLog::class, ['record' => $videoLog->getKey()])
        ->assertFormFieldIsVisible('upload_path');
});

it('attaches an uploaded video to the log and emails a link to each broadcaster contact', function () {
    Storage::disk('local')->put('videos/sample.mp4', 'demo');
    $mtm = Contact::create(['email' => 'mtm@example.com', 'broadcast' => 'MTM']);
    Contact::create(['email' => 'tv6@example.com', 'broadcast' => 'TV6']);
    $videoLog = makeVideoLog();

    Livewire::actingAs(User::factory()->create())
        ->test(EditVideoLog::class, ['record' => $videoLog->getKey()])
        ->call('handleStreamingUploadCompleted', 'videos/sample.mp4');

    $videoLog->refresh();
    $media = $videoLog->videoMedia();
    expect($media)->not->toBeNull()
        ->and($media->collection_name)->toBe('video-log-videos')
        ->and($videoLog->media_id)->toBe($media->id);

    $link = $videoLog->downloadLinks()->sole();
    expect($link->contact_id)->toBe($mtm->id)
        ->and($link->email)->toBe('mtm@example.com')
        ->and($link->expires_at->isFuture())->toBeTrue();

    Mail::assertSent(VideoDownloadLink::class, 1);
    Mail::assertSent(VideoDownloadLink::class, fn ($mail) => $mail->hasTo('mtm@example.com'));

    expect($videoLog->auditLogs()->pluck('event')->all())
        ->toBe([VideoLogAuditLog::VideoUploaded, VideoLogAuditLog::EmailSent]);
    expect($videoLog->auditLogs()->where('event', VideoLogAuditLog::EmailSent)->value('email'))->toBe('mtm@example.com');
});

it('loads the download links relation manager without errors', function () {
    Storage::disk('local')->put('videos/sample.mp4', 'demo');
    $videoLog = makeVideoLog();
    $videoLog->replaceVideoFromPath('videos/sample.mp4', 'sample.mp4');
    DownloadLink::create([
        'email' => 'a@example.com', 'title' => 'x', 'media_id' => $videoLog->media_id,
        'link' => 'tok', 'status' => 'sent', 'expires_at' => now()->addDay(),
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(\App\Filament\Resources\VideoLogResource\RelationManagers\DownloadLinkRelationManager::class, [
            'ownerRecord' => $videoLog,
            'pageClass' => EditVideoLog::class,
        ])
        ->assertSuccessful();
});

it('records when no contacts exist for the broadcaster', function () {
    Storage::disk('local')->put('videos/sample.mp4', 'demo');
    $videoLog = makeVideoLog();

    Livewire::actingAs(User::factory()->create())
        ->test(EditVideoLog::class, ['record' => $videoLog->getKey()])
        ->call('handleStreamingUploadCompleted', 'videos/sample.mp4');

    Mail::assertNothingSent();
    expect($videoLog->auditLogs()->pluck('event')->all())
        ->toBe([VideoLogAuditLog::VideoUploaded, VideoLogAuditLog::NoContacts]);
});

it('records a failed email in the audit log', function () {
    Storage::disk('local')->put('videos/sample.mp4', 'demo');
    Contact::create(['email' => 'mtm@example.com', 'broadcast' => 'MTM']);
    $videoLog = makeVideoLog();
    Mail::shouldReceive('to->send')->andThrow(new RuntimeException('smtp down'));

    Livewire::actingAs(User::factory()->create())
        ->test(EditVideoLog::class, ['record' => $videoLog->getKey()])
        ->call('handleStreamingUploadCompleted', 'videos/sample.mp4');

    $failed = $videoLog->auditLogs()->where('event', VideoLogAuditLog::EmailFailed)->sole();
    expect($failed->email)->toBe('mtm@example.com')->and($failed->message)->toBe('smtp down');
    expect($videoLog->refresh()->downloadLinks()->sole()->status)->toBe('error');
});

it('shows audit log entries in the relation manager', function () {
    $videoLog = makeVideoLog();
    $entry = $videoLog->auditLogs()->create(['event' => VideoLogAuditLog::EmailSent, 'email' => 'a@example.com']);

    Livewire::actingAs(User::factory()->create())
        ->test(\App\Filament\Resources\VideoLogResource\RelationManagers\AuditLogsRelationManager::class, [
            'ownerRecord' => $videoLog,
            'pageClass' => EditVideoLog::class,
        ])
        ->assertCanSeeTableRecords([$entry]);
});

it('records opens and downloads of a video link in the audit log', function () {
    Storage::disk('local')->put('videos/sample.mp4', 'demo');
    Contact::create(['email' => 'mtm@example.com', 'broadcast' => 'MTM']);
    $videoLog = makeVideoLog();
    $videoLog->replaceVideoFromPath('videos/sample.mp4', 'sample.mp4');
    \App\Services\VideoLogService::sendDownloadLink($videoLog);
    $token = $videoLog->downloadLinks()->sole()->link;

    $this->get(route('download-link.show', $token))->assertSuccessful();
    $this->get(route('download-link.show', $token))->assertSuccessful();
    $this->post(route('download-link.file', $token))->assertSuccessful();
    $this->post(route('download-link.file', $token))->assertSuccessful();

    $entries = $videoLog->auditLogs()
        ->whereIn('event', [VideoLogAuditLog::LinkOpened, VideoLogAuditLog::FileDownloaded])
        ->orderBy('id')
        ->get();

    expect($entries->pluck('event')->all())
        ->toBe([VideoLogAuditLog::LinkOpened, VideoLogAuditLog::FileDownloaded])
        ->and($entries->pluck('email')->unique()->all())->toBe(['mtm@example.com']);
});

it('shows the video details on the download page and in the email', function () {
    Storage::disk('local')->put('videos/sample.mp4', 'demo');
    Contact::create(['email' => 'mtm@example.com', 'broadcast' => 'MTM']);
    $videoLog = makeVideoLog();
    $videoLog->replaceVideoFromPath('videos/sample.mp4', 'sample.mp4');
    \App\Services\VideoLogService::sendDownloadLink($videoLog);
    $url = $videoLog->downloadLinks()->sole()->getDownloadUrl();

    $this->get($url)->assertSuccessful()->assertSeeInOrder(['Test video', 'Broadcaster', 'MTM', 'Reference', '123']);

    Mail::assertSent(VideoDownloadLink::class, function ($mail) use ($url) {
        $mail->assertSeeInHtml('Test video')
            ->assertSeeInHtml($url)
            ->assertSeeInText($url)
            ->assertDontSeeInHtml('Hi User');

        return true;
    });
});

it('tells the uploader how many contacts were emailed', function (array $emails, string $title) {
    Storage::disk('local')->put('videos/sample.mp4', 'demo');
    foreach ($emails as $email) {
        Contact::create(['email' => $email, 'broadcast' => 'MTM']);
    }
    $videoLog = makeVideoLog();

    Livewire::actingAs(User::factory()->create())
        ->test(EditVideoLog::class, ['record' => $videoLog->getKey()])
        ->call('handleStreamingUploadCompleted', 'videos/sample.mp4')
        ->assertNotified($title);
})->with([
    'no contacts' => [[], 'Video saved, nothing sent'],
    'one contact' => [['a@example.com'], 'Video sent to 1 contact'],
    'two contacts' => [['a@example.com', 'b@example.com'], 'Video sent to 2 contacts'],
]);

it('warns the uploader when an email fails', function () {
    Storage::disk('local')->put('videos/sample.mp4', 'demo');
    Contact::create(['email' => 'mtm@example.com', 'broadcast' => 'MTM']);
    $videoLog = makeVideoLog();
    Mail::shouldReceive('to->send')->andThrow(new RuntimeException('smtp down'));

    Livewire::actingAs(User::factory()->create())
        ->test(EditVideoLog::class, ['record' => $videoLog->getKey()])
        ->call('handleStreamingUploadCompleted', 'videos/sample.mp4')
        ->assertNotified('Video saved, 1 email failed');
});

it('shows the new file and no failure message after a successful upload', function () {
    Storage::disk('local')->put('videos/sample.mp4', 'demo');
    Contact::create(['email' => 'mtm@example.com', 'broadcast' => 'MTM']);
    $videoLog = makeVideoLog();

    Livewire::actingAs(User::factory()->create())
        ->test(EditVideoLog::class, ['record' => $videoLog->getKey()])
        ->call('handleStreamingUploadCompleted', 'videos/sample.mp4')
        ->assertNotified('Video sent to 1 contact')
        ->assertNotNotified('Upload failed')
        ->assertSet('data.upload_path', 'sample.mp4');
});
