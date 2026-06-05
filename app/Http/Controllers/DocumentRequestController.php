<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentRequestController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($user);
        $isParent = (string) $user->role === User::ROLE_PARENT;
        $status = trim((string) $request->get('status', ''));
        $q = trim((string) $request->get('q', ''));

        $requests = DocumentRequest::query()
            ->where('school_id', $schoolId)
            ->when($isParent, fn ($query) => $query->where('parent_user_id', $user->id))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where(function ($nested) use ($q) {
                $nested->where('custom_type', 'like', "%{$q}%")
                    ->orWhereHas('student', fn ($student) => $student->where('full_name', 'like', "%{$q}%"))
                    ->orWhereHas('parent', fn ($parent) => $parent->where('name', 'like', "%{$q}%"));
            }))
            ->with(['student.classroom:id,name', 'parent:id,name,phone'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $base = DocumentRequest::query()->where('school_id', $schoolId)
            ->when($isParent, fn ($query) => $query->where('parent_user_id', $user->id));
        $stats = [
            'pending' => (clone $base)->where('status', DocumentRequest::STATUS_PENDING)->count(),
            'processing' => (clone $base)->where('status', DocumentRequest::STATUS_PROCESSING)->count(),
            'ready' => (clone $base)->where('status', DocumentRequest::STATUS_READY)->count(),
            'delivered' => (clone $base)->where('status', DocumentRequest::STATUS_DELIVERED)->count(),
        ];

        return view('document-requests.index', $this->viewData($user, compact('requests', 'stats', 'status', 'q', 'isParent')));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        abort_unless((string) $user->role === User::ROLE_PARENT, 403);
        $children = Student::query()->active()->where('school_id', $this->schoolId($user))
            ->where('parent_user_id', $user->id)->with('classroom:id,name')->orderBy('full_name')->get();

        return view('document-requests.create', $this->viewData($user, compact('children')));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless((string) $user->role === User::ROLE_PARENT, 403);
        $schoolId = $this->schoolId($user);
        $childIds = Student::query()->active()->where('school_id', $schoolId)->where('parent_user_id', $user->id)->pluck('id')->all();
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::in($childIds)],
            'type' => ['required', Rule::in(array_keys(DocumentRequest::types()))],
            'custom_type' => ['nullable', 'required_if:type,other', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'copies' => ['required', 'integer', 'min:1', 'max:5'],
            'language' => ['required', Rule::in(['fr', 'ar', 'en'])],
            'delivery_method' => ['required', Rule::in(['pickup', 'digital'])],
        ]);

        $documentRequest = DocumentRequest::create([
            ...$data,
            'school_id' => $schoolId,
            'parent_user_id' => $user->id,
            'requested_by_user_id' => $user->id,
            'status' => DocumentRequest::STATUS_PENDING,
        ]);

        foreach ([User::ROLE_ADMIN => 'admin', User::ROLE_SCHOOL_LIFE => 'school-life', User::ROLE_ACCUEIL => 'accueil'] as $role => $prefix) {
            $managerIds = User::query()->where('school_id', $schoolId)->where('role', $role)->pluck('id')->all();
            $this->notifications->notifyUsers($managerIds, 'document_request', 'Nouvelle demande de document', $user->name . ' a demandé ' . $documentRequest->type_label . '.', [
                'document_request_id' => $documentRequest->id,
                'school_id' => $schoolId,
                'route' => route($prefix . '.document-requests.show', $documentRequest, absolute: false),
            ]);
        }

        return redirect()->route('parent.document-requests.show', $documentRequest)->with('success', 'Votre demande a été envoyée.');
    }

    public function show(Request $request, DocumentRequest $documentRequest)
    {
        $this->authorizeRequest($request->user(), $documentRequest);
        $documentRequest->load(['student.classroom:id,name', 'parent:id,name,email,phone', 'requestedBy:id,name', 'processedBy:id,name']);

        return view('document-requests.show', $this->viewData($request->user(), compact('documentRequest')));
    }

    public function update(Request $request, DocumentRequest $documentRequest)
    {
        $user = $request->user();
        $this->authorizeManager($user, $documentRequest);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(DocumentRequest::statuses()))],
            'admin_note' => ['nullable', 'string', 'max:3000'],
            'rejection_reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:3000'],
            'document_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        unset($data['document_file']);

        if ($request->hasFile('document_file')) {
            if ($documentRequest->file_path) {
                Storage::disk('local')->delete($documentRequest->file_path);
            }
            $file = $request->file('document_file');
            $data['file_path'] = $file->store('document-requests/' . $documentRequest->school_id, 'local');
            $data['original_name'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getMimeType();
            $data['size_bytes'] = $file->getSize();
        }
        $data['processed_by_user_id'] = $user->id;
        $data['processed_at'] = now();
        if ($data['status'] === DocumentRequest::STATUS_READY) {
            $data['ready_at'] = now();
        }
        if ($data['status'] === DocumentRequest::STATUS_DELIVERED) {
            $data['delivered_at'] = now();
        }
        $documentRequest->update($data);

        if ($documentRequest->parent_user_id) {
            $this->notifications->notifyParents([$documentRequest->parent_user_id], 'document_request_status', 'Mise à jour de votre demande', $documentRequest->type_label . ' : ' . (DocumentRequest::statuses()[$documentRequest->status] ?? $documentRequest->status) . '.', [
                'document_request_id' => $documentRequest->id,
                'school_id' => $documentRequest->school_id,
                'route' => route('parent.document-requests.show', $documentRequest, absolute: false),
            ]);
        }

        return back()->with('success', 'La demande a été mise à jour.');
    }

    public function cancel(Request $request, DocumentRequest $documentRequest)
    {
        $this->authorizeRequest($request->user(), $documentRequest);
        abort_unless((string) $request->user()->role === User::ROLE_PARENT && $documentRequest->status === DocumentRequest::STATUS_PENDING, 422);
        $documentRequest->update(['status' => DocumentRequest::STATUS_CANCELLED]);

        return back()->with('success', 'La demande a été annulée.');
    }

    public function download(Request $request, DocumentRequest $documentRequest)
    {
        $this->authorizeRequest($request->user(), $documentRequest);
        abort_unless($documentRequest->file_path && Storage::disk('local')->exists($documentRequest->file_path), 404);

        return Storage::disk('local')->download($documentRequest->file_path, $documentRequest->original_name ?: 'document.pdf');
    }

    private function authorizeRequest(User $user, DocumentRequest $documentRequest): void
    {
        abort_unless((int) $documentRequest->school_id === $this->schoolId($user), 404);
        if ((string) $user->role === User::ROLE_PARENT) {
            abort_unless((int) $documentRequest->parent_user_id === (int) $user->id, 404);
        }
    }

    private function authorizeManager(User $user, DocumentRequest $documentRequest): void
    {
        $this->authorizeRequest($user, $documentRequest);
        abort_unless(in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE, User::ROLE_ACCUEIL], true), 403);
    }

    private function schoolId(User $user): int
    {
        $id = app()->bound('current_school_id') ? (int) app('current_school_id') : (int) $user->school_id;
        abort_unless($id > 0, 403);
        return $id;
    }

    private function viewData(User $user, array $data): array
    {
        $prefix = match ((string) $user->role) {
            User::ROLE_ADMIN => 'admin.document-requests',
            User::ROLE_SCHOOL_LIFE => 'school-life.document-requests',
            User::ROLE_ACCUEIL => 'accueil.document-requests',
            User::ROLE_DIRECTOR => 'director.document-requests',
            default => 'parent.document-requests',
        };
        $layout = match ((string) $user->role) {
            User::ROLE_ADMIN => 'admin-layout',
            User::ROLE_SCHOOL_LIFE => 'school-life-layout',
            User::ROLE_ACCUEIL => 'accueil-layout',
            User::ROLE_DIRECTOR => 'director-layout',
            default => 'parent-layout',
        };

        return $data + [
            'routePrefix' => $prefix,
            'layoutComponent' => $layout,
            'canManage' => in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE, User::ROLE_ACCUEIL], true),
        ];
    }
}
