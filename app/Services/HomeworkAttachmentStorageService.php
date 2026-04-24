<?php

namespace App\Services;

use App\Models\HomeworkAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class HomeworkAttachmentStorageService
{
    public const PRIVATE_DISK = 'local';
    public const LEGACY_PUBLIC_DISK = 'public';

    public function storeUploadedFile(UploadedFile $file, int $schoolId, int $homeworkId): string
    {
        return $file->store($this->directory($schoolId, $homeworkId), self::PRIVATE_DISK);
    }

    public function downloadResponse(HomeworkAttachment $attachment)
    {
        [$diskName, $disk] = $this->resolveDisk($attachment);
        abort_unless($disk !== null, 404, 'File not found.');

        if ($diskName === self::LEGACY_PUBLIC_DISK) {
            $this->migrateLegacyAttachment($attachment);
            [, $disk] = $this->resolveDisk($attachment);
            abort_unless($disk !== null, 404, 'File not found.');
        }

        return $disk->download((string) $attachment->path, (string) $attachment->original_name);
    }

    private function migrateLegacyAttachment(HomeworkAttachment $attachment): void
    {
        $path = (string) $attachment->path;
        if ($path === '') {
            return;
        }

        $legacyDisk = Storage::disk(self::LEGACY_PUBLIC_DISK);
        if (!$legacyDisk->exists($path)) {
            return;
        }

        $privateDisk = Storage::disk(self::PRIVATE_DISK);
        if ($privateDisk->exists($path)) {
            $legacyDisk->delete($path);
            return;
        }

        try {
            $stream = $legacyDisk->readStream($path);
            if ($stream === false) {
                return;
            }

            $privateDisk->writeStream($path, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            $legacyDisk->delete($path);
        } catch (\Throwable $error) {
            Log::warning('Homework attachment legacy migration failed', [
                'attachment_id' => (int) $attachment->id,
                'path' => $path,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function resolveDisk(HomeworkAttachment $attachment): array
    {
        $path = (string) $attachment->path;
        if ($path === '') {
            return [null, null];
        }

        $privateDisk = Storage::disk(self::PRIVATE_DISK);
        if ($privateDisk->exists($path)) {
            return [self::PRIVATE_DISK, $privateDisk];
        }

        $legacyDisk = Storage::disk(self::LEGACY_PUBLIC_DISK);
        if ($legacyDisk->exists($path)) {
            return [self::LEGACY_PUBLIC_DISK, $legacyDisk];
        }

        return [null, null];
    }

    private function directory(int $schoolId, int $homeworkId): string
    {
        return "schools/{$schoolId}/homeworks/{$homeworkId}";
    }
}
