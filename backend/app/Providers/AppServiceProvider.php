<?php

namespace App\Providers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // SR-01: salted hash from an adaptive function, work factor not below 12.
        Hash::setRounds(max(12, (int) config('hashing.bcrypt.rounds', 12)));
    }
}
