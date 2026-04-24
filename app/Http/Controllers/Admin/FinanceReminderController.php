<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceReminderController extends Controller
{
    public function __construct(
        private readonly PaymentReminderService $reminders,
    ) {
    }

    public function edit(): View
    {
        $schoolId = $this->schoolId();
        $preview = $this->reminders->previewForSchool($schoolId);

        return view('admin.finance.reminders', $preview);
    }

    public function update(Request $request): RedirectResponse
    {
        $schoolId = $this->schoolId();
        $setting = $this->reminders->settingForSchool($schoolId);

        $data = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'reminder_day' => ['nullable', 'integer', 'between:1,31'],
            'message_template' => ['nullable', 'string', 'max:5000'],
        ]);

        $setting->fill([
            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
            'reminder_day' => isset($data['reminder_day']) ? (int) $data['reminder_day'] : null,
            'message_template' => $data['message_template'] ?? null,
        ]);
        $setting->save();

        return redirect()
            ->route('admin.finance.reminders.edit')
            ->with('success', 'Les reglages de rappel ont ete mis a jour.');
    }

    public function sendNow(): RedirectResponse
    {
        $schoolId = $this->schoolId();
        $sent = $this->reminders->sendForSchool($schoolId);

        return redirect()
            ->route('admin.finance.reminders.edit')
            ->with('success', $sent > 0
                ? "Rappels envoyes a {$sent} destinataire(s)."
                : 'Aucun rappel a envoyer pour les impayes actuels.');
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if ($schoolId <= 0) {
            abort(403, 'School context missing.');
        }

        return $schoolId;
    }
}
