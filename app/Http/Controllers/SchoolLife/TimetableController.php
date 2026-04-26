<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Admin\TimetableController as BaseTimetableController;

class TimetableController extends BaseTimetableController
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
