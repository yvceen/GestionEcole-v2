<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\CourseAttachment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CoursesController extends Controller
{
    use InteractsWithParentPortal;

    public function index(Request $request)
    {
        $children = $this->ownedChildren(['classroom.level']);
        $q = trim((string) $request->get('q', ''));
        $selectedChild = $this->selectedOwnedChild($children, (int) $request->integer('child_id'));
        $classroomIds = $this->selectedClassroomIds($children, $selectedChild);

        $courses = $classroomIds->isEmpty()
            ? collect()
            : $this->visibleCoursesQuery($classroomIds)
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($nested) use ($q) {
                        $nested->where('title', 'like', "%{$q}%")
                            ->orWhere('description', 'like', "%{$q}%");
                    });
                })
                ->with(['classroom.level', 'teacher', 'attachments'])
                ->latest()
                ->paginate(12)
                ->withQueryString();

        return view('parent.courses', [
            'children' => $children,
            'courses' => $courses,
            'q' => $q,
            'childId' => $selectedChild?->id,
        ]);
    }

    public function childCourses(Request $request, Student $student)
    {
        $student = $this->resolveOwnedStudent($student, ['classroom.level']);
        $request->merge(['child_id' => $student->id]);

        return $this->index($request);
    }

    public function download(CourseAttachment $attachment)
    {
        $attachment->load('course');

        abort_unless($attachment->course, 404);
        abort_unless((int) $attachment->school_id === $this->schoolIdOrFail(), 403);
        abort_unless((int) $attachment->course->school_id === $this->schoolIdOrFail(), 404);
        abort_unless(
            $this->ownedChildrenQuery()->where('classroom_id', (int) $attachment->course->classroom_id)->exists(),
            403
        );

        $disk = Storage::disk('public');
        abort_unless($disk->exists($attachment->path), 404, 'File not found.');

        return $disk->download($attachment->path, $attachment->original_name);
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
