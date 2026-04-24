<?php

namespace App\Services;

use App\Models\SchoolCalendarEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SchoolCalendarService
{
    public function buildIndexData(int $schoolId, Request $request, int $perPage = 12): array
    {
        $month = $this->resolveMonth((string) $request->get('month', now()->format('Y-m')));
        $type = trim((string) $request->get('type', ''));

        $query = SchoolCalendarEvent::query()
            ->where('school_id', $schoolId)
            ->with('creator:id,name')
            ->whereDate('starts_on', '<=', $month->copy()->endOfMonth()->toDateString())
            ->where(function ($builder) use ($month) {
                $builder->whereNull('ends_on')
                    ->whereDate('starts_on', '>=', $month->copy()->startOfMonth()->toDateString())
                    ->orWhere(function ($nested) use ($month) {
                        $nested->whereNotNull('ends_on')
                            ->whereDate('ends_on', '>=', $month->copy()->startOfMonth()->toDateString());
                    });
            })
            ->when(
                $type !== '' && in_array($type, SchoolCalendarEvent::types(), true),
                fn ($builder) => $builder->where('type', $type)
            )
            ->orderBy('starts_on')
            ->orderBy('title');

        $events = (clone $query)->paginate($perPage)->withQueryString();
        $summarySource = (clone $query)->get();

        return [
            'events' => $events,
            'month' => $month,
            'type' => $type,
            'types' => SchoolCalendarEvent::types(),
            'summary' => [
                'total' => $summarySource->count(),
                'exam' => $summarySource->where('type', SchoolCalendarEvent::TYPE_EXAM)->count(),
                'holiday' => $summarySource->where('type', SchoolCalendarEvent::TYPE_HOLIDAY)->count(),
                'event' => $summarySource->where('type', SchoolCalendarEvent::TYPE_EVENT)->count(),
            ],
            'upcoming' => SchoolCalendarEvent::query()
                ->where('school_id', $schoolId)
                ->whereDate('starts_on', '>=', now()->toDateString())
                ->orderBy('starts_on')
                ->limit(5)
                ->get(),
        ];
    }

    private function resolveMonth(string $value): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}
