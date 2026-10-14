<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Administrator = 'administrator';

    /** FR-01.1: each role lands on its own dashboard. */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Student => '/student',
            self::Teacher => '/teacher',
            self::Administrator => '/admin',
        };
    }
}
