<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class KwaleCountySeniorSchoolsSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPrincipalPassword = '#Agma1010';

        $schools = [
            [
                'name' => 'Kwale High School',
                'code' => 'KWA-SS-001',
                'location' => 'Kwale Town',
                'county' => 'Kwale',
                'type' => 'boarding',
                'status' => 'active',
                'note' => 'County senior school profile seed data.',
                'logo_path' => '/images/schools/kwale-high-logo.png',
                'hero_image_path' => '/images/schools/kwale-high-hero.jpg',
                'principal' => [
                    'name' => 'Hassan Mwajuma',
                    'email' => 'principal.kwalehigh@schools.kwale.ke',
                ],
            ],
            [
                'name' => 'Kaya Tiwi School',
                'code' => 'KWA-SS-002',
                'location' => 'Tiwi, Matuga',
                'county' => 'Kwale',
                'type' => 'boarding',
                'status' => 'active',
                'note' => 'County senior school profile seed data.',
                'logo_path' => '/images/schools/kaya-tiwi-logo.png',
                'hero_image_path' => '/images/schools/kaya-tiwi-hero.jpg',
                'principal' => [
                    'name' => 'Amina Khamis',
                    'email' => 'principal.kayatiwi@schools.kwale.ke',
                ],
            ],
            [
                'name' => 'Waa Boys High School',
                'code' => 'KWA-SS-003',
                'location' => 'Waa, Matuga',
                'county' => 'Kwale',
                'type' => 'boarding',
                'status' => 'active',
                'note' => 'County senior school profile seed data.',
                'logo_path' => '/images/schools/waa-boys-logo.png',
                'hero_image_path' => '/images/schools/waa-boys-hero.jpg',
                'principal' => [
                    'name' => 'James Charo',
                    'email' => 'principal.waaboys@schools.kwale.ke',
                ],
            ],
            [
                'name' => 'Msambweni High School',
                'code' => 'KWA-SS-004',
                'location' => 'Msambweni',
                'county' => 'Kwale',
                'type' => 'day',
                'status' => 'active',
                'note' => 'County senior school profile seed data.',
                'logo_path' => '/images/schools/msambweni-high-logo.png',
                'hero_image_path' => '/images/schools/msambweni-high-hero.jpg',
                'principal' => [
                    'name' => 'Fatma Binti',
                    'email' => 'principal.msambwenihigh@schools.kwale.ke',
                ],
            ],
            [
                'name' => 'Matuga Girls High School',
                'code' => 'KWA-SS-005',
                'location' => 'Matuga',
                'county' => 'Kwale',
                'type' => 'boarding',
                'status' => 'active',
                'note' => 'County senior school profile seed data.',
                'logo_path' => '/images/schools/matuga-girls-logo.png',
                'hero_image_path' => '/images/schools/matuga-girls-hero.jpg',
                'principal' => [
                    'name' => 'Zainab Salim',
                    'email' => 'principal.matugagirls@schools.kwale.ke',
                ],
            ],
            [
                'name' => 'Kinango Secondary School',
                'code' => 'KWA-SS-006',
                'location' => 'Kinango',
                'county' => 'Kwale',
                'type' => 'day',
                'status' => 'active',
                'note' => 'County senior school profile seed data.',
                'logo_path' => '/images/schools/kinango-sec-logo.png',
                'hero_image_path' => '/images/schools/kinango-sec-hero.jpg',
                'principal' => [
                    'name' => 'Ali Kombo',
                    'email' => 'principal.kinango@schools.kwale.ke',
                ],
            ],
            [
                'name' => 'Mackinnon Road Secondary School',
                'code' => 'KWA-SS-007',
                'location' => 'Mackinnon Road, Kinango',
                'county' => 'Kwale',
                'type' => 'day',
                'status' => 'active',
                'note' => 'County senior school profile seed data.',
                'logo_path' => '/images/schools/mackinnon-road-logo.png',
                'hero_image_path' => '/images/schools/mackinnon-road-hero.jpg',
                'principal' => [
                    'name' => 'Mariam Juma',
                    'email' => 'principal.mackinnonroad@schools.kwale.ke',
                ],
            ],
            [
                'name' => 'Samburu Secondary School',
                'code' => 'KWA-SS-008',
                'location' => 'Samburu, Kinango',
                'county' => 'Kwale',
                'type' => 'day',
                'status' => 'active',
                'note' => 'County senior school profile seed data.',
                'logo_path' => '/images/schools/samburu-sec-logo.png',
                'hero_image_path' => '/images/schools/samburu-sec-hero.jpg',
                'principal' => [
                    'name' => 'Abdallah Said',
                    'email' => 'principal.samburu@schools.kwale.ke',
                ],
            ],
            [
                'name' => 'Lunga Lunga Secondary School',
                'code' => 'KWA-SS-009',
                'location' => 'Lunga Lunga',
                'county' => 'Kwale',
                'type' => 'day',
                'status' => 'active',
                'note' => 'County senior school profile seed data.',
                'logo_path' => '/images/schools/lungalunga-sec-logo.png',
                'hero_image_path' => '/images/schools/lungalunga-sec-hero.jpg',
                'principal' => [
                    'name' => 'Shariff Mwakio',
                    'email' => 'principal.lungalunga@schools.kwale.ke',
                ],
            ],
            [
                'name' => 'Mwereni Secondary School',
                'code' => 'KWA-SS-010',
                'location' => 'Mwereni, Lunga Lunga',
                'county' => 'Kwale',
                'type' => 'day',
                'status' => 'active',
                'note' => 'County senior school profile seed data.',
                'logo_path' => '/images/schools/mwereni-sec-logo.png',
                'hero_image_path' => '/images/schools/mwereni-sec-hero.jpg',
                'principal' => [
                    'name' => 'Rehema Omar',
                    'email' => 'principal.mwereni@schools.kwale.ke',
                ],
            ],
        ];

        DB::transaction(function () use ($schools, $defaultPrincipalPassword): void {
            foreach ($schools as $entry) {
                $slug = Str::slug($entry['name']);

                $school = School::query()->updateOrCreate(
                    ['code' => $entry['code']],
                    [
                        'name' => $entry['name'],
                        'slug' => $slug,
                        'location' => $entry['location'],
                        'county' => $entry['county'],
                        'type' => $entry['type'],
                        'status' => $entry['status'],
                        'note' => $entry['note'],
                        'logo_path' => $entry['logo_path'],
                        'hero_image_path' => $entry['hero_image_path'],
                    ]
                );

                User::query()->updateOrCreate(
                    ['email' => $entry['principal']['email']],
                    [
                        'school_id' => $school->id,
                        'name' => $entry['principal']['name'],
                        'password' => Hash::make($defaultPrincipalPassword),
                        'role' => 'principal',
                        'email_verified_at' => now(),
                    ]
                );
            }
        });
    }
}