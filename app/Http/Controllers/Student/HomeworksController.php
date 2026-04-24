<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\InteractsWithStudentPortal;
use App\Models\HomeworkAttachment;
use App\Services\HomeworkAttachmentStorageService;
use Illuminate\Http\Request;

class HomeworksController extends Controller
{
    use InteractsWithStudentPortal;

    public function index(Request $request)
    {
        $student = $this->currentStudent();

        $q = trim((string) $request->get('q', ''));

        $homeworks = $this->visibleHomeworksQuery($student)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->with(['classroom.level', 'subject', 'teacher', 'attachments'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('student.homeworks.index', compact('student', 'homeworks', 'q'));
    }

    public function download(HomeworkAttachment $attachment)
    {
        $student = $this->currentStudent();
        $schoolId = $this->schoolIdOrFail();

        $attachment->load('homework');

        abort_unless($attachment->homework, 404);
        abort_unless((int) $attachment->school_id === $schoolId, 403);
        abort_unless((int) $attachment->homework->school_id === $schoolId, 404);
        abort_unless((int) $attachment->homework->classroom_id === (int) $student->classroom_id, 403);

        return app(HomeworkAttachmentStorageService::class)->downloadResponse($attachment);
    }
}
