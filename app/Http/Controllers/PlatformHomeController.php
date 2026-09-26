<?php

namespace App\Http\Controllers;

use App\Models\School;
use Inertia\Inertia;
use Inertia\Response;

class PlatformHomeController extends Controller
{
    public function __invoke(): Response
    {
        $schools = School::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
                'county',
                'type',
                'logo_path',
            ]);

        return Inertia::render('Platform/Home', [
            'schools' => $schools,
            'stats' => [
                'schools' => $schools->count(),
                'modules' => 10,
                'roles' => 11,
            ],
        ]);
    }
}