<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileDocumentRequestController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $parent = $this->parent($request);
        $children = $this->children($parent);
        $items = DocumentRequest::query()->where('school_id', $parent->school_id)->where('parent_user_id', $parent->id)
            ->with('student.classroom:id,name')->latest()->limit(100)->get();

        return response()->json([
            'items' => $items->map(fn (DocumentRequest $item) => $this->payload($item))->values(),
            'children' => $children->map(fn (Student $child) => ['id' => $child->id, 'name' => $child->full_name, 'classroom' => $child->classroom?->name ?? ''])->values(),
            'types' => DocumentRequest::types(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $parent = $this->parent($request);
        $childIds = $this->children($parent)->pluck('id')->all();
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::in($childIds)],
            'type' => ['required', Rule::in(array_keys(DocumentRequest::types()))],
            'custom_type' => ['nullable', 'required_if:type,other', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'copies' => ['required', 'integer', 'min:1', 'max:5'],
            'language' => ['required', Rule::in(['fr', 'ar', 'en'])],
            'delivery_method' => ['required', Rule::in(['pickup', 'digital'])],
        ]);
        $item = DocumentRequest::create([...$data, 'school_id' => $parent->school_id, 'parent_user_id' => $parent->id, 'requested_by_user_id' => $parent->id]);
        $managerIds = User::query()->where('school_id', $parent->school_id)->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE])->pluck('id')->all();
        $this->notifications->notifyUsers($managerIds, 'document_request', 'Nouvelle demande de document', $parent->name . ' a demandé ' . $item->type_label . '.', ['document_request_id' => $item->id, 'school_id' => $parent->school_id]);

        return response()->json(['message' => 'Demande envoyée.', 'item' => $this->payload($item->load('student.classroom:id,name'))], 201);
    }

    public function cancel(Request $request, DocumentRequest $documentRequest): JsonResponse
    {
        $parent = $this->parent($request);
        abort_unless((int) $documentRequest->school_id === (int) $parent->school_id && (int) $documentRequest->parent_user_id === (int) $parent->id, 404);
        abort_unless($documentRequest->status === DocumentRequest::STATUS_PENDING, 422);
        $documentRequest->update(['status' => DocumentRequest::STATUS_CANCELLED]);

        return response()->json(['message' => 'Demande annulée.']);
    }

    private function parent(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && (string) $user->role === User::ROLE_PARENT, 403);
        return $user;
    }

    private function children(User $parent)
    {
        return Student::query()->active()->where('school_id', $parent->school_id)->where('parent_user_id', $parent->id)
            ->with('classroom:id,name')->orderBy('full_name')->get(['id', 'full_name', 'classroom_id']);
    }

    private function payload(DocumentRequest $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'type_label' => $item->type_label,
            'status' => $item->status,
            'status_label' => DocumentRequest::statuses()[$item->status] ?? $item->status,
            'purpose' => $item->purpose ?? '',
            'copies' => $item->copies,
            'language' => $item->language,
            'delivery_method' => $item->delivery_method,
            'admin_note' => $item->admin_note ?? '',
            'rejection_reason' => $item->rejection_reason ?? '',
            'has_file' => (bool) $item->file_path,
            'student' => ['id' => $item->student_id, 'name' => $item->student?->full_name ?? '', 'classroom' => $item->student?->classroom?->name ?? ''],
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
