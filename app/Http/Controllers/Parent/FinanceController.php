<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\Receipt;
use App\Models\Student;
use App\Services\FinanceArrearsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    use InteractsWithParentPortal;

    public function __construct(
        private readonly FinanceArrearsService $arrearsService,
    ) {
    }

    public function index(Request $request)
    {
        $children = $this->ownedChildren(['classroom.level']);
        $childId = (int) $request->integer('child_id');
        $student = $childId > 0 ? $children->firstWhere('id', $childId) : null;
        $schoolId = $this->schoolIdOrFail();
        $childIds = $student ? collect([$student->id]) : $children->pluck('id');

        $paymentsQuery = $this->ownedPaymentsQuery()
            ->when($student, fn (Builder $query) => $query->where('student_id', $student->id));

        $receipts = $this->ownedReceiptsQuery()
            ->with([
                'parent:id,name,email,phone',
                'payments' => fn ($query) => $query
                    ->where('school_id', $schoolId)
                    ->whereIn('student_id', $childIds)
                    ->with(['student.classroom'])
                    ->orderBy('period_month'),
            ])
            ->whereHas('payments', fn ($query) => $query
                ->where('school_id', $schoolId)
                ->whereIn('student_id', $childIds))
            ->orderByDesc('issued_at')
            ->paginate(10)
            ->withQueryString();

        $arrears = $this->arrearsService->forChildren(
            $student ? $children->where('id', $student->id)->values() : $children,
            $schoolId
        );

        return view('parent.finance.index', [
            'children' => $children,
            'childId' => $student?->id,
            'paymentsTotal' => (float) (clone $paymentsQuery)->sum('amount'),
            'paymentsCount' => (int) (clone $paymentsQuery)->count(),
            'lastPayment' => (clone $paymentsQuery)->latest('paid_at')->first(),
            'receipts' => $receipts,
            'arrears' => $arrears,
        ]);
    }

    public function childFinance(Request $request, Student $student)
    {
        $student = $this->resolveOwnedStudent($student);
        $request->merge(['child_id' => $student->id]);

        return $this->index($request);
    }

    public function showReceipt(Receipt $receipt)
    {
        $receipt = $this->resolveOwnedReceipt($receipt);

        return view('parent.finance.receipt', compact('receipt'));
    }

    public function exportReceipt(Receipt $receipt)
    {
        $receipt = $this->resolveOwnedReceipt($receipt);

        return view('parent.finance.receipt-export', [
            'receipt' => $receipt,
            'autoPrint' => request()->boolean('print', true),
        ]);
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
}
