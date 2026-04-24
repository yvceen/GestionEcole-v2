<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless(in_array((string) $user->role, [User::ROLE_PARENT, User::ROLE_STUDENT], true), 403);

        $schoolId = $this->schoolId($user);
        $classroomIds = $this->classroomIdsForUser($user, $schoolId);

        $items = SchoolDocument::query()
            ->visibleToAudience($schoolId, (string) $user->role, $classroomIds)
            ->with('classroom:id,name')
            ->latest('published_at')
            ->latest('id')
            ->limit(40)
            ->get()
            ->map(fn (SchoolDocument $document) => [
                'id' => (int) $document->id,
                'title' => (string) $document->title,
                'summary' => (string) ($document->summary ?? ''),
                'category' => (string) $document->category,
                'audience_scope' => (string) $document->audience_scope,
                'classroom_name' => (string) ($document->classroom?->name ?? ''),
                'published_at' => optional($document->published_at)->toIso8601String(),
                'published_label' => optional($document->published_at)->format('d/m/Y') ?? '',
                'file_url' => route('api.documents.download', ['document' => $document->id], false),
                'download_path' => route('api.documents.download', ['document' => $document->id], false),
                'mime_type' => (string) ($document->mime_type ?? ''),
                'size_bytes' => (int) ($document->size_bytes ?? 0),
            ])
            ->values()
            ->all();

        return response()->json(['items' => $items]);
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

    private function schoolId(User $user): int
    {
        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
