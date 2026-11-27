<?php

namespace App\Http\Controllers;

use App\Mail\DownloadLinkActivity;
use App\Models\DownloadLink;
use App\Models\VideoLog;
use App\Models\VideoLogAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadLinkController extends Controller
{
    public function show(Request $request, string $token): View
    {
        [$downloadLink, $media] = $this->resolve($token);

        if ($request->isMethod('GET') && $this->recordFirst($downloadLink, 'opened_at')) {
            $this->announce($downloadLink, $media, DownloadLinkActivity::Opened);
        }

        return view('download-links.show', [
            'downloadLink' => $downloadLink,
            'fileName' => $media->file_name,
            'fileSize' => Number::fileSize($media->size),
        ]);
    }

    public function download(Request $request, string $token): BinaryFileResponse
    {
        [$downloadLink, $media] = $this->resolve($token);

        $isFirstDownload = $this->recordFirst($downloadLink, 'downloaded_at');

        $downloadLink->forceFill([
            'status' => 'downloaded',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ])->save();
        $downloadLink->increment('number_clicks');

        if ($isFirstDownload) {
            $this->announce($downloadLink, $media, DownloadLinkActivity::Downloaded);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * @return array{0: DownloadLink, 1: Media}
     */
    private function resolve(string $token): array
    {
        $downloadLink = DownloadLink::query()
            ->where('link', $token)
            ->where('expires_at', '>', now())
            ->first();

        $media = $downloadLink?->media;

        if (! $media || ! file_exists($media->getPath())) {
            abort(404, 'Download link has expired or is invalid.');
        }

        return [$downloadLink, $media];
    }

    /**
     * Stamp the column only if it is still empty, so simultaneous requests announce once.
     */
    private function recordFirst(DownloadLink $downloadLink, string $column): bool
    {
        return DownloadLink::query()
            ->whereKey($downloadLink->getKey())
            ->whereNull($column)
            ->update([$column => now()]) === 1;
    }

    private function announce(DownloadLink $downloadLink, Media $media, string $activity): void
    {
        $isOpen = $activity === DownloadLinkActivity::Opened;

        if ($media->model instanceof VideoLog) {
            $media->model->auditLogs()->create([
                'event' => $isOpen ? VideoLogAuditLog::LinkOpened : VideoLogAuditLog::FileDownloaded,
                'email' => $downloadLink->email,
                'message' => $isOpen ? 'Download link opened' : 'File downloaded',
            ]);
        }

        $notifyAddress = config('mail.notify_address');

        if (! $notifyAddress) {
            return;
        }

        try {
            Mail::to($notifyAddress)->send(new DownloadLinkActivity($downloadLink, $activity));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
