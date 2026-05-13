<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SchoolPageController extends Controller
{
    public function __invoke(): Response
    {
        $school = School::query()
            ->with([
                'programs' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'staffMembers' => fn ($query) => $query
                    ->where('is_leadership', true)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->where('code', '2109104')
            ->first();

        if (! $school) {
            $school = School::query()
                ->with([
                    'programs' => fn ($query) => $query
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                    'staffMembers' => fn ($query) => $query
                        ->where('is_leadership', true)
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                ])
                ->first();
        }

        $schoolPayload = $school
            ? [
                'id' => $school->id,
                'name' => $school->name,
                'slug' => $school->slug,
                'location' => $school->location,
                'county' => $school->county,
                'type' => $school->type,
                'status' => $school->status,
                'note' => $school->note,
                'code' => $school->code,
                'logo_url' => $this->assetUrl($school->logo_path),
                'hero_image_url' => $this->assetUrl($school->hero_image_path),
            ]
            : null;

        $programsPayload = $school
            ? $school->programs->map(fn ($program) => [
                'id' => $program->id,
                'title' => $program->title,
                'slug' => $program->slug,
                'summary' => $program->summary,
                'details' => $program->details,
                'image_url' => $this->assetUrl($program->image_path),
            ])->values()
            : collect();

        $leadershipPayload = $school
            ? $school->staffMembers->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->role,
                'department' => $member->department,
                'bio' => $member->bio,
            ])->values()
            : collect();

        return Inertia::render('Vigurungani', [
            'school' => $schoolPayload,
            'programs' => $programsPayload,
            'leadershipTeam' => $leadershipPayload,
        ]);
    }

    private function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::url($path);
    }
}