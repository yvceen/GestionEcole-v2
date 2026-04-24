<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\HomeworkAttachment;
use App\Services\HomeworkAttachmentStorageService;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HomeworkController extends Controller
{
    use InteractsWithParentPortal;

    public function index(Request $request)
    {
        $children = $this->ownedChildren(['classroom.level']);
        $q = trim((string) $request->get('q', ''));
        $selectedChild = $this->selectedOwnedChild($children, (int) $request->integer('child_id'));
        $classroomIds = $this->selectedClassroomIds($children, $selectedChild);

        $homeworks = $classroomIds->isEmpty()
            ? collect()
            : $this->visibleHomeworksQuery($classroomIds)
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($nested) use ($q) {
                        $nested->where('title', 'like', "%{$q}%")
                            ->orWhere('description', 'like', "%{$q}%");
                    });
                })
                ->with(['classroom.level', 'subject', 'attachments', 'teacher:id,name'])
                ->latest()
                ->paginate(12)
                ->withQueryString();

        return view('parent.homeworks', [
            'children' => $children,
            'homeworks' => $homeworks,
            'childId' => $selectedChild?->id,
            'q' => $q,
        ]);
    }

    public function childHomeworks(Request $request, Student $student)
    {
        $student = $this->resolveOwnedStudent($student, ['classroom.level']);
        $request->merge(['child_id' => $student->id]);

        return $this->index($request);
    }

    public function download(HomeworkAttachment $attachment)
    {
        $attachment->load('homework');

        abort_unless($attachment->homework, 404);
        abort_unless((int) $attachment->school_id === $this->schoolIdOrFail(), 403);
        abort_unless((int) $attachment->homework->school_id === $this->schoolIdOrFail(), 404);
        abort_unless(
            $this->ownedChildrenQuery()->where('classroom_id', (int) $attachment->homework->classroom_id)->exists(),
            403
        );

        return app(HomeworkAttachmentStorageService::class)->downloadResponse($attachment);
    }

    private function selectedOwnedChild(Collection $children, int $childId): ?Student
    {
        return $childId > 0 ? $children->firstWhere('id', $childId) : null;
    }

    private function selectedClassroomIds(Collection $children, ?Student $student = null): Collection
    {
        if ($student && $student->classroom_id) {
            return collect([(int) $student->classroom_id]);
        }

        return $children->pluck('classroom_id')->filter()->unique()->values();
    }
}
