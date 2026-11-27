<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MakeMediaPrivate extends Command
{
    protected $signature = 'media:make-private';

    protected $description = 'Move media files from the public disk to private storage';

    public function handle(): int
    {
        $moved = 0;
        $missing = 0;
        $failed = 0;

        foreach (Media::query()->where('disk', 'public')->cursor() as $media) {
            $directory = dirname($media->getPathRelativeToRoot());
            $source = Storage::disk('public')->path($directory);
            $target = Storage::disk('media')->path($directory);

            if (! File::isDirectory($source)) {
                $missing++;
            } else {
                File::ensureDirectoryExists(dirname($target));

                if (File::exists($target) || ! File::moveDirectory($source, $target)) {
                    $this->error("Media {$media->id} was not moved: check {$target}");
                    $failed++;

                    continue;
                }

                $moved++;
            }

            $media->forceFill(['disk' => 'media', 'conversions_disk' => 'media'])->save();
        }

        $this->info("Moved {$moved}, already missing {$missing}, failed {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
