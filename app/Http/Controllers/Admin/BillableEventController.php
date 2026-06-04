<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillableEvent;
use App\Models\BillableEventPayment;
use App\Models\BillableEventStudent;
use App\Models\Classroom;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BillableEventController extends Controller
{
    protected function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }

    protected function routePrefix(): string
    {
        return 'admin.finance.events';
    }

    protected function layoutComponent(): string
    {
        return 'admin-layout';
    }

    protected function viewPrefix(): string
    {
        return 'admin.finance.events';
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $status = (string) $request->get('status', '');
        $q = trim((string) $request->get('q', ''));

        $events = BillableEvent::query()
            ->where('school_id', $schoolId)
            ->withCount('targets')
            ->withSum('targets as expected_total', 'amount_due')
            ->withSum('payments as paid_total', 'amount')
            ->when(in_array($status, [BillableEvent::STATUS_ACTIVE, BillableEvent::STATUS_CLOSED], true), fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view($this->viewPrefix() . '.index', $this->viewData(compact('events', 'status', 'q')));
    }

    public function create()
    {
        return view($this->viewPrefix() . '.form', $this->viewData([
            'event' => null,
            'classrooms' => $this->classrooms(),
            'students' => $this->students(),
            'action' => route($this->routePrefix() . '.store'),
            'method' => 'POST',
            'submitLabel' => 'Creer evenement',
        ]));
    }

    public function store(Request $request)
    {
        $schoolId = $this->schoolId();
        $data = $this->validatedEventData($request, $schoolId);

        $event = DB::transaction(function () use ($data, $schoolId) {
            $event = BillableEvent::create([
                'school_id' => $schoolId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'event_date' => $data['event_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'amount_per_student' => $data['amount_per_student'],
                'status' => $data['status'],
                'created_by_user_id' => auth()->id(),
            ]);

            $this->syncTargets($event, $data, $schoolId);

            return $event;
        });

        return redirect()->route($this->routePrefix() . '.show', $event)->with('success', 'Evenement cree.');
    }

    public function show(Request $request, BillableEvent $event)
    {
        $this->authorizeEvent($event);
        $status = (string) $request->get('payment_status', '');
        $classroomId = (int) $request->integer('classroom_id');
        $q = trim((string) $request->get('q', ''));

        $event->loadSum('targets as expected_total', 'amount_due')
            ->loadSum('payments as paid_total', 'amount')
            ->loadCount('targets');

        $paidSubquery = BillableEventPayment::query()
            ->selectRaw('COALESCE(SUM(amount), 0)')
            ->whereColumn('billable_event_payments.student_id', 'billable_event_students.student_id')
            ->whereColumn('billable_event_payments.billable_event_id', 'billable_event_students.billable_event_id');

        $targets = BillableEventStudent::query()
            ->where('billable_event_id', $event->id)
            ->where('school_id', $event->school_id)
            ->with(['student.classroom', 'student.parentUser'])
            ->select('billable_event_students.*')
            ->selectSub($paidSubquery, 'paid_amount')
            ->when($classroomId > 0, fn ($query) => $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('classroom_id', $classroomId)))
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('student', function ($studentQuery) use ($q) {
                    $studentQuery->where('full_name', 'like', "%{$q}%")
                        ->orWhereHas('parentUser', function ($parentQuery) use ($q) {
                            $parentQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%");
                        });
                });
            })
            ->get()
            ->filter(function (BillableEventStudent $target) use ($status) {
                $paid = (float) ($target->paid_amount ?? 0);
                $due = (float) $target->amount_due;

                return match ($status) {
                    'paid' => $paid >= $due && $due > 0,
                    'partial' => $paid > 0 && $paid < $due,
                    'unpaid' => $paid <= 0 && $due > 0,
                    default => true,
                };
            })
            ->values();

        $payments = BillableEventPayment::query()
            ->where('billable_event_id', $event->id)
            ->where('school_id', $event->school_id)
            ->with(['student.classroom', 'parent', 'receivedBy'])
            ->orderByDesc('paid_at')
            ->limit(12)
            ->get();

        return view($this->viewPrefix() . '.show', $this->viewData([
            'event' => $event,
            'targets' => $targets,
            'payments' => $payments,
            'classrooms' => $this->classrooms(),
            'paymentStatus' => $status,
            'classroomId' => $classroomId,
            'q' => $q,
        ]));
    }

    public function edit(BillableEvent $event)
    {
        $this->authorizeEvent($event);

        $event->load('targets');

        return view($this->viewPrefix() . '.form', $this->viewData([
            'event' => $event,
            'classrooms' => $this->classrooms(),
            'students' => $this->students(),
            'action' => route($this->routePrefix() . '.update', $event),
            'method' => 'PUT',
            'submitLabel' => 'Enregistrer',
        ]));
    }

    public function update(Request $request, BillableEvent $event)
    {
        $this->authorizeEvent($event);
        $data = $this->validatedEventData($request, (int) $event->school_id);

        DB::transaction(function () use ($event, $data): void {
            $event->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'event_date' => $data['event_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'amount_per_student' => $data['amount_per_student'],
                'status' => $data['status'],
            ]);

            $this->syncTargets($event, $data, (int) $event->school_id);
        });

        return redirect()->route($this->routePrefix() . '.show', $event)->with('success', 'Evenement modifie.');
    }

    public function destroy(BillableEvent $event)
    {
        $this->authorizeEvent($event);
        $event->delete();

        return redirect()->route($this->routePrefix() . '.index')->with('success', 'Evenement supprime.');
    }

    public function storePayment(Request $request, BillableEvent $event)
    {
        $this->authorizeEvent($event);

        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')->where(fn ($query) => $query->where('school_id', $event->school_id))],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999'],
            'method' => ['required', 'in:cash,transfer,card,check'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $target = BillableEventStudent::query()
            ->where('billable_event_id', $event->id)
            ->where('student_id', $data['student_id'])
            ->where('school_id', $event->school_id)
            ->with('student.parentUser')
            ->firstOrFail();

        $payment = DB::transaction(function () use ($event, $target, $data) {
            $paidTotal = (float) BillableEventPayment::query()
                ->where('billable_event_id', $event->id)
                ->where('student_id', $target->student_id)
                ->lockForUpdate()
                ->sum('amount');
            $remaining = max(0, (float) $target->amount_due - $paidTotal);
            $amount = (float) $data['amount'];

            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Ce paiement est déjà soldé.',
                ]);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Le montant dépasse le reste à payer de ' . number_format($remaining, 2) . ' MAD.',
                ]);
            }

            $paidAt = !empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : now();
            $receiptNumber = $this->nextReceiptNumber((int) $event->school_id, $paidAt);

            return BillableEventPayment::create([
                'school_id' => $event->school_id,
                'billable_event_id' => $event->id,
                'student_id' => $target->student_id,
                'parent_user_id' => $target->student?->parent_user_id,
                'receipt_number' => $receiptNumber,
                'amount' => $amount,
                'method' => $data['method'],
                'paid_at' => $paidAt,
                'received_by_user_id' => auth()->id(),
                'note' => $data['note'] ?? null,
            ]);
        });

        return redirect()->route($this->routePrefix() . '.payments.receipt', $payment)->with('success', 'Paiement enregistre.');
    }

    public function receipt(BillableEventPayment $payment)
    {
        abort_unless((int) $payment->school_id === $this->schoolId(), 404);

        $payment->load(['event.school', 'student.classroom', 'parent', 'receivedBy']);

        return view($this->viewPrefix() . '.receipt', $this->viewData([
            'payment' => $payment,
        ]));
    }

    protected function viewData(array $data = []): array
    {
        return $data + [
            'routePrefix' => $this->routePrefix(),
            'layoutComponent' => $this->layoutComponent(),
        ];
    }

    protected function authorizeEvent(BillableEvent $event): void
    {
        abort_unless((int) $event->school_id === $this->schoolId(), 404);
    }

    private function validatedEventData(Request $request, int $schoolId): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'event_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'amount_per_student' => ['required', 'numeric', 'min:0', 'max:999999'],
            'status' => ['required', 'in:' . BillableEvent::STATUS_ACTIVE . ',' . BillableEvent::STATUS_CLOSED],
            'target_scope' => ['required', 'in:school,classrooms,students'],
            'classroom_ids' => ['nullable', 'array'],
            'classroom_ids.*' => ['integer', Rule::exists('classrooms', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
        ]);
    }

    private function syncTargets(BillableEvent $event, array $data, int $schoolId): void
    {
        $studentQuery = Student::query()->where('school_id', $schoolId)->active();

        if ($data['target_scope'] === 'classrooms') {
            $studentQuery->whereIn('classroom_id', array_map('intval', $data['classroom_ids'] ?? []));
        }

        if ($data['target_scope'] === 'students') {
            $studentQuery->whereIn('id', array_map('intval', $data['student_ids'] ?? []));
        }

        $studentIds = $studentQuery->pluck('id')->map(fn ($id) => (int) $id)->all();
        $amount = (float) $data['amount_per_student'];
        $paidStudentIds = BillableEventPayment::query()
            ->where('billable_event_id', $event->id)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        BillableEventStudent::query()
            ->where('billable_event_id', $event->id)
            ->whereNotIn('student_id', $studentIds)
            ->whereNotIn('student_id', $paidStudentIds)
            ->delete();

        foreach ($studentIds as $studentId) {
            BillableEventStudent::updateOrCreate(
                [
                    'billable_event_id' => $event->id,
                    'student_id' => $studentId,
                ],
                [
                    'school_id' => $schoolId,
                    'amount_due' => $amount,
                ]
            );
        }
    }

    private function nextReceiptNumber(int $schoolId, Carbon $paidAt): string
    {
        $year = $paidAt->format('Y');

        $last = BillableEventPayment::query()
            ->where('school_id', $schoolId)
            ->whereYear('paid_at', $year)
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

        return sprintf('EV-%s-%06d', $year, $nextSeq);
    }

    private function classrooms()
    {
        return Classroom::query()
            ->where('school_id', $this->schoolId())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function students()
    {
        return Student::query()
            ->where('school_id', $this->schoolId())
            ->active()
            ->with(['classroom:id,name', 'parentUser:id,name,phone,email'])
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'classroom_id', 'parent_user_id']);
    }
}
