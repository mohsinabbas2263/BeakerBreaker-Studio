<?php

use Illuminate\Support\Facades\Route;

// The web root carries no user interface of its own. The student and teacher
// portal is the Next.js application; the administrator panel is Filament at /admin.
Route::get('/', fn () => response()->json([
    'service' => 'Beaker Breakers Studio API',
    'admin' => url('/admin'),
    'health' => url('/up'),
]));
