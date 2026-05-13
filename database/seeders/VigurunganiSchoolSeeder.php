<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VigurunganiSchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $school = School::updateOrCreate(
            ['code' => '2109104'],
            [
                'name' => 'Vigurungani Senior School',
                'slug' => Str::slug('Vigurungani Senior School'),
                'location' => 'Kinango Constituency, Kwale County, Kenya',
                'county' => 'Kwale County',
                'type' => 'Public Mixed Boarding School',
                'status' => 'Listed in the official register of secondary schools in Kwale County.',
                'note' => 'Specific contact details, postal addresses, and KCSE results are not explicitly provided in the current context.',
                'logo_path' => '/images/vigurungani-logo.png',
                'hero_image_path' => '/images/kinango-campus.jpg',
            ]
        );

        $school->programs()->delete();
        $school->staffMembers()->delete();

        $school->programs()->createMany([
            [
                'title' => 'Sports Excellence',
                'slug' => 'sports-excellence',
                'summary' => 'Strong basketball, football, and volleyball teams for both girls and boys.',
                'details' => 'Students train in structured sessions with coaching support, regular inter-school fixtures, and a focus on discipline, teamwork, and leadership through sports.',
                'image_path' => '/images/school-sports.jpg',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Science and Technology Program',
                'slug' => 'science-and-technology',
                'summary' => 'Hands-on learning in science, innovation, and practical technology skills.',
                'details' => 'The program supports inquiry-based learning in laboratory sciences and encourages participation in science fairs and technology projects to prepare students for modern careers.',
                'image_path' => '/images/kinango-campus.jpg',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Agriculture Program',
                'slug' => 'agriculture-program',
                'summary' => 'Applied agriculture learning with a functional chicken pen house.',
                'details' => 'Learners engage in poultry management, feeding plans, hygiene practices, and agribusiness basics as part of practical agricultural education.',
                'image_path' => '/images/chicken-pen-program.jpg',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ]);

        $school->staffMembers()->createMany([
            [
                'name' => 'Mr. Abass Ulaya',
                'role' => 'Principal',
                'department' => 'School Administration',
                'bio' => 'Provides strategic leadership and oversees academic and co-curricular excellence across the school.',
                'sort_order' => 1,
                'is_leadership' => true,
            ],
            [
                'name' => 'Mr Joel Nyae',
                'role' => 'Deputy Principal',
                'department' => 'Academics and Student Affairs',
                'bio' => 'Coordinates academic programs, examinations, and student welfare operations.',
                'sort_order' => 2,
                'is_leadership' => true,
            ],
            [
                'name' => 'Heads of Department',
                'role' => 'Departmental Leadership',
                'department' => 'Sciences, Humanities, Languages, Sports, Agriculture',
                'bio' => 'Lead curriculum planning, mentoring, and instructional quality in each subject area.',
                'sort_order' => 3,
                'is_leadership' => true,
            ],
            [
                'name' => 'Teaching Staff Team',
                'role' => 'Teachers',
                'department' => 'Academic Departments',
                'bio' => 'Deliver competency-focused teaching, mentoring, and assessment support for all learners.',
                'sort_order' => 4,
                'is_leadership' => false,
            ],
            [
                'name' => 'Boarding and Student Welfare Team',
                'role' => 'Student Support Staff',
                'department' => 'Boarding',
                'bio' => 'Support boarding routines, safety, discipline, and holistic student well-being.',
                'sort_order' => 5,
                'is_leadership' => false,
            ],
            [
                'name' => 'Support Staff Team',
                'role' => 'Operations Staff',
                'department' => 'School Operations',
                'bio' => 'Maintain facilities, sanitation, food services, and daily operations for a conducive learning environment.',
                'sort_order' => 6,
                'is_leadership' => false,
            ],
        ]);
    }
}