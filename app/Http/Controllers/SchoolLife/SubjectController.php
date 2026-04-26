<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Admin\SubjectController as BaseSubjectController;

class SubjectController extends BaseSubjectController
{
    protected function routePrefix(): string
    {
        return 'school-life.subjects';
    }

    protected function layoutComponent(): string
    {
        return 'school-life-layout';
    }
}
