<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Admin\NewsController as BaseNewsController;

class NewsController extends BaseNewsController
{
    protected function routePrefix(): string
    {
        return 'director.news';
    }

    protected function layoutComponent(): string
    {
        return 'director-layout';
    }
}
