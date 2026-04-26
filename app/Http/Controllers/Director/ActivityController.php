<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Admin\ActivityController as BaseActivityController;

class ActivityController extends BaseActivityController
{
    protected function routePrefix(): string
    {
        return 'director.activities';
    }

    protected function layoutComponent(): string
    {
        return 'director-layout';
    }
}
