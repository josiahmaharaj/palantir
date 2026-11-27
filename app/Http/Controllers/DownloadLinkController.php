<?php

namespace App\Http\Controllers;

use App\Contracts\Deliverable;
use App\Events\DownloadLinkOpened;
use App\Events\FileDownloaded;
use App\Models\DownloadLink;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadLinkController extends Controller
{
    public function show(Request $request, string $token): View
    {
        [$downloadLink, $media] = $this->resolve($token);

        if ($request->isMethod('GET') && $this->recordFirst($downloadLink, 'opened_at')) {
            DownloadLinkOpened::dispatch($downloadLink);
        }

        return view('download-links.show', [
            'downloadLink' => $downloadLink,
            'fileName' => $media->file_name,
            'format' => Str::upper(pathinfo($media->file_name, PATHINFO_EXTENSION) ?: 'File'),
            'details' => $this->details($media),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function details(Media $media): array
    {
        $owner = $media->model;

        return [
            ...($owner instanceof Deliverable ? $owner->deliveryDetails() : []),
            'File size' => Number::fileSize($media->size, precision: 1),
        ];
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
            FileDownloaded::dispatch($downloadLink);
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
            throw new HttpResponseException(response()->view('download-links.unavailable', status: 404));
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
}
