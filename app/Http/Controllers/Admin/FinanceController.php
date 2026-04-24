<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceController extends Controller
{
    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if ($schoolId <= 0) {
            abort(403, 'School context missing.');
        }

        return $schoolId;
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();

        $month = $request->get('month') ?: now()->format('Y-m');
        $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $q = trim((string) $request->get('q', ''));
        $levelId = max(0, (int) $request->integer('level_id'));
        $parentId = max(0, (int) $request->integer('parent_id'));
        $classroomId = max(0, (int) $request->integer('classroom_id'));
        $dateFrom = $this->parseOptionalDate((string) $request->get('date_from', ''));
        $dateTo = $this->parseOptionalDate((string) $request->get('date_to', ''), true);

        $monthStart = $monthDate->copy()->startOfMonth();
        $monthEnd = $monthDate->copy()->endOfMonth();
        $paymentsRangeStart = $dateFrom ?: $monthStart;
        $paymentsRangeEnd = $dateTo ?: $monthEnd;

        $thisMonthRevenue = Payment::query()
            ->where('school_id', $schoolId)
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->sum('amount');

        $parents = User::query()
            ->where('school_id', $schoolId)
            ->where('role', 'parent')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $selectedParent = $parentId > 0
            ? $parents->firstWhere('id', $parentId)
            : null;

        if (!$selectedParent) {
            $parentId = 0;
        }

        $classrooms = \App\Models\Classroom::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name', 'level_id']);

        $levels = \App\Models\Level::query()
            ->where('school_id', $schoolId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'sort_order']);

        if ($classroomId > 0 && !$classrooms->contains('id', $classroomId)) {
            $classroomId = 0;
        }

        $recentPayments = $this->applyPaymentFilters(
            Payment::query()
                ->where('school_id', $schoolId)
                ->with(['student.parentUser', 'student.classroom', 'receipt.parent']),
            $schoolId,
            $q,
            $parentId,
            $classroomId,
            $levelId,
            $paymentsRangeStart,
            $paymentsRangeEnd
        )
            ->orderByDesc('paid_at')
            ->paginate(10)
            ->withQueryString();

        $students = $this->financeStudentsQuery($schoolId, $parentId);
        $students = $this->applyFinanceStudentFilters($students, $schoolId, $q, $parentId, $classroomId, $levelId)->get();
        $pricedStudents = $this->pricedStudents($students);

        $parentHistoryPayments = collect();
        $parentHistoryTotal = 0;
        $parentHistoryLastPaidAt = null;

        if ($parentId > 0) {
            $parentHistoryPayments = $this->paymentsForParent(Payment::query(), $parentId, $schoolId)
                ->with(['student.classroom', 'receipt.parent'])
                ->orderByDesc('paid_at')
                ->limit(8)
                ->get();

            $parentHistoryTotal = (float) $this->paymentsForParent(Payment::query(), $parentId, $schoolId)->sum('amount');
            $parentHistoryLastPaidAt = $parentHistoryPayments->first()?->paid_at;
        }

        $from12 = $monthDate->copy()->subMonths(11)->startOfMonth();
        $to12 = $monthDate->copy()->endOfMonth();

        $payments12 = $this->applyPaymentFilters(
            Payment::query()->where('school_id', $schoolId),
            $schoolId,
            $q,
            $parentId,
            $classroomId,
            $levelId,
            null,
            null
        )
            ->whereBetween('period_month', [
                $from12->toDateString(),
                $to12->toDateString(),
            ])
            ->get(['student_id', 'period_month']);

        $paidMap = [];
        foreach ($payments12 as $payment) {
            if (!$payment->period_month) {
                continue;
            }

            $ym = Carbon::parse($payment->period_month)->format('Y-m');
            $paidMap[$payment->student_id][$ym] = true;
        }

        $unpaidThisMonth = [];
        $arrears = [];

        foreach ($pricedStudents as $entry) {
            /** @var \App\Models\Student $student */
            $student = $entry['student'];
            $pricing = $entry['pricing'];
            $monthlyTotal = (float) $pricing['monthly_total'];
            $ymNow = $monthDate->format('Y-m');
            $startMonth = (int) ($pricing['details']['starts_month'] ?? 9);

            $schoolYearStart = Carbon::create($monthDate->year, $startMonth, 1);
            if ($monthDate->month < $startMonth) {
                $schoolYearStart = Carbon::create($monthDate->year - 1, $startMonth, 1);
            }

            $missing = [];
            $cursor = $schoolYearStart->copy();

            while ($cursor->lte($monthDate)) {
                $ym = $cursor->format('Y-m');
                if (empty($paidMap[$student->id][$ym])) {
                    $missing[] = $ym;
                }

                $cursor->addMonth();
            }

            if (in_array($ymNow, $missing, true)) {
                $unpaidThisMonth[] = [
                    'student_id' => $student->id,
                    'student' => $student->full_name,
                    'parent' => $student->parentUser?->name ?? '-',
                    'classroom' => $student->classroom?->name ?? '-',
                    'monthly_total' => $monthlyTotal,
                ];
            }

            $arrearsMonths = array_values(array_filter($missing, fn ($value) => $value !== $ymNow));
            if ($arrearsMonths !== []) {
                $arrears[] = [
                    'student_id' => $student->id,
                    'student' => $student->full_name,
                    'parent' => $student->parentUser?->name ?? '-',
                    'classroom' => $student->classroom?->name ?? '-',
                    'missing_months' => $arrearsMonths,
                    'missing_count' => count($arrearsMonths),
                    'monthly_total' => $monthlyTotal,
                ];
            }
        }

        $unpaidByMonth = [];
        $cursor = $from12->copy();
        while ($cursor->lte($monthDate)) {
            $ym = $cursor->format('Y-m');
            $count = 0;

            foreach ($pricedStudents as $entry) {
                /** @var \App\Models\Student $student */
                $student = $entry['student'];
                $pricing = $entry['pricing'];
                $startMonth = (int) ($pricing['details']['starts_month'] ?? 9);
                $schoolYearStart = Carbon::create($cursor->year, $startMonth, 1);
                if ($cursor->month < $startMonth) {
                    $schoolYearStart = Carbon::create($cursor->year - 1, $startMonth, 1);
                }

                if ($cursor->lt($schoolYearStart)) {
                    continue;
                }

                if (empty($paidMap[$student->id][$ym])) {
                    $count++;
                }
            }

            $unpaidByMonth[] = [
                'ym' => $ym,
                'label' => $cursor->format('M y'),
                'count' => $count,
            ];

            $cursor->addMonth();
        }

        return view('admin.finance.index', compact(
            'month',
            'thisMonthRevenue',
            'recentPayments',
            'unpaidThisMonth',
            'arrears',
            'unpaidByMonth',
            'q',
            'levelId',
            'parentId',
            'classroomId',
            'dateFrom',
            'dateTo',
            'parents',
            'classrooms',
            'levels',
            'selectedParent',
            'parentHistoryPayments',
            'parentHistoryTotal',
            'parentHistoryLastPaidAt'
        ));
    }

    public function createPayment()
    {
        $schoolId = $this->schoolId();

        $parents = User::query()
            ->where('school_id', $schoolId)
            ->where('role', 'parent')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.finance.create-payment', compact('parents'));
    }

    public function storePayment(StorePaymentRequest $request)
    {
        $schoolId = $this->schoolId();
        $data = $request->validated();

        $parent = User::query()
            ->whereKey($data['parent_id'])
            ->where('school_id', $schoolId)
            ->where('role', 'parent')
            ->first();

        if (!$parent) {
            return back()->withErrors(['parent_id' => 'Parent invalide pour cette ecole.'])->withInput();
        }

        $requestedStudentIds = array_values(array_unique(array_map('intval', $data['student_ids'])));
        $linkedStudentIds = $this->linkedStudentsQuery($parent, $schoolId)
            ->whereIn('id', $requestedStudentIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($linkedStudentIds) !== count($requestedStudentIds)) {
            return back()
                ->withErrors(['student_ids' => 'Un ou plusieurs eleves ne correspondent pas a ce parent / ecole.'])
                ->withInput();
        }

        $paidAt = !empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : now();

        try {
            $result = DB::transaction(function () use ($data, $paidAt, $schoolId, $parent) {
                $year = $paidAt->format('Y');

                $last = Receipt::query()
                    ->where('school_id', $schoolId)
                    ->whereYear('issued_at', $year)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                $nextSeq = 1;
                if ($last && $last->receipt_number) {
                    $parts = explode('-', (string) $last->receipt_number);
                    $maybe = end($parts);
                    if (is_numeric($maybe)) {
                        $nextSeq = ((int) $maybe) + 1;
                    }
                }

                $receipt = Receipt::create([
                    'school_id' => $schoolId,
                    'receipt_number' => sprintf('R-%s-%06d', $year, $nextSeq),
                    'parent_id' => $data['parent_id'],
                    'method' => $data['method'],
                    'total_amount' => 0,
                    'issued_at' => $paidAt,
                    'received_by_admin_user_id' => auth()->id(),
                    'note' => $data['note'] ?? null,
                ]);

                $created = 0;
                $skipped = [];
                $total = 0;

                foreach ($data['student_ids'] as $studentId) {
                    $student = $this->linkedStudentsQuery($parent, $schoolId)
                        ->with([
                            'classroom.fee',
                            'feePlan',
                            'parentFee' => function ($query) use ($parent, $schoolId) {
                                $query->where('parent_user_id', $parent->id)
                                    ->where('school_id', $schoolId);
                            },
                        ])
                        ->findOrFail($studentId);

                    $pricing = $this->studentMonthlyPricing($student);
                    $monthlyAmount = (float) $pricing['monthly_total'];
                    if ($monthlyAmount <= 0) {
                        continue;
                    }

                    foreach ($data['months'] as $ym) {
                        $periodMonth = Carbon::createFromFormat('Y-m', $ym)->startOfMonth()->toDateString();

                        $exists = Payment::query()
                            ->where('school_id', $schoolId)
                            ->where('student_id', $student->id)
                            ->whereDate('period_month', $periodMonth)
                            ->exists();

                        if ($exists) {
                            $skipped[] = $student->full_name . " ({$ym})";
                            continue;
                        }

                        $payment = Payment::create([
                            'school_id' => $schoolId,
                            'receipt_id' => $receipt->id,
                            'student_id' => $student->id,
                            'amount' => $monthlyAmount,
                            'method' => $data['method'],
                            'period_month' => $periodMonth,
                            'paid_at' => $paidAt,
                            'received_by_admin_user_id' => auth()->id(),
                            'note' => $data['note'] ?? null,
                        ]);

                        $created++;
                        $total += (float) $payment->amount;
                    }
                }

                if ($created === 0) {
                    throw new \RuntimeException('Aucun paiement cree (deja payes ou frais = 0).');
                }

                $receipt->update(['total_amount' => $total]);

                return [
                    'receipt_id' => $receipt->id,
                    'created' => $created,
                    'skipped' => $skipped,
                    'total' => $total,
                ];
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['months' => $e->getMessage()])->withInput();
        }

        $warning = null;
        if (!empty($result['skipped'])) {
            $warning = "Certains mois/eleves ont ete ignores car deja regles :<br>&bull; "
                . implode('<br>&bull; ', array_slice($result['skipped'], 0, 12));

            if (count($result['skipped']) > 12) {
                $warning .= '<br>... et +' . (count($result['skipped']) - 12) . ' autre(s)';
            }
        }

        return redirect()
            ->route('admin.finance.receipts.show', ['receipt' => $result['receipt_id']])
            ->with('success', 'Paiements enregistres et recu genere.')
            ->with('warning', $warning);
    }

    public function showReceipt(Receipt $receipt)
    {
        abort_unless((int) $receipt->school_id === $this->schoolId(), 404);

        $receipt->load([
            'school',
            'parent',
            'receivedBy',
            'payments.student.parentUser',
            'payments.student.classroom',
        ]);

        $computedTotal = $receipt->payments->sum('amount');
        if ((float) $receipt->total_amount <= 0 && $computedTotal > 0) {
            $receipt->total_amount = $computedTotal;
            $receipt->save();
        }

        return view('admin.finance.receipt', compact('receipt'));
    }

    public function exportReceipt(Receipt $receipt)
    {
        abort_unless((int) $receipt->school_id === $this->schoolId(), 404);

        $receipt->load([
            'school',
            'parent',
            'receivedBy',
            'payments.student.parentUser',
            'payments.student.classroom',
        ]);

        return view('admin.finance.receipt-export', [
            'receipt' => $receipt,
            'autoPrint' => request()->boolean('print', true),
        ]);
    }

    public function parentStudents(User $parent)
    {
        abort_unless($parent->role === 'parent', 404);

        $schoolId = $this->schoolId();
        abort_unless((int) $parent->school_id === $schoolId, 404);

        $students = $this->linkedStudentsQuery($parent, $schoolId)
            ->with([
                'classroom:id,name',
                'classroom.fee',
                'feePlan',
                'parentFee' => function ($query) use ($parent, $schoolId) {
                    $query->where('parent_user_id', $parent->id)
                        ->where('school_id', $schoolId);
                },
            ])
            ->orderBy('full_name')
            ->get(['students.id', 'students.full_name', 'students.classroom_id'])
            ->map(function (Student $student) {
                $pricing = $this->studentMonthlyPricing($student);

                return [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'classroom' => $student->classroom?->name,
                    'fee_source' => $pricing['source'],
                    'fee_source_label' => $this->pricingSourceLabel($pricing['source']),
                    'monthly_total' => $pricing['monthly_total'],
                    'has_monthly_fees' => (float) $pricing['monthly_total'] > 0,
                    'details' => $pricing['details'],
                ];
            })
            ->values();

        return response()->json([
            'count' => $students->count(),
            'students' => $students,
        ]);
    }

    public function parentStudentsWithFees(User $parent)
    {
        return $this->parentStudents($parent);
    }

    public function suggest(Request $request)
    {
        $schoolId = $this->schoolId();

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->with('parentUser:id,name,email')
            ->where(function ($query) use ($q) {
                $query->where('full_name', 'like', "%{$q}%")
                    ->orWhereHas('parentUser', function ($parentQuery) use ($q) {
                        $parentQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    });
            })
            ->limit(8)
            ->get(['id', 'full_name', 'parent_user_id']);

        $parents = User::query()
            ->where('school_id', $schoolId)
            ->where('role', 'parent')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->limit(6)
            ->get(['id', 'name', 'email']);

        return response()->json(
            $parents
                ->map(fn (User $parent) => [
                    'id' => $parent->id,
                    'type' => 'parent',
                    'label' => $parent->name,
                    'meta' => $parent->email,
                ])
                ->concat($students->map(function ($student) {
                    $parent = $student->parentUser;

                    return [
                        'id' => $student->id,
                        'type' => 'student',
                        'label' => $student->full_name,
                        'meta' => $parent ? ($parent->name . ' - ' . $parent->email) : null,
                    ];
                }))
                ->take(10)
                ->values()
        );
    }

    public function unpaid()
    {
        try {
            $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
            if ($schoolId <= 0) {
                return response()->json([
                    'unpaid_month' => [],
                    'unpaid_insurance' => [],
                ]);
            }

            if (!Schema::hasTable('students') || !Schema::hasTable('payments')) {
                return response()->json([
                    'unpaid_month' => [],
                    'unpaid_insurance' => [],
                ]);
            }

            $monthInput = (string) request('month', now()->format('Y-m'));
            $month = preg_match('/^\d{4}-\d{2}$/', $monthInput) ? $monthInput : now()->format('Y-m');
            $periodMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();

            $paidStudentIds = Payment::query()
                ->where('school_id', $schoolId)
                ->whereDate('period_month', $periodMonth)
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $pricedStudents = $this->pricedStudents(
                $this->financeStudentsQuery($schoolId)->get()
            );

            $unpaidMonth = $pricedStudents
                ->reject(fn (array $entry) => in_array((int) $entry['student']->id, $paidStudentIds, true))
                ->take(200)
                ->map(fn (array $entry) => [
                    'student_id' => (int) $entry['student']->id,
                    'student' => $entry['student']->full_name,
                    'parent' => $entry['student']->parentUser?->name ?? '-',
                    'classroom' => $entry['student']->classroom?->name ?? '-',
                    'amount' => (float) $entry['pricing']['monthly_total'],
                ])
                ->values();

            $unpaidInsurance = $pricedStudents
                ->filter(function (array $entry) {
                    $insuranceYearly = (float) ($entry['pricing']['details']['insurance_yearly'] ?? 0);
                    $insurancePaid = (bool) ($entry['pricing']['details']['insurance_paid'] ?? false);

                    return $insuranceYearly > 0 && !$insurancePaid;
                })
                ->take(200)
                ->map(fn (array $entry) => [
                    'student_id' => (int) $entry['student']->id,
                    'student' => $entry['student']->full_name,
                    'parent' => $entry['student']->parentUser?->name ?? '-',
                    'classroom' => $entry['student']->classroom?->name ?? '-',
                    'amount' => (float) ($entry['pricing']['details']['insurance_yearly'] ?? 0),
                ])
                ->values();

            return response()->json([
                'unpaid_month' => $unpaidMonth,
                'unpaid_insurance' => $unpaidInsurance,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'unpaid_month' => [],
                'unpaid_insurance' => [],
            ]);
        }
    }

    public function printStatement(Request $request)
    {
        try {
            $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
            if ($schoolId <= 0 || !Schema::hasTable('payments')) {
                return view('admin.finance.print-statement', [
                    'title' => 'Not implemented yet',
                    'month' => $request->get('month', now()->format('Y-m')),
                    'payments' => collect(),
                    'total' => 0,
                    'message' => 'Not implemented yet',
                ]);
            }

            $monthInput = (string) $request->get('month', now()->format('Y-m'));
            $month = preg_match('/^\d{4}-\d{2}$/', $monthInput) ? $monthInput : now()->format('Y-m');
            $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

            $type = (string) $request->get('type', '');
            $id = (int) $request->get('id', 0);

            $paymentsQuery = Payment::query()
                ->where('school_id', $schoolId)
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->with(['student.parentUser', 'receipt'])
                ->orderByDesc('paid_at');

            $title = 'Not implemented yet';

            if ($type === 'student' && $id > 0) {
                $paymentsQuery->where('student_id', $id);
                $student = Student::query()->where('school_id', $schoolId)->find($id);
                $title = $student ? ('Releve eleve: ' . $student->full_name) : 'Releve eleve';
            } elseif ($type === 'parent' && $id > 0) {
                $paymentsQuery = $this->paymentsForParent($paymentsQuery, $id, $schoolId);
                $parent = User::query()
                    ->where('school_id', $schoolId)
                    ->where('role', 'parent')
                    ->find($id);
                $title = $parent ? ('Releve parent: ' . $parent->name) : 'Releve parent';
            }

            $payments = $paymentsQuery->get();
            $total = (float) $payments->sum('amount');

            return view('admin.finance.print-statement', [
                'title' => $title,
                'month' => $month,
                'type' => $type,
                'id' => $id,
                'payments' => $payments,
                'total' => $total,
                'message' => 'Not implemented yet',
            ]);
        } catch (\Throwable $e) {
            return view('admin.finance.print-statement', [
                'title' => 'Not implemented yet',
                'month' => $request->get('month', now()->format('Y-m')),
                'payments' => collect(),
                'total' => 0,
                'message' => 'Not implemented yet',
            ]);
        }
    }

    public function searchParents(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $schoolId = $this->schoolId();

        $parents = User::query()
            ->where('school_id', $schoolId)
            ->where('role', 'parent')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($parents->map(fn ($parent) => [
            'id' => $parent->id,
            'label' => $parent->name,
            'meta' => $parent->email,
        ]));
    }

    private function linkedStudentsQuery(User $parent, int $schoolId): Builder
    {
        return Student::query()
            ->where('school_id', $schoolId)
            ->where(function (Builder $query) use ($parent, $schoolId) {
                $query->where('parent_user_id', $parent->id);

                if ($this->supportsParentStudentFees()) {
                    $query->orWhereExists(function ($subQuery) use ($parent, $schoolId) {
                        $subQuery->selectRaw('1')
                            ->from('parent_student_fees')
                            ->whereColumn('parent_student_fees.student_id', 'students.id')
                            ->where('parent_student_fees.parent_user_id', $parent->id);

                        if ($this->supportsParentStudentFeeSchoolColumn()) {
                            $subQuery->where('parent_student_fees.school_id', $schoolId);
                        }
                    });
                }
            });
    }

    private function financeStudentsQuery(int $schoolId, int $parentId = 0): Builder
    {
        return Student::query()
            ->where('school_id', $schoolId)
            ->with([
                'parentUser:id,name,email,phone',
                'classroom:id,name,level_id',
                'classroom.fee',
                'feePlan',
                'parentFee' => function ($query) use ($schoolId, $parentId) {
                    if ($this->supportsParentStudentFeeSchoolColumn()) {
                        $query->where('school_id', $schoolId);
                    }

                    if ($parentId > 0) {
                        $query->where('parent_user_id', $parentId);
                    }
                },
            ])
            ->where(function (Builder $pricingQuery) use ($schoolId, $parentId) {
                $pricingQuery->whereHas('feePlan')
                    ->orWhereHas('classroom.fee', function ($query) {
                        if ($this->supportsActiveClassroomFees()) {
                            $query->where('is_active', true);
                        }
                    });

                if ($this->supportsParentStudentFees()) {
                    $pricingQuery->orWhereHas('parentFee', function ($query) use ($schoolId, $parentId) {
                        if ($this->supportsParentStudentFeeSchoolColumn()) {
                            $query->where('school_id', $schoolId);
                        }

                        if ($parentId > 0) {
                            $query->where('parent_user_id', $parentId);
                        }
                    });
                }
            });
    }

    private function applyFinanceStudentFilters(
        Builder $query,
        int $schoolId,
        string $q = '',
        int $parentId = 0,
        int $classroomId = 0,
        int $levelId = 0
    ): Builder {
        $query
            ->when($q !== '', function (Builder $studentQuery) use ($q) {
                $studentQuery->where(function (Builder $nested) use ($q) {
                    $nested->where('full_name', 'like', "%{$q}%")
                        ->orWhereHas('parentUser', function ($parentQuery) use ($q) {
                            $parentQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%");
                        });
                });
            })
            ->when($levelId > 0, fn (Builder $studentQuery) => $studentQuery->whereHas('classroom', fn ($classroomQuery) => $classroomQuery->where('level_id', $levelId)))
            ->when($classroomId > 0, fn (Builder $studentQuery) => $studentQuery->where('classroom_id', $classroomId));

        if ($parentId > 0) {
            $query = $this->filterStudentsForParent($query, $parentId, $schoolId);
        }

        return $query->orderBy('full_name');
    }

    private function pricedStudents(Collection $students): Collection
    {
        return $students
            ->map(function (Student $student) {
                $pricing = $this->studentMonthlyPricing($student);

                return [
                    'student' => $student,
                    'pricing' => $pricing,
                ];
            })
            ->filter(fn (array $entry) => (float) $entry['pricing']['monthly_total'] > 0)
            ->values();
    }

    private function studentMonthlyPricing(Student $student): array
    {
        $pricingSource = $student->parentFee;
        $source = 'parent_student_fee';

        if (!$pricingSource) {
            $pricingSource = $student->feePlan;
            $source = 'student_fee_plan';
        }

        if (!$pricingSource) {
            $classroomFee = $student->classroom?->fee;
            $pricingSource = $classroomFee && ($classroomFee->is_active ?? true) ? $classroomFee : null;
            $source = $pricingSource ? 'classroom_fee' : 'none';
        }

        $tuition = (float) ($pricingSource->tuition_monthly ?? 0);
        $canteen = (float) ($pricingSource->canteen_monthly ?? 0);
        $transport = (float) ($pricingSource->transport_monthly ?? 0);
        $insuranceYearly = (float) ($pricingSource->insurance_yearly ?? 0);
        $insurancePaid = (bool) ($pricingSource->insurance_paid ?? false);
        $startsMonth = (int) ($pricingSource->starts_month ?? 9);

        return [
            'source' => $source,
            'monthly_total' => $tuition + $canteen + $transport,
            'details' => [
                'tuition' => $tuition,
                'canteen' => $canteen,
                'transport' => $transport,
                'insurance_yearly' => $insuranceYearly,
                'insurance_paid' => $insurancePaid,
                'starts_month' => $startsMonth,
            ],
        ];
    }

    private function pricingSourceLabel(string $source): string
    {
        return match ($source) {
            'parent_student_fee' => 'Tarif parent',
            'student_fee_plan' => 'Plan eleve',
            'classroom_fee' => 'Tarif classe',
            default => 'Aucun tarif',
        };
    }

    private function parseOptionalDate(string $value, bool $endOfDay = false): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::parse($value);

            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function applyPaymentFilters(
        Builder $query,
        int $schoolId,
        string $q = '',
        int $parentId = 0,
        int $classroomId = 0,
        int $levelId = 0,
        ?Carbon $dateFrom = null,
        ?Carbon $dateTo = null
    ): Builder {
        return $query
            ->when($dateFrom, fn ($builder) => $builder->where('paid_at', '>=', $dateFrom))
            ->when($dateTo, fn ($builder) => $builder->where('paid_at', '<=', $dateTo))
            ->when($parentId > 0, fn ($builder) => $this->paymentsForParent($builder, $parentId, $schoolId))
            ->when($classroomId > 0, fn ($builder) => $builder->whereHas('student', fn ($studentQuery) => $studentQuery->where('classroom_id', $classroomId)))
            ->when($levelId > 0, fn ($builder) => $builder->whereHas('student.classroom', fn ($classroomQuery) => $classroomQuery->where('level_id', $levelId)))
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($nested) use ($q) {
                    $nested->whereHas('student', fn ($studentQuery) => $studentQuery->where('full_name', 'like', "%{$q}%"))
                        ->orWhereHas('student.parentUser', function ($parentQuery) use ($q) {
                            $parentQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%");
                        })
                        ->orWhereHas('receipt', fn ($receiptQuery) => $receiptQuery->where('receipt_number', 'like', "%{$q}%"));
                });
            });
    }

    private function paymentsForParent(Builder $query, int $parentId, int $schoolId): Builder
    {
        return $query->where(function ($builder) use ($parentId, $schoolId) {
            $builder->whereHas('receipt', fn ($receiptQuery) => $receiptQuery
                ->where('school_id', $schoolId)
                ->where('parent_id', $parentId))
                ->orWhereHas('student', function ($studentQuery) use ($parentId, $schoolId) {
                    $studentQuery->where('school_id', $schoolId)
                        ->where(function ($linkedQuery) use ($parentId, $schoolId) {
                            $linkedQuery->where('parent_user_id', $parentId);

                            if ($this->supportsParentStudentFees()) {
                                $linkedQuery->orWhereExists(function ($subQuery) use ($parentId, $schoolId) {
                                    $subQuery->selectRaw('1')
                                        ->from('parent_student_fees')
                                        ->whereColumn('parent_student_fees.student_id', 'students.id')
                                        ->where('parent_student_fees.parent_user_id', $parentId);

                                    if ($this->supportsParentStudentFeeSchoolColumn()) {
                                        $subQuery->where('parent_student_fees.school_id', $schoolId);
                                    }
                                });
                            }
                        });
                });
        });
    }

    private function filterStudentsForParent(Builder $query, int $parentId, int $schoolId): Builder
    {
        return $query->where(function (Builder $studentQuery) use ($parentId, $schoolId) {
            $studentQuery->where('parent_user_id', $parentId);

            if ($this->supportsParentStudentFees()) {
                $studentQuery->orWhereExists(function ($subQuery) use ($parentId, $schoolId) {
                    $subQuery->selectRaw('1')
                        ->from('parent_student_fees')
                        ->whereColumn('parent_student_fees.student_id', 'students.id')
                        ->where('parent_student_fees.parent_user_id', $parentId);

                    if ($this->supportsParentStudentFeeSchoolColumn()) {
                        $subQuery->where('parent_student_fees.school_id', $schoolId);
                    }
                });
            }
        });
    }

    private function supportsParentStudentFees(): bool
    {
        return Schema::hasTable('parent_student_fees')
            && Schema::hasColumn('parent_student_fees', 'student_id')
            && Schema::hasColumn('parent_student_fees', 'parent_user_id');
    }

    private function supportsParentStudentFeeSchoolColumn(): bool
    {
        return Schema::hasTable('parent_student_fees')
            && Schema::hasColumn('parent_student_fees', 'school_id');
    }

    private function supportsActiveClassroomFees(): bool
    {
        return Schema::hasTable('classroom_fees')
            && Schema::hasColumn('classroom_fees', 'is_active');
    }
}
