<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Admin\NewsController as BaseNewsController;

class NewsController extends BaseNewsController
{
    protected function routePrefix(): string
    {
        return 'school-life.news';
    }

    protected function layoutComponent(): string
    {
        return 'school-life-layout';
    }
}
