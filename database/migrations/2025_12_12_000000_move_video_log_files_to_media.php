<?php

use App\Models\VideoLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('media_id')->nullable()->after('broadcaster');
            $table->foreign('media_id')
                ->references('id')
                ->on('media')
                ->nullOnDelete();
        });

        if (Schema::hasColumn('video_logs', 'file')) {
            VideoLog::query()
                ->whereNotNull('file')
                ->where('file', '!=', '')
                ->chunkById(50, function ($logs) {
                    foreach ($logs as $log) {
                        $path = storage_path('app/'.$log->file);

                        if (! file_exists($path)) {
                            continue;
                        }

                        $log->clearMediaCollection('videos');

                        $media = $log->addMedia($path)
                            ->usingFileName(basename($log->file))
                            ->toMediaCollection('videos');

                        $log->forceFill(['media_id' => $media->id])->save();
                    }
                });

            Schema::table('video_logs', function (Blueprint $table) {
                $table->dropColumn('file');
            });
        }
    }

    public function down(): void
    {
        Schema::table('video_logs', function (Blueprint $table) {
            $table->string('file')->nullable()->after('broadcaster');
            $table->dropForeign(['media_id']);
            $table->dropColumn('media_id');
        });
    }
};
