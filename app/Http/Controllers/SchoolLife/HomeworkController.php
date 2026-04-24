<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Admin\HomeworkController as BaseHomeworkController;

class HomeworkController extends BaseHomeworkController
{
    protected function routePrefix(): string
    {
        return 'school-life.homeworks';
    }

    protected function viewPrefix(): string
    {
        return 'school-life.homeworks';
    }

    protected function canCreate(): bool
    {
        return false;
    }
}
