<?php

use App\Livewire\FileUploadPage;
use App\Mail\DownloadLinkActivity;
use App\Mail\FileDownloadLink;
use App\Models\Contact;
use App\Models\DownloadLink;
use App\Models\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::disk('local')->put('videos/sample.mp4', 'demo-video');
});

it('rejects an invalid recipient email before uploading', function (string $email) {
    Livewire::test(FileUploadPage::class)
        ->set('email', $email)
        ->call('validateRecipient')
        ->assertReturned(false)
        ->assertHasErrors('email');
})->with(['empty' => '', 'malformed' => 'not-an-email']);

it('creates a download link and emails it after upload', function () {
    Mail::fake();
    $contact = Contact::create(['email' => 'client@example.com', 'broadcast' => 'MTM']);

    Livewire::test(FileUploadPage::class)
        ->set('title', 'Daybreak')
        ->set('email', 'client@example.com')
        ->call('saveUpload', 'videos/sample.mp4', 'sample.mp4', 10, 'video/mp4')
        ->assertHasNoErrors()
        ->assertSet('isComplete', true);

    $link = DownloadLink::first();
    expect($link->email)->toBe('client@example.com')
        ->and($link->contact_id)->toBe($contact->id)
        ->and($link->status)->toBe('sent')
        ->and($link->media->model)->toBeInstanceOf(FileUpload::class)
        ->and($link->expires_at->isFuture())->toBeTrue();

    Mail::assertSent(FileDownloadLink::class, fn ($mail) => $mail->hasTo('client@example.com')
        && $mail->downloadLink->is($link));
});

it('does not save an upload without a valid email', function () {
    Mail::fake();

    Livewire::test(FileUploadPage::class)
        ->set('email', 'bad')
        ->call('saveUpload', 'videos/sample.mp4', 'sample.mp4', 10, 'video/mp4')
        ->assertHasErrors('email');

    expect(DownloadLink::count())->toBe(0);
    Mail::assertNothingSent();
});

it('marks the link as error when the email fails to send', function () {
    Mail::shouldReceive('to->send')->andThrow(new RuntimeException('smtp down'));

    Livewire::test(FileUploadPage::class)
        ->set('email', 'client@example.com')
        ->call('saveUpload', 'videos/sample.mp4', 'sample.mp4', 10, 'video/mp4')
        ->assertHasErrors('email');

    expect(DownloadLink::first()->status)->toBe('error');
});

function createDownloadLink(array $overrides = []): DownloadLink
{
    Mail::fake();
    Livewire::test(FileUploadPage::class)
        ->set('email', 'client@example.com')
        ->call('saveUpload', 'videos/sample.mp4', 'sample.mp4', 10, 'video/mp4');

    $link = DownloadLink::first();
    $link->update($overrides);

    return $link->fresh();
}

it('shows a landing page and records the first open only once', function () {
    $link = createDownloadLink();
    config(['mail.notify_address' => 'owner@example.com']);

    $this->get(route('download-link.show', $link->link))
        ->assertSuccessful()
        ->assertSee('sample.mp4')
        ->assertSee(route('download-link.file', $link->link));
    $openedAt = $link->refresh()->opened_at;

    $this->travel(5)->minutes();
    $this->get(route('download-link.show', $link->link))->assertSuccessful();

    expect($openedAt)->not->toBeNull()
        ->and($link->refresh()->opened_at->equalTo($openedAt))->toBeTrue()
        ->and($link->status)->toBe('sent')
        ->and($link->downloaded_at)->toBeNull();
    Mail::assertSent(DownloadLinkActivity::class, 1);
    Mail::assertSent(DownloadLinkActivity::class, fn ($mail) => $mail->hasTo('owner@example.com')
        && $mail->activity === DownloadLinkActivity::Opened);
});

it('does not count a HEAD request as an open', function () {
    $link = createDownloadLink();
    config(['mail.notify_address' => 'owner@example.com']);

    $this->head(route('download-link.show', $link->link))->assertSuccessful();

    expect($link->refresh()->opened_at)->toBeNull();
    Mail::assertNotSent(DownloadLinkActivity::class);
});

it('downloads the file, records the click and notifies once', function () {
    $link = createDownloadLink();
    config(['mail.notify_address' => 'owner@example.com']);

    $this->post(route('download-link.file', $link->link))
        ->assertSuccessful()
        ->assertDownload('sample.mp4');
    $this->post(route('download-link.file', $link->link))->assertSuccessful();

    $link->refresh();
    expect($link->status)->toBe('downloaded')
        ->and($link->number_clicks)->toBe(2)
        ->and($link->downloaded_at)->not->toBeNull();
    Mail::assertSent(DownloadLinkActivity::class, 1);
    Mail::assertSent(DownloadLinkActivity::class, fn ($mail) => $mail->activity === DownloadLinkActivity::Downloaded);
});

it('does not download the file on a plain visit to the file url', function () {
    $link = createDownloadLink();

    $this->get(route('download-link.file', $link->link))->assertMethodNotAllowed();
});

it('sends no notification when no address is configured', function () {
    $link = createDownloadLink();
    config(['mail.notify_address' => null]);

    $this->get(route('download-link.show', $link->link))->assertSuccessful();
    $this->post(route('download-link.file', $link->link))->assertSuccessful();

    Mail::assertNotSent(DownloadLinkActivity::class);
});

it('still serves the file when the notification cannot be sent', function () {
    $link = createDownloadLink();
    config(['mail.notify_address' => 'owner@example.com']);
    Mail::shouldReceive('to->send')->andThrow(new RuntimeException('smtp down'));

    $this->get(route('download-link.show', $link->link))->assertSuccessful();
    $this->post(route('download-link.file', $link->link))->assertDownload('sample.mp4');
});

it('returns not found for expired or unknown tokens', function () {
    $link = createDownloadLink(['expires_at' => now()->subMinute()]);

    foreach ([$link->link, 'unknown'] as $token) {
        $this->get(route('download-link.show', $token))->assertNotFound();
        $this->post(route('download-link.file', $token))->assertNotFound();
    }
    expect($link->refresh()->opened_at)->toBeNull();
});
