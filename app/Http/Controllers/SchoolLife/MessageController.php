<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Admin\MessageController as BaseMessageController;

class MessageController extends BaseMessageController
{
    protected function routePrefix(): string
    {
        return 'school-life.messages';
    }

    protected function layoutComponent(): string
    {
        return 'school-life-layout';
    }
}
