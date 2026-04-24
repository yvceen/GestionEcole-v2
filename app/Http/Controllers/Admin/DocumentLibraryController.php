<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\SchoolDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentLibraryController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $q = trim((string) $request->get('q', ''));
        $category = trim((string) $request->get('category', 'all'));
        $audience = trim((string) $request->get('audience', 'all'));

        $documents = SchoolDocument::query()
            ->where('school_id', $schoolId)
            ->with(['classroom:id,name', 'author:id,name'])
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($scope) use ($q): void {
                    $scope->where('title', 'like', "%{$q}%")
                        ->orWhere('summary', 'like', "%{$q}%");
                });
            })
            ->when($category !== '' && $category !== 'all', fn ($query) => $query->where('category', $category))
            ->when($audience !== '' && $audience !== 'all', fn ($query) => $query->where('audience_scope', $audience))
            ->latest('published_at')
            ->latest('id')
            ->paginate(16)
            ->withQueryString();

        return view('admin.documents.library.index', [
            'documents' => $documents,
            'q' => $q,
            'category' => $category,
            'audience' => $audience,
            'categories' => SchoolDocument::categories(),
            'audiences' => SchoolDocument::audienceScopes(),
            'roles' => $this->roles(),
            'classrooms' => Classroom::query()->where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = $this->schoolId();
        $data = $this->validatedData($request, $schoolId);

        $storedPath = $request->file('document')->store('school-documents', 'public');

        SchoolDocument::create([
            'school_id' => $schoolId,
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'category' => $data['category'],
            'audience_scope' => $data['audience_scope'],
            'role' => $data['audience_scope'] === SchoolDocument::AUDIENCE_ROLE ? ($data['role'] ?? null) : null,
            'classroom_id' => $data['audience_scope'] === SchoolDocument::AUDIENCE_CLASSROOM ? (int) ($data['classroom_id'] ?? 0) ?: null : null,
            'file_path' => $storedPath,
            'mime_type' => $request->file('document')?->getMimeType(),
            'size_bytes' => (int) ($request->file('document')?->getSize() ?? 0),
            'published_at' => now(),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_by_user_id' => (int) $request->user()->id,
        ]);

        return redirect()->route('admin.documents.library.index')->with('success', 'Document ajoute.');
    }

    public function update(Request $request, SchoolDocument $document)
    {
        $document = $this->resolveDocument($document);
        $schoolId = $this->schoolId();
        $data = $this->validatedData($request, $schoolId, false);

        $payload = [
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'category' => $data['category'],
            'audience_scope' => $data['audience_scope'],
            'role' => $data['audience_scope'] === SchoolDocument::AUDIENCE_ROLE ? ($data['role'] ?? null) : null,
            'classroom_id' => $data['audience_scope'] === SchoolDocument::AUDIENCE_CLASSROOM ? (int) ($data['classroom_id'] ?? 0) ?: null : null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];

        if ($request->hasFile('document')) {
            $storedPath = $request->file('document')->store('school-documents', 'public');
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $payload['file_path'] = $storedPath;
            $payload['mime_type'] = $request->file('document')?->getMimeType();
            $payload['size_bytes'] = (int) ($request->file('document')?->getSize() ?? 0);
        }

        $document->update($payload);

        return redirect()->route('admin.documents.library.index')->with('success', 'Document mis a jour.');
    }

    public function destroy(SchoolDocument $document)
    {
        $document = $this->resolveDocument($document);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('admin.documents.library.index')->with('success', 'Document supprime.');
    }

    private function validatedData(Request $request, int $schoolId, bool $fileRequired = true): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', 'in:' . implode(',', SchoolDocument::categories())],
            'audience_scope' => ['required', 'in:' . implode(',', SchoolDocument::audienceScopes())],
            'role' => ['nullable', 'in:' . implode(',', $this->roles())],
            'classroom_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'document' => [$fileRequired ? 'required' : 'nullable', 'file', 'max:10240'],
        ];

        $data = $request->validate($rules);

        if (($data['audience_scope'] ?? '') === SchoolDocument::AUDIENCE_CLASSROOM) {
            $classroomId = (int) ($data['classroom_id'] ?? 0);
            $exists = Classroom::query()->where('school_id', $schoolId)->whereKey($classroomId)->exists();
            if (!$exists) {
                throw ValidationException::withMessages([
                    'classroom_id' => 'La classe selectionnee est invalide pour cette ecole.',
                ]);
            }
        }

        if (($data['audience_scope'] ?? '') === SchoolDocument::AUDIENCE_ROLE && empty($data['role'])) {
            throw ValidationException::withMessages([
                'role' => 'Choisissez un role cible pour cette diffusion.',
            ]);
        }

        return $data;
    }

    private function resolveDocument(SchoolDocument $document): SchoolDocument
    {
        abort_unless((int) $document->school_id === $this->schoolId(), 404);

        return $document;
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }

    private function roles(): array
    {
        return [
            User::ROLE_PARENT,
            User::ROLE_STUDENT,
            User::ROLE_TEACHER,
            User::ROLE_SCHOOL_LIFE,
            User::ROLE_DIRECTOR,
            User::ROLE_ADMIN,
        ];
    }
}
