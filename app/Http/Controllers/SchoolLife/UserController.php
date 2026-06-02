<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Admin\UserController as BaseUserController;
use App\Models\User;

class UserController extends BaseUserController
{
    protected function allowedRoles(): array
    {
        return [
            User::ROLE_DIRECTOR,
            User::ROLE_TEACHER,
            User::ROLE_PARENT,
            User::ROLE_STUDENT,
            User::ROLE_CHAUFFEUR,
            User::ROLE_SCHOOL_LIFE,
        ];
    }

    protected function routePrefix(): string
    {
        return 'school-life.users';
    }

    protected function layoutComponent(): string
    {
        return 'school-life-layout';
    }

    protected function viewPrefix(): string
    {
        return 'school-life.users';
    }
}
