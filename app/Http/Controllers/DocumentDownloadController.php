<?php

namespace App\Http\Controllers;

use App\Models\SchoolDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentDownloadController extends Controller
{
    public function __invoke(Request $request, SchoolDocument $document): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($this->canDownload($document, $user), 403);

        $path = trim((string) $document->file_path);
        abort_if($path === '', 404, 'File not found.');

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404, 'File not found.');

        return response()->download(
            $disk->path($path),
            $this->downloadName($document)
        );
    }

    private function canDownload(SchoolDocument $document, User $user): bool
    {
        if ((int) $document->school_id <= 0) {
            return false;
        }

        if ((string) $user->role === User::ROLE_SUPER_ADMIN) {
            return true;
        }

        if ((int) ($user->school_id ?? 0) !== (int) $document->school_id) {
            return false;
        }

        if (!(bool) ($document->is_active ?? false)) {
            return false;
        }

        if (in_array((string) $user->role, [
            User::ROLE_ADMIN,
            User::ROLE_DIRECTOR,
            User::ROLE_TEACHER,
            User::ROLE_SCHOOL_LIFE,
        ], true)) {
            return true;
        }

        $classroomIds = $this->classroomIdsForUser($user, (int) $document->school_id);

        return SchoolDocument::query()
            ->whereKey($document->id)
            ->visibleToAudience((int) $document->school_id, (string) $user->role, $classroomIds)
            ->exists();
    }

    private function classroomIdsForUser(User $user, int $schoolId): array
    {
        if ((string) $user->role === User::ROLE_PARENT) {
            return Student::query()
                ->active()
                ->where('school_id', $schoolId)
                ->where('parent_user_id', (int) $user->id)
                ->whereNotNull('classroom_id')
                ->pluck('classroom_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        if ((string) $user->role === User::ROLE_STUDENT) {
            $classroomId = Student::query()
                ->active()
                ->where('school_id', $schoolId)
                ->where('user_id', (int) $user->id)
                ->value('classroom_id');

            return $classroomId ? [(int) $classroomId] : [];
        }

        return [];
    }

    private function downloadName(SchoolDocument $document): string
    {
        $title = trim((string) $document->title);
        $extension = pathinfo((string) $document->file_path, PATHINFO_EXTENSION);
        $safeTitle = preg_replace('/[^A-Za-z0-9._-]+/', '_', $title !== '' ? $title : 'document');
        $safeTitle = trim((string) $safeTitle, '._');
        $safeTitle = $safeTitle !== '' ? $safeTitle : 'document';

        if ($extension !== '' && !str_ends_with(strtolower($safeTitle), '.' . strtolower($extension))) {
            return $safeTitle . '.' . $extension;
        }

        return $safeTitle;
    }
}
