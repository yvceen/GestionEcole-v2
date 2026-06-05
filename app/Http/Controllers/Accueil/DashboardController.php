<?php

namespace App\Http\Controllers\Accueil;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\FeedbackCase;
use App\Models\Student;
use App\Models\User;
use App\Models\VisitorVisit;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : (int) $user->school_id;
        abort_unless($schoolId > 0, 403);

        $q = trim((string) $request->get('q', ''));

        $stats = [
            'visitors_inside' => VisitorVisit::query()
                ->where('school_id', $schoolId)
                ->where('status', VisitorVisit::STATUS_CHECKED_IN)
                ->count(),
            'visitors_expected' => VisitorVisit::query()
                ->where('school_id', $schoolId)
                ->where('status', VisitorVisit::STATUS_EXPECTED)
                ->whereDate('expected_at', today())
                ->count(),
            'documents_pending' => DocumentRequest::query()
                ->where('school_id', $schoolId)
                ->whereIn('status', [DocumentRequest::STATUS_PENDING, DocumentRequest::STATUS_PROCESSING])
                ->count(),
            'feedback_open' => FeedbackCase::query()
                ->where('school_id', $schoolId)
                ->whereIn('status', ['new', 'reviewing', 'waiting_submitter'])
                ->count(),
        ];

        $students = Student::query()
            ->active()
            ->where('school_id', $schoolId)
            ->with(['classroom:id,name', 'parentUser:id,name,email,phone'])
            ->when($q !== '', fn ($query) => $query->where(function ($nested) use ($q) {
                $nested->where('full_name', 'like', "%{$q}%")
                    ->orWhereHas('classroom', fn ($classroom) => $classroom->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('parentUser', fn ($parent) => $parent
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%"));
            }))
            ->orderBy('full_name')
            ->limit(8)
            ->get(['id', 'full_name', 'classroom_id', 'parent_user_id']);

        $parents = User::query()
            ->where('school_id', $schoolId)
            ->where('role', User::ROLE_PARENT)
            ->when($q !== '', fn ($query) => $query->where(function ($nested) use ($q) {
                $nested->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            }))
            ->withCount('children')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'email', 'phone']);

        $recentVisitors = VisitorVisit::query()
            ->where('school_id', $schoolId)
            ->with(['hostUser:id,name', 'student:id,full_name'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $recentDocuments = DocumentRequest::query()
            ->where('school_id', $schoolId)
            ->with(['student:id,full_name', 'parent:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        $recentFeedback = FeedbackCase::query()
            ->where('school_id', $schoolId)
            ->with(['submitter:id,name', 'assignedTo:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        return view('accueil.dashboard', compact(
            'q',
            'parents',
            'recentDocuments',
            'recentFeedback',
            'recentVisitors',
            'stats',
            'students'
        ));
    }
}
