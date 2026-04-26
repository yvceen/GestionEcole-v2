<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Admin\HomeworkController as BaseHomeworkController;

class HomeworkController extends BaseHomeworkController
{
    protected function routePrefix(): string
    {
        return 'director.homeworks';
    }

    protected function viewPrefix(): string
    {
        return 'admin.homeworks';
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function portalTitle(): string
    {
        return 'Direction';
    }
}
