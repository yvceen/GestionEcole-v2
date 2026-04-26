<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Admin\AppointmentController as BaseAppointmentController;

class AppointmentController extends BaseAppointmentController
{
    protected function routePrefix(): string
    {
        return 'director.appointments';
    }

    protected function layoutComponent(): string
    {
        return 'director-layout';
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
