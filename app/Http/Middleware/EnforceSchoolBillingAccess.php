<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSchoolBillingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($this->normalizeRole($user->role ?? null) === 'super_admin') {
            return $next($request);
        }

        $school = app()->bound('currentSchool') ? app('currentSchool') : null;
        if (! $school || ! isset($school->id)) {
            return $next($request);
        }

        if ((bool) ($school->billing_access_override ?? false)) {
            return $next($request);
        }

        if (! empty($school->billing_grace_until) && now()->lt($school->billing_grace_until)) {
            return $next($request);
        }

        $routeName = (string) optional($request->route())->getName();
        $alwaysAllowed = config('billing_access.always_allowed_route_names', []);

        if (in_array($routeName, $alwaysAllowed, true)) {
            return $next($request);
        }

        $stage = (string) ($school->billing_access_stage ?? 'locked');
        $role = $this->normalizeRole($user->role ?? null);

        $allowedRolesByStage = config('billing_access.allowed_roles_by_stage', []);
        $allowedRoles = $allowedRolesByStage[$stage] ?? [];

        if (in_array($role, $allowedRoles, true)) {
            return $next($request);
        }

        $requiredPercent = match ($role) {
            'principal', 'deputy_principal' => 50,
            'accountant' => 0,
            default => 25,
        };

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Access restricted: School billing threshold not met for your role.',
                'stage' => $stage,
                'paid_percent' => (float) ($school->billing_paid_percent ?? 0),
                'required_percent' => $requiredPercent,
                'role' => $role,
            ], 403);
        }

        return redirect()->route('dashboard')->with('error', 'Access restricted: School billing threshold not met for your role.');
    }

    private function normalizeRole(?string $role): string
    {
        return strtolower(trim((string) $role));
    }
}