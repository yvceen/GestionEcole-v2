<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Admin\TimetableController as BaseTimetableController;

class TimetableController extends BaseTimetableController
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
