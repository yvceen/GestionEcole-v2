<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Admin\TimetableSettingsController as BaseTimetableSettingsController;

class TimetableSettingsController extends BaseTimetableSettingsController
{
    protected function routePrefix(): string
    {
        return 'director.timetable';
    }

    protected function layoutComponent(): string
    {
        return 'director-layout';
    }
}
