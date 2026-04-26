<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Admin\AppointmentController as BaseAppointmentController;

class AppointmentController extends BaseAppointmentController
{
    protected function routePrefix(): string
    {
        return 'school-life.appointments';
    }

    protected function layoutComponent(): string
    {
        return 'school-life-layout';
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function canEdit(): bool
    {
        return false;
    }

    protected function canDelete(): bool
    {
        return false;
    }
}
