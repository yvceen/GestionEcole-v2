<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', 'all'));
        $scope = trim((string) $request->get('scope', 'all'));

        $items = News::query()
            ->when($this->hasSchoolColumn(), fn ($query) => $query->where('school_id', $this->currentSchoolIdOrFail()))
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($scope) use ($q): void {
                    $scope->where('title', 'like', "%{$q}%")
                        ->orWhere('summary', 'like', "%{$q}%")
                        ->orWhere('body', 'like', "%{$q}%");
                });
            })
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($scope !== '' && $scope !== 'all', fn ($query) => $query->where('scope', $scope))
            ->with('classroom:id,name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $statsBase = News::query()
            ->when($this->hasSchoolColumn(), fn ($query) => $query->where('school_id', $this->currentSchoolIdOrFail()));

        $stats = [
            'total' => (clone $statsBase)->count(),
            'published' => (clone $statsBase)->where('status', 'published')->count(),
            'draft' => (clone $statsBase)->where('status', 'draft')->count(),
            'pinned' => Schema::hasColumn('news', 'is_pinned')
                ? (clone $statsBase)->where('is_pinned', true)->count()
                : 0,
        ];

        return view('admin.news.index', compact('items', 'q', 'status', 'scope', 'stats'));
    }

    public function create()
    {
        return view('admin.news.create', array_merge(
            $this->formData(),
            ['news' => new News()]
        ));
    }

    public function store(Request $request)
    {
        $schoolId = $this->hasSchoolColumn() ? $this->currentSchoolIdOrFail() : 0;
        $data = $this->validateNews($request, $schoolId);
        $scope = $data['scope'] ?? 'classroom';
        $this->validateClassroomScope($scope, $data['classroom_id'] ?? null, $schoolId);

        $payload = $this->buildPayload($request, $data, $schoolId);
        $news = News::create($payload);
        $this->notifyOnPublish($news, $scope, $schoolId);

        return redirect()->route('admin.news.index')->with('success', 'Actualite creee.');
    }

    public function edit(News $news)
    {
        $this->ensureTenantOwnership($news);

        return view('admin.news.edit', array_merge(
            $this->formData(),
            ['news' => $news->loadMissing('classroom:id,name')]
        ));
    }

    public function update(Request $request, News $news)
    {
        $this->ensureTenantOwnership($news);
        $schoolId = $this->hasSchoolColumn() ? $this->currentSchoolIdOrFail() : 0;
        $data = $this->validateNews($request, $schoolId, $news);
        $scope = $data['scope'] ?? 'classroom';
        $this->validateClassroomScope($scope, $data['classroom_id'] ?? null, $schoolId);

        $payload = $this->buildPayload($request, $data, $schoolId, $news);
        $news->update($payload);

        return redirect()->route('admin.news.index')->with('success', 'Actualite mise a jour.');
    }

    public function destroy(News $news)
    {
        $this->ensureTenantOwnership($news);

        $coverPath = $news->cover_path;
        $news->delete();

        if ($coverPath && Storage::disk('public')->exists($coverPath)) {
            Storage::disk('public')->delete($coverPath);
        }

        return redirect()->route('admin.news.index')->with('success', 'Actualite supprimee.');
    }

    private function formData(): array
    {
        $schoolId = $this->hasSchoolColumn() ? $this->currentSchoolIdOrFail() : 0;
        $classrooms = collect();

        if ($schoolId > 0 && Schema::hasTable('classrooms')) {
            $classrooms = DB::table('classrooms')
                ->when(Schema::hasColumn('classrooms', 'school_id'), fn ($query) => $query->where('school_id', $schoolId))
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return compact('classrooms');
    }

    private function validateNews(Request $request, int $schoolId, ?News $news = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
            'date' => ['required', 'date'],
            'scope' => ['nullable', 'in:classroom,school'],
            'classroom_id' => ['nullable', 'integer'],
            'is_pinned' => ['nullable', 'boolean'],
            'cover' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    private function validateClassroomScope(string $scope, mixed $classroomId, int $schoolId): void
    {
        if ($scope !== 'classroom') {
            return;
        }

        $resolvedClassroomId = (int) ($classroomId ?? 0);
        $classroomExists = DB::table('classrooms')
            ->where('id', $resolvedClassroomId)
            ->where('school_id', $schoolId)
            ->exists();

        if (!$classroomExists) {
            throw ValidationException::withMessages([
                'classroom_id' => 'La classe selectionnee est invalide pour cette ecole.',
            ]);
        }
    }

    private function buildPayload(Request $request, array $data, int $schoolId, ?News $news = null): array
    {
        $scope = $data['scope'] ?? 'classroom';
        $payload = [
            'title' => $data['title'],
            'summary' => $this->sanitizeText($data['summary'] ?? null),
            'body' => $this->sanitizeText($data['body'] ?? null),
            'status' => $data['status'],
            'date' => $data['date'],
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
        ];

        if (Schema::hasColumn('news', 'scope')) {
            $payload['scope'] = $scope;
        }
        if (Schema::hasColumn('news', 'school_id')) {
            $payload['school_id'] = $schoolId ?: null;
        }
        if (Schema::hasColumn('news', 'classroom_id')) {
            $payload['classroom_id'] = $scope === 'classroom'
                ? ((int) ($data['classroom_id'] ?? 0) ?: null)
                : null;
        }

        if ($request->hasFile('cover')) {
            $storedPath = $request->file('cover')->store('news-covers', 'public');
            $payload['cover_path'] = $storedPath;

            $oldCover = $news?->cover_path;
            if ($oldCover && Storage::disk('public')->exists($oldCover)) {
                Storage::disk('public')->delete($oldCover);
            }
        } elseif ($news) {
            $payload['cover_path'] = $news->cover_path;
        }

        return $payload;
    }

    private function sanitizeText(?string $value): ?string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : null;
    }

    private function notifyOnPublish(News $news, string $scope, int $schoolId): void
    {
        if ((string) $news->status !== 'published') {
            return;
        }

        try {
            $service = app(NotificationService::class);
            $recipientIds = [];

            if ($scope === 'school' && $schoolId > 0) {
                $recipientIds = array_merge(
                    $service->parentIdsBySchool($schoolId),
                    $service->studentUserIdsBySchool($schoolId)
                );
            }

            if ($scope === 'classroom') {
                $classroomId = (int) ($news->classroom_id ?? 0);
                if ($classroomId > 0) {
                    $recipientIds = array_merge(
                        $service->parentIdsByClassroom($classroomId, $schoolId ?: null),
                        $service->studentUserIdsByClassroom($classroomId, $schoolId ?: null)
                    );
                }
            }

            if ($recipientIds !== []) {
                $service->notifyUsers(
                    array_values(array_unique(array_map('intval', $recipientIds))),
                    'news',
                    'Nouvelle actualite',
                    (string) $news->title,
                    ['news_id' => (int) $news->id, 'scope' => $scope]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('News notifications failed', [
                'news_id' => $news->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function hasSchoolColumn(): bool
    {
        return Schema::hasTable('news') && Schema::hasColumn('news', 'school_id');
    }

    private function currentSchoolIdOrFail(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if ($schoolId <= 0) {
            abort(403, 'School context missing.');
        }

        return $schoolId;
    }

    private function ensureTenantOwnership(News $news): void
    {
        if (!$this->hasSchoolColumn()) {
            return;
        }

        abort_unless((int) ($news->school_id ?? 0) === $this->currentSchoolIdOrFail(), 404);
    }
}
