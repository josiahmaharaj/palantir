<?php

namespace App\Mail;

use App\Models\DownloadLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FileDownloadLink extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DownloadLink $downloadLink,
        public string $fileSize = 'Unknown size',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Download link - '.$this->downloadLink->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.file-download-link',
            with: [
                'brandName' => config('app.name'),
                'title' => $this->downloadLink->title,
                'downloadUrl' => $this->downloadLink->getDownloadUrl(),
                'expiresAt' => $this->downloadLink->expires_at->format('F j, Y \a\t g:i A'),
                'fileSize' => $this->fileSize,
            ],
        );
    }
}
