<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Admin\TimetableSettingsController as BaseTimetableSettingsController;

class TimetableSettingsController extends BaseTimetableSettingsController
{
    protected function routePrefix(): string
    {
        return 'school-life.timetable';
    }

    protected function layoutComponent(): string
    {
        return 'school-life-layout';
    }
}
