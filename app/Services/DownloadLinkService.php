<?php

namespace App\Services;

use App\Models\DownloadLink;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DownloadLinkService
{
    public const ValidForDays = 7;

    public function create(Media $media, string $email, string $title, ?int $contactId = null): DownloadLink
    {
        return DownloadLink::create([
            'contact_id' => $contactId,
            'email' => $email,
            'title' => $title,
            'media_id' => $media->id,
            'link' => Str::random(64),
            'status' => 'sent',
            'expires_at' => now()->addDays(self::ValidForDays),
        ]);
    }

    /**
     * Email the link to its recipient, marking it as errored if the mail cannot be sent.
     *
     * @throws \Throwable
     */
    public function send(DownloadLink $downloadLink, Mailable $mail): void
    {
        try {
            Mail::to($downloadLink->email)->send($mail);
        } catch (\Throwable $e) {
            $downloadLink->update(['status' => 'error']);

            throw $e;
        }
    }
}
