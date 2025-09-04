<?php

namespace App\Mail;

use App\Models\VideoLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VideoDownloadLink extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $brandName,
        public VideoLog $videoLog,
        public string $downloadLink,
        public string $expiresAt,
        public string $fileSize = 'Unknown size',
        public string $recipientName = 'User'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Video Download Link - ' . $this->videoLog->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.video-download-link',
            with: [
                'brandName' => $this->brandName,
                'videoLog' => $this->videoLog,
                'downloadLink' => $this->downloadLink,
                'expiresAt' => $this->expiresAt,
                'fileSize' => $this->fileSize,
                'recipientName' => $this->recipientName,
            ],
        );
    }
}
