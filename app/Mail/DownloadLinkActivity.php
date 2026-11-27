<?php

namespace App\Mail;

use App\Models\DownloadLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DownloadLinkActivity extends Mailable
{
    use Queueable, SerializesModels;

    public const Opened = 'opened';

    public const Downloaded = 'downloaded';

    public function __construct(
        public DownloadLink $downloadLink,
        public string $activity,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->summary(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.download-link-activity',
            with: [
                'summary' => $this->summary(),
                'happenedAt' => now()->format('F j, Y \a\t g:i A'),
            ],
        );
    }

    private function summary(): string
    {
        $recipient = $this->downloadLink->email ?? 'Someone';
        $action = $this->activity === self::Opened ? 'opened the link for' : 'downloaded';

        return "{$recipient} {$action} {$this->downloadLink->title}";
    }
}
