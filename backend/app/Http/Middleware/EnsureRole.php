<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SR-04: role based access control, enforced on the server.
 * SR-03: a request without a valid token never reaches here.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! in_array($user->role->value, $roles, true)) {
            return response()->json([
                'message' => 'This account may not perform that action.',
            ], 403);
        }

        return $next($request);
    }
}
