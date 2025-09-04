<?php

namespace App\Http\Controllers;

use App\Mail\VideoDownloadLink;
use App\Models\Contact;
use App\Models\Link;
use App\Models\VideoLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VideoLogController extends Controller
{
    // public static function sendDownloadLink(VideoLog $videoLog)
    // {
    //     // Generate a temporary encrypted link
    //     $token = Str::random(64);
    //     $expiresAt = now()->addDays(7); // Link expires in 7 days

    //     // Create the link record
    //     $link = Link::create([
    //         'video_id' => $videoLog->id,
    //         'link' => $token,
    //         'expired_at' => $expiresAt,
    //         'shortcode' => Str::random(8),
    //         'recipients' => 'contacts',
    //     ]);

    //     // Get all contacts
    //     $contacts = Contact::all();

    //     // Generate the download URL
    //     $downloadUrl = route('video.download', ['token' => $token]);

    //     // Send email to each contact
    //     foreach ($contacts as $contact) {
    //         Mail::to($contact->email)->send(new VideoDownloadLink(
    //             $videoLog,
    //             $downloadUrl,
    //             $expiresAt->format('F j, Y \a\t g:i A')
    //         ));
    //     }

    //     // Update the link record with email sent timestamp
    //     $link->update(['email_sent_at' => now()]);

    //     return response()->json([
    //         'message' => 'Download links sent successfully to ' . $contacts->count() . ' contacts',
    //         'expires_at' => $expiresAt->format('F j, Y \a\t g:i A')
    //     ]);
    // }

    public function download(Request $request, $token)
    {
        $link = Link::where('link', $token)
            ->where('expired_at', '>', now())
            ->first();

        if (! $link) {
            abort(404, 'Download link has expired or is invalid.');
        }

        $videoLog = VideoLog::find($link->video_id);
        if (! $videoLog || ! $videoLog->file) {
            abort(404, 'Video file not found.');
        }

        // Update download timestamp and IP address
        $link->update([
            'downloaded_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Return the file for download
        return response()->download(
            storage_path('app/public/'.$videoLog->file),
            $videoLog->file,
        );
    }
}
