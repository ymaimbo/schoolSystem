<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $school = null;

        // 1) Try subdomain-based resolution: <slug>.yourdomain.com
        $host = (string) $request->getHost();
        $host = explode(':', $host)[0] ?? $host; // strip port if present
        $host = trim(strtolower($host));

        if ($host !== '' && ! in_array($host, ['localhost', '127.0.0.1'], true)) {
            $parts = array_values(array_filter(explode('.', $host)));

            // only resolve when we truly have a subdomain
            // e.g. school.example.com => ['school', 'example', 'com']
            if (count($parts) >= 3) {
                $slug = $parts[0];
                if (! empty($slug)) {
                    $school = School::query()->where('slug', $slug)->first();
                }
            }
        }

        // 2) Try session school selection
        if (! $school && $request->session()->has('school_id')) {
            $school = School::query()->find($request->session()->get('school_id'));
        }

        // 3) Try authenticated user's school
        $user = $request->user();
        if (! $school && $user && ! empty($user->school_id)) {
            $school = School::query()->find($user->school_id);
        }

        // 4) Safe fallback: first school
        if (! $school) {
            $school = School::query()->orderBy('id')->first();
        }

        if (! $school) {
            abort(403, 'No school context found.');
        }

        app()->instance('currentSchool', $school);
        $request->session()->put('school_id', $school->id);

        return $next($request);
    }
}