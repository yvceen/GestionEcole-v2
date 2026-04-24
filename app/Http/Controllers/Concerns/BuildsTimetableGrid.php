<?php

namespace App\Http\Controllers\Concerns;

use App\Models\TimetableSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

trait BuildsTimetableGrid
{
    private function loadTimetableSetting(int $schoolId): TimetableSetting
    {
        return TimetableSetting::forSchool($schoolId);
    }

    private function buildTimetableGridPayload(Collection $slots, TimetableSetting $settings): array
    {
        $dayStart = Carbon::createFromFormat('H:i:s', $this->normalizeTime($settings->day_start_time));
        $dayEnd = Carbon::createFromFormat('H:i:s', $this->normalizeTime($settings->day_end_time));
        $totalMinutes = max(1, $dayStart->diffInMinutes($dayEnd));

        $times = [];
        $cursor = $dayStart->copy();
        $step = max(1, (int) $settings->slot_minutes);
        while ($cursor->lte($dayEnd)) {
            $times[] = $cursor->format('H:i');
            $cursor->addMinutes($step);
        }
        if (empty($times) || end($times) !== $dayEnd->format('H:i')) {
            $times[] = $dayEnd->format('H:i');
        }

        $palette = [
            ['bg' => 'bg-blue-50', 'border' => '#2563EB'],
            ['bg' => 'bg-teal-50', 'border' => '#14B8A6'],
            ['bg' => 'bg-indigo-50', 'border' => '#4F46E5'],
            ['bg' => 'bg-cyan-50', 'border' => '#0891B2'],
            ['bg' => 'bg-emerald-50', 'border' => '#059669'],
            ['bg' => 'bg-sky-50', 'border' => '#0EA5E9'],
        ];

        $decorated = $slots->map(function ($slot) use ($dayStart, $dayEnd, $totalMinutes, $palette) {
            $start = Carbon::createFromFormat('H:i:s', $this->normalizeTime($slot->start_time));
            $end = Carbon::createFromFormat('H:i:s', $this->normalizeTime($slot->end_time));

            if ($start->lt($dayStart)) {
                $start = $dayStart->copy();
            }
            if ($end->gt($dayEnd)) {
                $end = $dayEnd->copy();
            }
            if ($end->lte($start)) {
                $end = $start->copy()->addMinutes(5);
            }

            $topPct = round(($dayStart->diffInMinutes($start) / $totalMinutes) * 100, 4);
            $heightPct = round((max(5, $start->diffInMinutes($end)) / $totalMinutes) * 100, 4);

            $index = crc32(strtolower((string) $slot->subject)) % count($palette);

            $slot->grid_style = "top: {$topPct}%; height: {$heightPct}%;";
            $slot->grid_bg_class = $palette[$index]['bg'];
            $slot->grid_border_color = $palette[$index]['border'];
            $slot->grid_start_minutes = $dayStart->diffInMinutes($start);
            $slot->grid_end_minutes = $dayStart->diffInMinutes($end);
            $slot->start_label = substr((string) $slot->start_time, 0, 5);
            $slot->end_label = substr((string) $slot->end_time, 0, 5);

            return $slot;
        });

        $lunchBlock = null;
        if ($settings->lunch_start && $settings->lunch_end) {
            $lStart = Carbon::createFromFormat('H:i:s', $this->normalizeTime($settings->lunch_start));
            $lEnd = Carbon::createFromFormat('H:i:s', $this->normalizeTime($settings->lunch_end));
            if ($lStart->gt($dayStart) && $lEnd->lt($dayEnd) && $lEnd->gt($lStart)) {
                $lTop = round(($dayStart->diffInMinutes($lStart) / $totalMinutes) * 100, 4);
                $lHeight = round(($lStart->diffInMinutes($lEnd) / $totalMinutes) * 100, 4);
                $lunchBlock = [
                    'label' => 'Pause dejeuner',
                    'style' => "top: {$lTop}%; height: {$lHeight}%;",
                ];
            }
        }

        return [
            'times' => $times,
            'slotsByDay' => $decorated->groupBy('day'),
            'lunchBlock' => $lunchBlock,
            'totalMinutes' => $totalMinutes,
        ];
    }

    private function normalizeTime(string $value): string
    {
        if (strlen($value) === 5) {
            return $value . ':00';
        }

        return $value;
    }
}
