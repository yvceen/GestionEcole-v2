<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentStudentFee;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class ParentFeesController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        $parents = User::where('role', 'parent')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // counts children per parent (efficient enough for small DB)
        $childrenCounts = Student::selectRaw('parent_user_id, COUNT(*) as cnt')
            ->whereNotNull('parent_user_id')
            ->groupBy('parent_user_id')
            ->pluck('cnt', 'parent_user_id');

        return view('admin.parents.index', compact('parents', 'childrenCounts', 'q'));
    }

    public function edit(User $parent)
    {
        abort_unless($parent->role === 'parent', 404);

        $students = Student::where('parent_user_id', $parent->id)
            ->with('classroom')
            ->orderBy('full_name')
            ->get();

        $fees = ParentStudentFee::where('parent_user_id', $parent->id)
            ->get()
            ->keyBy('student_id');

        return view('admin.parents.fees', compact('parent', 'students', 'fees'));
    }

    public function update(Request $request, User $parent)
    {
        abort_unless($parent->role === 'parent', 404);

        $data = $request->validate([
            'fees' => ['required','array'],
            'fees.*.tuition_monthly' => ['nullable','numeric','min:0'],
            'fees.*.transport_monthly' => ['nullable','numeric','min:0'],
            'fees.*.canteen_monthly' => ['nullable','numeric','min:0'],
            'fees.*.insurance_yearly' => ['nullable','numeric','min:0'],
            'fees.*.starts_month' => ['nullable','integer','min:1','max:12'],
            'fees.*.notes' => ['nullable','string','max:500'],
        ]);

        // ensure students belong to this parent
        $studentIds = array_map('intval', array_keys($data['fees']));
        $validIds = Student::where('parent_user_id', $parent->id)
            ->whereIn('id', $studentIds)
            ->pluck('id')
            ->all();

        if (count($validIds) !== count($studentIds)) {
            abort(403, 'Invalid student selection');
        }

        foreach ($data['fees'] as $studentId => $row) {
            ParentStudentFee::updateOrCreate(
                ['parent_user_id' => $parent->id, 'student_id' => (int)$studentId],
                [
                    'tuition_monthly' => (float)($row['tuition_monthly'] ?? 0),
                    'transport_monthly' => (float)($row['transport_monthly'] ?? 0),
                    'canteen_monthly' => (float)($row['canteen_monthly'] ?? 0),
                    'insurance_yearly' => (float)($row['insurance_yearly'] ?? 0),
                    'starts_month' => (int)($row['starts_month'] ?? 9),
                    'notes' => $row['notes'] ?? null,
                ]
            );
        }

        return redirect()
            ->route('admin.parents.fees.edit', $parent)
            ->with('success', 'Tarifs enregistrés avec succès.');
    }
}
