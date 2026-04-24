<?php

namespace App\Services;

use App\Models\PaymentReminderSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentReminderService
{
    public function __construct(
        private readonly FinanceArrearsService $arrearsService,
        private readonly NotificationService $notifications,
    ) {
    }

    public function settingForSchool(int $schoolId): PaymentReminderSetting
    {
        return PaymentReminderSetting::query()->firstOrCreate(
            ['school_id' => $schoolId],
            [
                'is_enabled' => false,
                'reminder_day' => 28,
            ],
        );
    }

    public function previewForSchool(int $schoolId, ?PaymentReminderSetting $setting = null, ?Carbon $today = null): array
    {
        $today ??= now();
        $setting ??= $this->settingForSchool($schoolId);
        $school = School::query()->find($schoolId);

        $parents = User::query()
            ->where('school_id', $schoolId)
            ->where('role', User::ROLE_PARENT)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $children = Student::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('parent_user_id')
            ->with([
                'feePlan',
                'classroom.fee',
                'parentUser:id,name,email',
                'parentFee',
            ])
            ->get();

        $groupedChildren = $children->groupBy('parent_user_id');

        $recipients = $parents
            ->map(function (User $parent) use ($groupedChildren, $schoolId, $today, $setting, $school) {
                $ownedChildren = $groupedChildren->get($parent->id, collect());
                if ($ownedChildren->isEmpty()) {
                    return null;
                }

                $arrears = $this->arrearsService->forChildren($ownedChildren, $schoolId, $today);
                $totalDue = (float) ($arrears['total_due'] ?? 0);
                $overdueTotal = (float) ($arrears['total_overdue'] ?? 0);

                if ($totalDue <= 0 && $overdueTotal <= 0) {
                    return null;
                }

                $message = $this->renderMessage(
                    template: (string) ($setting->message_template ?? ''),
                    parent: $parent,
                    schoolName: (string) ($school?->name ?? 'MyEdu'),
                    totalDue: $totalDue,
                    overdueTotal: $overdueTotal,
                    unpaidMonths: (int) ($arrears['total_unpaid_months'] ?? 0),
                    overdueMonths: (int) ($arrears['total_overdue_months'] ?? 0),
                );

                return [
                    'parent_id' => (int) $parent->id,
                    'parent_name' => (string) $parent->name,
                    'parent_email' => (string) ($parent->email ?? ''),
                    'children' => $ownedChildren->pluck('full_name')->filter()->values()->all(),
                    'total_due' => $totalDue,
                    'overdue_total' => $overdueTotal,
                    'unpaid_months' => (int) ($arrears['total_unpaid_months'] ?? 0),
                    'overdue_months' => (int) ($arrears['total_overdue_months'] ?? 0),
                    'message_preview' => $message,
                ];
            })
            ->filter()
            ->values();

        return [
            'setting' => $setting,
            'school' => $school,
            'recipients' => $recipients,
            'summary' => [
                'count' => $recipients->count(),
                'total_due' => (float) $recipients->sum('total_due'),
                'total_overdue' => (float) $recipients->sum('overdue_total'),
                'total_unpaid_months' => (int) $recipients->sum('unpaid_months'),
                'total_overdue_months' => (int) $recipients->sum('overdue_months'),
            ],
        ];
    }

    public function sendForSchool(int $schoolId, bool $markAsScheduled = false, ?Carbon $today = null): int
    {
        $today ??= now();
        $setting = $this->settingForSchool($schoolId);
        $preview = $this->previewForSchool($schoolId, $setting, $today);
        $schoolName = (string) ($preview['school']?->name ?? 'MyEdu');

        $sent = 0;
        foreach ($preview['recipients'] as $recipient) {
            $sent += $this->notifications->notifyUsers(
                [(int) $recipient['parent_id']],
                'finance_reminder',
                'Rappel de paiement',
                (string) $recipient['message_preview'],
                [
                    'school_id' => $schoolId,
                    'type' => 'finance_reminder',
                    'school_name' => $schoolName,
                    'total_due' => (float) $recipient['total_due'],
                    'overdue_total' => (float) $recipient['overdue_total'],
                ],
            );
        }

        if ($markAsScheduled || $sent > 0) {
            $setting->forceFill(['last_sent_at' => $today])->save();
        }

        return $sent;
    }

    public function sendDueScheduledReminders(?Carbon $today = null): int
    {
        $today ??= now();

        $settings = PaymentReminderSetting::query()
            ->where('is_enabled', true)
            ->where('reminder_day', (int) $today->day)
            ->get();

        $sent = 0;
        foreach ($settings as $setting) {
            if ($setting->last_sent_at?->isSameDay($today)) {
                continue;
            }

            $sent += $this->sendForSchool((int) $setting->school_id, true, $today);
        }

        return $sent;
    }

    public function defaultTemplate(): string
    {
        return 'Bonjour {parent_name}, ceci est un rappel de {school_name}. '
            . 'Des frais restent a regler pour votre famille. '
            . 'Montant estime: {total_due} MAD. '
            . 'En retard: {overdue_total} MAD. Merci de contacter l etablissement si besoin.';
    }

    private function renderMessage(
        string $template,
        User $parent,
        string $schoolName,
        float $totalDue,
        float $overdueTotal,
        int $unpaidMonths,
        int $overdueMonths,
    ): string {
        $body = trim($template) !== '' ? $template : $this->defaultTemplate();

        return strtr($body, [
            '{parent_name}' => (string) $parent->name,
            '{school_name}' => $schoolName,
            '{total_due}' => number_format($totalDue, 2, '.', ''),
            '{overdue_total}' => number_format($overdueTotal, 2, '.', ''),
            '{unpaid_months}' => (string) $unpaidMonths,
            '{overdue_months}' => (string) $overdueMonths,
        ]);
    }
}
