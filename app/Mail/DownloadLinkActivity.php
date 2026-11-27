<?php

namespace App\Mail;

use App\Filament\Resources\VideoLogResource;
use App\Models\DownloadLink;
use App\Models\VideoLog;
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
        $videoLog = $this->downloadLink->media?->model;
        $videoLog = $videoLog instanceof VideoLog ? $videoLog : null;

        return new Content(
            view: 'emails.download-link-activity',
            text: 'emails.text.download-link-activity',
            with: [
                'heading' => $this->activity === self::Opened ? 'Link opened' : 'File downloaded',
                'summary' => $this->summary(),
                'details' => array_filter([
                    'Recipient' => $this->downloadLink->email,
                    'Title' => $this->downloadLink->title,
                    'Broadcaster' => $videoLog?->broadcaster,
                    'Reference' => $videoLog?->log_id,
                    'When' => now()->format('F j, Y \a\t g:i A'),
                ]),
                'logUrl' => $videoLog ? VideoLogResource::getUrl('edit', ['record' => $videoLog]) : null,
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
