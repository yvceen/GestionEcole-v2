<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Admin\BillableEventController as BaseBillableEventController;

class BillableEventController extends BaseBillableEventController
{
    protected function routePrefix(): string
    {
        return 'school-life.finance.events';
    }

    protected function layoutComponent(): string
    {
        return 'school-life-layout';
    }
}
