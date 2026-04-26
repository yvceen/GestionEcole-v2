<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Admin\SubjectController as BaseSubjectController;

class SubjectController extends BaseSubjectController
{
    protected function routePrefix(): string
    {
        return 'director.subjects';
    }

    protected function layoutComponent(): string
    {
        return 'director-layout';
    }
}
