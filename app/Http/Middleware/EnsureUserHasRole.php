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

        $normalize = static fn ($value) => str_replace([' ', '-'], '_', strtolower(trim((string) $value)));

        $userRole = $normalize($user->role ?? '');
        $allowedRoles = array_map($normalize, $roles);

        // Super admin can access all protected routes.
        if ($userRole === 'super_admin') {
            return $next($request);
        }

        if (! in_array($userRole, $allowedRoles, true)) {
            abort(403, 'You do not have the required role.');
        }

        // Enforce school scope for non-global users when school context is present.
        if (app()->bound('currentSchool')) {
            $currentSchool = app('currentSchool');

            if ((int) $user->school_id !== (int) $currentSchool->id) {
                abort(403, 'You are not allowed to access this school context.');
            }
        }

        return $next($request);
    }
}