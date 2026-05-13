<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $normalize = fn ($value) => str_replace([' ', '-'], '_', strtolower(trim((string) $value)));

        $normalizedRoles = array_map($normalize, $roles);
        $role = $normalize($user->role);

        if (! in_array($role, $normalizedRoles, true)) {
            abort(403, 'You are not authorized for this action.');
        }

        return $next($request);
    }
}