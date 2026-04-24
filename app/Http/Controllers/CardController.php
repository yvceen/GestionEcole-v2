<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Services\CardTokenService;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function __construct(
        private readonly CardTokenService $cards,
    ) {
    }

    public function adminIndex(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        $scope = $this->normalizeScope((string) $request->get('scope', 'students'));
        $q = trim((string) $request->get('q', ''));

        return view('admin.cards.index', $this->buildIndexData($schoolId, $scope, $q, true));
    }

    public function schoolLifeIndex(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        $scope = $this->normalizeScope((string) $request->get('scope', 'students'));
        $q = trim((string) $request->get('q', ''));

        return view('school-life.cards.index', $this->buildIndexData($schoolId, $scope, $q, false));
    }

    public function adminShowStudent(Student $student)
    {
        return $this->renderStudentCard($this->resolveSchoolStudent($student), 'admin.cards.index');
    }

    public function adminShowParent(User $user)
    {
        return $this->renderParentCard($this->resolveSchoolParent($user), 'admin.cards.index');
    }

    public function schoolLifeShowStudent(Student $student)
    {
        return $this->renderStudentCard($this->resolveSchoolStudent($student), 'school-life.cards.index');
    }

    public function schoolLifeShowParent(User $user)
    {
        return $this->renderParentCard($this->resolveSchoolParent($user), 'school-life.cards.index');
    }

    public function regenerateStudent(Student $student)
    {
        $student = $this->cards->ensureStudentToken($this->resolveSchoolStudent($student), true);

        return redirect()
            ->route('admin.cards.students.show', $student)
            ->with('success', 'Carte eleve regeneree avec un nouveau QR code.');
    }

    public function regenerateParent(User $user)
    {
        $user = $this->cards->ensureParentToken($this->resolveSchoolParent($user), true);

        return redirect()
            ->route('admin.cards.parents.show', $user)
            ->with('success', 'Carte parent regeneree avec un nouveau QR code.');
    }

    public function parentIndex()
    {
        $parent = auth()->user();
        abort_unless($parent && $parent->role === User::ROLE_PARENT, 403);

        $parent = $this->cards->ensureParentToken($parent);
        $children = $this->ownedChildren(['classroom.level'])
            ->map(fn (Student $student) => $this->cards->ensureStudentToken($student));

        return view('parent.cards.index', compact('parent', 'children'));
    }

    public function parentShowSelf()
    {
        $parent = auth()->user();
        abort_unless($parent && $parent->role === User::ROLE_PARENT, 403);

        return $this->renderParentCard($this->cards->ensureParentToken($parent), 'parent.cards.index');
    }

    public function parentShowStudent(Student $student)
    {
        return $this->renderStudentCard($this->cards->ensureStudentToken($this->resolveOwnedStudent($student)), 'parent.cards.index');
    }

    public function studentShow()
    {
        $student = $this->currentStudent(['classroom.level']);

        return $this->renderStudentCard($this->cards->ensureStudentToken($student), 'student.dashboard');
    }

    private function buildIndexData(int $schoolId, string $scope, string $q, bool $canRegenerate): array
    {
        if ($scope === 'parents') {
            $items = User::query()
                ->where('school_id', $schoolId)
                ->where('role', User::ROLE_PARENT)
                ->withCount('children')
                ->with(['children:id,parent_user_id,full_name'])
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($nested) use ($q) {
                        $nested->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    });
                })
                ->orderBy('name')
                ->paginate(16)
                ->withQueryString()
                ->through(fn (User $user) => $this->cards->ensureParentToken($user));
        } else {
            $items = Student::query()
                ->where('school_id', $schoolId)
                ->active()
                ->with(['classroom.level', 'parentUser:id,name,phone,email'])
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($nested) use ($q) {
                        $nested->where('full_name', 'like', "%{$q}%")
                            ->orWhereHas('classroom', fn ($classroom) => $classroom->where('name', 'like', "%{$q}%"))
                            ->orWhereHas('parentUser', fn ($parent) => $parent->where('name', 'like', "%{$q}%"));
                    });
                })
                ->orderBy('full_name')
                ->paginate(16)
                ->withQueryString()
                ->through(fn (Student $student) => $this->cards->ensureStudentToken($student));
        }

        return compact('scope', 'q', 'items', 'canRegenerate');
    }

    private function renderStudentCard(Student $student, string $backRoute)
    {
        $student = $this->cards->ensureStudentToken($student);
        $school = app()->bound('currentSchool')
            ? app('currentSchool')
            : (app()->bound('current_school') ? app('current_school') : $student->school);

        return view('cards.show', [
            'school' => $school,
            'holderName' => $student->full_name,
            'roleLabel' => 'Carte eleve',
            'detailLines' => array_values(array_filter([
                $student->classroom?->name ? 'Classe : ' . $student->classroom->name : null,
                $student->classroom?->level?->name ? 'Niveau : ' . $student->classroom->level->name : null,
                $student->parentUser?->name ? 'Parent : ' . $student->parentUser->name : null,
            ])),
            'token' => (string) $student->card_token,
            'qrPayload' => $this->cards->qrPayloadForStudent($student),
            'qrSvg' => $this->cards->qrSvg($this->cards->qrPayloadForStudent($student)),
            'backUrl' => route($backRoute),
            'printLabel' => 'Carte eleve',
        ]);
    }

    private function renderParentCard(User $parent, string $backRoute)
    {
        $parent = $this->cards->ensureParentToken($parent);
        $school = app()->bound('currentSchool')
            ? app('currentSchool')
            : (app()->bound('current_school') ? app('current_school') : $parent->school);

        return view('cards.show', [
            'school' => $school,
            'holderName' => $parent->name,
            'roleLabel' => 'Carte parent',
            'detailLines' => array_values(array_filter([
                $parent->email ? 'Email : ' . $parent->email : null,
                $parent->phone ? 'Telephone : ' . $parent->phone : null,
                $parent->children->isNotEmpty() ? 'Enfants : ' . $parent->children->pluck('full_name')->implode(', ') : null,
            ])),
            'token' => (string) $parent->card_token,
            'qrPayload' => $this->cards->qrPayloadForParent($parent),
            'qrSvg' => $this->cards->qrSvg($this->cards->qrPayloadForParent($parent)),
            'backUrl' => route($backRoute),
            'printLabel' => 'Carte parent',
        ]);
    }

    private function resolveSchoolStudent(Student $student): Student
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $student->school_id === $schoolId && !$student->is_archived, 404);

        return $student->loadMissing(['classroom.level', 'parentUser:id,name']);
    }

    private function resolveSchoolParent(User $user): User
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $user->school_id === $schoolId && $user->role === User::ROLE_PARENT, 404);

        return $user->loadMissing('children:id,parent_user_id,full_name');
    }

    private function normalizeScope(string $scope): string
    {
        return $scope === 'parents' ? 'parents' : 'students';
    }

    private function ownedChildren(array $with = [])
    {
        $user = auth()->user();
        abort_unless($user && $user->role === User::ROLE_PARENT, 403);

        return Student::query()
            ->with($with)
            ->where('school_id', $this->currentSchoolId())
            ->where('parent_user_id', $user->id)
            ->active()
            ->orderBy('full_name')
            ->get();
    }

    private function resolveOwnedStudent(Student $student): Student
    {
        return Student::query()
            ->with(['classroom.level', 'parentUser:id,name'])
            ->where('school_id', $this->currentSchoolId())
            ->where('parent_user_id', auth()->id())
            ->active()
            ->findOrFail($student->id);
    }

    private function currentStudent(array $with = []): Student
    {
        return Student::query()
            ->with($with)
            ->where('school_id', $this->currentSchoolId())
            ->where('user_id', auth()->id())
            ->active()
            ->firstOrFail();
    }

    private function currentSchoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
