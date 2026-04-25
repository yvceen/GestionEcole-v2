<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicYearService;
use App\Services\FinanceArrearsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileFinanceController extends Controller
{
    use InteractsWithParentPortal;

    public function __construct(
        private readonly FinanceArrearsService $arrearsService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless((string) $user->role === User::ROLE_PARENT, 403);

        $children = $this->ownedChildren(['classroom:id,name', 'feePlan']);
        $selectedChild = $this->resolveSelectedChild($children, $request->integer('child_id'));
        $schoolId = $this->schoolIdOrFail();
        $academicYearId = app(AcademicYearService::class)
            ->resolveYearForSchool($schoolId, $request->integer('academic_year_id') ?: null)
            ->id;
        $scopedChildren = $selectedChild ? $children->where('id', $selectedChild->id)->values() : $children;
        $childIds = $scopedChildren->pluck('id');

        $paymentsQuery = $this->ownedPaymentsQuery()
            ->when($selectedChild, fn (Builder $query) => $query->where('student_id', $selectedChild->id))
            ->with(['student.classroom', 'receipt']);

        $payments = (clone $paymentsQuery)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $receipts = $this->ownedReceiptsQuery()
            ->with([
                'payments' => fn ($query) => $query
                    ->where('school_id', $schoolId)
                    ->whereIn('student_id', $childIds)
                    ->with(['student.classroom'])
                    ->orderByDesc('paid_at'),
            ])
            ->whereHas('payments', fn ($query) => $query
                ->where('school_id', $schoolId)
                ->whereIn('student_id', $childIds))
            ->orderByDesc('issued_at')
            ->limit(30)
            ->get();

        $arrears = $this->arrearsService->forChildren($scopedChildren, $schoolId, null, $academicYearId);

        return response()->json([
            'children' => $children->map(fn (Student $student): array => [
                'id' => (int) $student->id,
                'name' => (string) $student->full_name,
                'classroom' => (string) ($student->classroom?->name ?? ''),
            ])->values()->all(),
            'selected_child_id' => $selectedChild?->id,
            'selected_academic_year_id' => $academicYearId,
            'summary' => [
                'payments_total' => (float) (clone $paymentsQuery)->sum('amount'),
                'payments_count' => (int) (clone $paymentsQuery)->count(),
                'total_due' => (float) ($arrears['total_due'] ?? 0),
                'total_overdue' => (float) ($arrears['total_overdue'] ?? 0),
                'last_payment_at' => optional((clone $paymentsQuery)->latest('paid_at')->first()?->paid_at)->toIso8601String(),
            ],
            'balances' => collect($arrears['by_child'] ?? [])->values()->map(function (array $item): array {
                /** @var Student $student */
                $student = $item['student'];

                return [
                    'student' => [
                        'id' => (int) $student->id,
                        'name' => (string) $student->full_name,
                        'classroom' => (string) ($student->classroom?->name ?? ''),
                    ],
                    'monthly_due' => (float) ($item['monthly_due'] ?? 0),
                    'unpaid_count' => (int) ($item['unpaid_count'] ?? 0),
                    'overdue_count' => (int) ($item['overdue_count'] ?? 0),
                    'unpaid_total' => (float) ($item['unpaid_total'] ?? 0),
                    'overdue_total' => (float) ($item['overdue_total'] ?? 0),
                    'unpaid_months' => collect($item['unpaid_months'] ?? [])->map(fn (array $month): array => [
                        'key' => (string) ($month['key'] ?? ''),
                        'label' => (string) ($month['label'] ?? ''),
                        'is_overdue' => (bool) ($month['is_overdue'] ?? false),
                    ])->values()->all(),
                ];
            })->all(),
            'payments' => $payments->map(fn (Payment $payment): array => [
                'id' => (int) $payment->id,
                'amount' => (float) $payment->amount,
                'method' => (string) ($payment->method ?? ''),
                'note' => (string) ($payment->note ?? ''),
                'period_month' => optional($payment->period_month)->format('Y-m-d'),
                'paid_at' => optional($payment->paid_at)->toIso8601String(),
                'student' => [
                    'id' => (int) ($payment->student?->id ?? 0),
                    'name' => (string) ($payment->student?->full_name ?? ''),
                    'classroom' => (string) ($payment->student?->classroom?->name ?? ''),
                ],
                'receipt' => $payment->receipt ? [
                    'id' => (int) $payment->receipt->id,
                    'receipt_number' => (string) ($payment->receipt->receipt_number ?? ''),
                ] : null,
            ])->values()->all(),
            'receipts' => $receipts->map(function (Receipt $receipt): array {
                $payments = $receipt->payments->values();

                return [
                    'id' => (int) $receipt->id,
                    'receipt_number' => (string) ($receipt->receipt_number ?? ''),
                    'method' => (string) ($receipt->method ?? ''),
                    'total_amount' => (float) ($receipt->total_amount ?: $payments->sum('amount')),
                    'issued_at' => optional($receipt->issued_at)->toIso8601String(),
                    'note' => (string) ($receipt->note ?? ''),
                    'download_path' => route('api.finance.receipts.download', ['receipt' => $receipt->id], false),
                    'payments' => $payments->map(fn (Payment $payment): array => [
                        'id' => (int) $payment->id,
                        'amount' => (float) $payment->amount,
                        'method' => (string) ($payment->method ?? ''),
                        'period_month' => optional($payment->period_month)->format('Y-m-d'),
                        'paid_at' => optional($payment->paid_at)->toIso8601String(),
                        'student' => [
                            'id' => (int) ($payment->student?->id ?? 0),
                            'name' => (string) ($payment->student?->full_name ?? ''),
                            'classroom' => (string) ($payment->student?->classroom?->name ?? ''),
                        ],
                    ])->values()->all(),
                ];
            })->values()->all(),
        ]);
    }

    public function downloadReceipt(Request $request, Receipt $receipt)
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless((string) $user->role === User::ROLE_PARENT, 403);

        $receipt = $this->resolveOwnedReceipt($receipt);

        return response(
            view('parent.finance.receipt-export', [
                'receipt' => $receipt,
                'autoPrint' => false,
            ])->render(),
            200,
            [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $this->downloadName($receipt) . '"',
            ]
        );
    }

    private function resolveSelectedChild($children, int $childId): ?Student
    {
        return $childId > 0 ? $children->firstWhere('id', $childId) : null;
    }

    private function resolveOwnedReceipt(Receipt $receipt): Receipt
    {
        $schoolId = $this->schoolIdOrFail();
        $childIds = $this->ownedChildren()->pluck('id');

        abort_unless((int) $receipt->school_id === $schoolId, 404);

        $receipt->load([
            'school',
            'parent',
            'receivedBy',
            'payments' => fn ($query) => $query
                ->where('school_id', $schoolId)
                ->whereIn('student_id', $childIds)
                ->with(['student.parentUser', 'student.classroom']),
        ]);

        abort_unless($receipt->payments->isNotEmpty(), 404);

        $receipt->setRelation('payments', $receipt->payments->values());
        $receipt->total_amount = $receipt->payments->sum('amount');

        return $receipt;
    }

    private function downloadName(Receipt $receipt): string
    {
        $number = trim((string) $receipt->receipt_number);
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $number !== '' ? $number : 'receipt-' . $receipt->id);
        $safe = trim((string) $safe, '._');

        return ($safe !== '' ? $safe : 'receipt-' . $receipt->id) . '.html';
    }
}
