<?php

namespace Database\Seeders;

use App\Models\ClassTeacherAssignment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolOperationsSeeder extends Seeder
{
    /**
     * Keep teacher IDs by class key for assignment.
     *
     * @var array<string, int>
     */
    private array $teacherIds = [];

    public function run(): void
    {
        $school = School::query()
            ->where('slug', 'vigurungani-senior-school')
            ->orWhere('name', 'Vigurungani Secondary School')
            ->orWhere('name', 'Vigurungani Senior School')
            ->firstOrFail();

        DB::transaction(function () use ($school): void {
            $this->seedSchoolUsers($school);
            $this->seedClassTeachers($school);
            $this->seedStudents($school);
            $this->seedClassTeacherAssignments($school);
        });
    }

    private function seedSchoolUsers(School $school): void
    {
        $users = [
            ['email' => 'principal@vigurungani.school', 'name' => 'School Principal', 'role' => 'principal'],
            ['email' => 'deputy@vigurungani.school', 'name' => 'Deputy Principal', 'role' => 'deputy_principal'],
            ['email' => 'dean@vigurungani.school', 'name' => 'School Dean', 'role' => 'dean'],
            ['email' => 'hod@vigurungani.school', 'name' => 'Head Of Department', 'role' => 'hod'],
            ['email' => 'accountant@vigurungani.school', 'name' => 'School Accountant', 'role' => 'accountant'],
            ['email' => 'storekeeper@vigurungani.school', 'name' => 'Store Keeper', 'role' => 'store_keeper'],
            ['email' => 'secretary@vigurungani.school', 'name' => 'School Secretary', 'role' => 'secretary'],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'email' => $user['email'],
                ],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }

    private function seedClassTeachers(School $school): void
    {
        $teachers = [
            ['class_level' => 'Grade 10', 'stream' => 'Green', 'name' => 'ASHA NJIRA HAMED'],
            ['class_level' => 'Grade 10', 'stream' => 'Blue',  'name' => 'MATHEW KEMEI'],

            ['class_level' => 'Form 3', 'stream' => 'White', 'name' => 'SOLOMON KAVOI MUASYA'],
            ['class_level' => 'Form 3', 'stream' => 'Red',   'name' => 'ALI MUPHWA'],
            ['class_level' => 'Form 3', 'stream' => 'Green', 'name' => 'JOHN MWENDO'],
            ['class_level' => 'Form 3', 'stream' => 'Blue',  'name' => 'NYAWA STEPHEN'],

            ['class_level' => 'Form 4', 'stream' => 'Green', 'name' => 'HASSAN MATATA'],
            ['class_level' => 'Form 4', 'stream' => 'Blue',  'name' => 'JUMA TSUMA'],
        ];

        foreach ($teachers as $teacher) {
            $email = $this->teacherEmail($teacher['name'], $school->id);

            $user = User::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'email' => $email,
                ],
                [
                    'name' => $teacher['name'],
                    'password' => Hash::make('password'),
                    'role' => 'class_teacher',
                    'email_verified_at' => now(),
                ]
            );

            $key = $this->classKey($teacher['class_level'], $teacher['stream']);
            $this->teacherIds[$key] = (int) $user->id;
        }
    }

    private function seedClassTeacherAssignments(School $school): void
    {
        foreach ($this->teacherIds as $key => $userId) {
            [$classLevel, $stream] = explode('|', $key);

            ClassTeacherAssignment::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'user_id' => $userId,
                ],
                [
                    'class_level' => $classLevel,
                    'stream' => $stream,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedStudents(School $school): void
    {
        $classes = [
            // -------------------------- GRADE 10 --------------------------
            [
                'class_level' => 'Grade 10',
                'stream' => 'Green',
                'education_system' => 'CBC',
                'students' => [
                    ['admission_no' => '1691', 'name' => 'AMINA MGULWA MGUTA', 'gender' => 'Female'],
                    ['admission_no' => '1695', 'name' => 'CHINYAVU MWANAMVUA ALII', 'gender' => 'Female'],
                    ['admission_no' => '1697', 'name' => 'LOICE CHIZI WANGONI', 'gender' => 'Female'],
                    ['admission_no' => '1700', 'name' => 'NZALAMBI MDOE NDURYA', 'gender' => 'Female'],
                    ['admission_no' => '1701', 'name' => 'WAMATI MARY WANYIKA', 'gender' => 'Female'],
                    ['admission_no' => '1702', 'name' => 'KOMBOLA EVANGLINE MGHOI', 'gender' => 'Female'],
                    ['admission_no' => '1708', 'name' => 'SALOME DZADZE NGUTA', 'gender' => 'Female'],
                    ['admission_no' => '1709', 'name' => 'LUVUNO NDEGWA', 'gender' => 'Female'],
                    ['admission_no' => '1714', 'name' => 'JESCAR KADZO WILFRED', 'gender' => 'Female'],
                    ['admission_no' => '1715', 'name' => 'CHRISTINE NZALAMBI MWATELA', 'gender' => 'Female'],
                    ['admission_no' => '1717', 'name' => 'SAUMU MWAKA MAHUPA', 'gender' => 'Female'],
                    ['admission_no' => '1718', 'name' => 'MBEYU BEMEDZA MLALA', 'gender' => 'Female'],
                    ['admission_no' => '1719', 'name' => 'MBEYU MWERO MANGALE', 'gender' => 'Female'],
                    ['admission_no' => '1720', 'name' => 'JECINTA NZARA JABU', 'gender' => 'Female'],
                    ['admission_no' => '1724', 'name' => 'UMAZI CHILOLO MERI', 'gender' => 'Female'],
                    ['admission_no' => '1727', 'name' => 'CHARITY MBEYU JAWA', 'gender' => 'Female'],
                    ['admission_no' => '1728', 'name' => 'FARIDA LUVUNO CHIRUDI', 'gender' => 'Female'],
                    ['admission_no' => '1730', 'name' => 'ZUHURA MLONGO BAHATI', 'gender' => 'Female'],
                    ['admission_no' => '1731', 'name' => 'SIMAIYA NZARA HAMISI', 'gender' => 'Female'],
                    ['admission_no' => '1734', 'name' => 'SARRAH CHIZI MDZOMBA', 'gender' => 'Female'],
                    ['admission_no' => '1735', 'name' => 'AMINA NYAMVULA MGANDI', 'gender' => 'Female'],
                    ['admission_no' => '1736', 'name' => 'SALOME MBEYU MDOE', 'gender' => 'Female'],
                    ['admission_no' => '1737', 'name' => 'FATUMA KADIDI JUMAA', 'gender' => 'Female'],
                    ['admission_no' => '1742', 'name' => 'TUMAINI SETOON KANUNGA', 'gender' => 'Female'],
                    ['admission_no' => '1745', 'name' => 'RIZIKI MTWANA CHAKA', 'gender' => 'Female'],
                    ['admission_no' => '1747', 'name' => 'FATUMA DZADZE JUMAA', 'gender' => 'Female'],
                    ['admission_no' => '1748', 'name' => 'HALIMA NJIRA MWAMZUKA', 'gender' => 'Female'],
                    ['admission_no' => '1752', 'name' => 'ELIZABETH MWAKA MWANDORO', 'gender' => 'Female'],
                    ['admission_no' => '1753', 'name' => 'SALOME UMAZI MWANDORO', 'gender' => 'Female'],
                    ['admission_no' => '1755', 'name' => 'MWANAPILI MWAKA KARISA', 'gender' => 'Female'],
                    ['admission_no' => '1756', 'name' => 'GRACE LUVUNO MWAYAMA', 'gender' => 'Female'],
                    ['admission_no' => '1757', 'name' => 'AGNES MONJE MWACHILUNGO', 'gender' => 'Female'],
                    ['admission_no' => '1758', 'name' => 'YASMIN MBODZE MWACHILUNGO', 'gender' => 'Female'],
                ],
            ],
            [
                'class_level' => 'Grade 10',
                'stream' => 'Blue',
                'education_system' => 'CBC',
                'students' => [
                    ['admission_no' => '1698', 'name' => 'ISAYA MUMBO CHOMBO', 'gender' => 'Male'],
                    ['admission_no' => '1699', 'name' => 'HAJI JAWA TUNDO', 'gender' => 'Male'],
                    ['admission_no' => '1703', 'name' => 'SHADRACK NJOLE', 'gender' => 'Male'],
                    ['admission_no' => '1704', 'name' => 'CHIONZO MWAVADU', 'gender' => 'Male'],
                    ['admission_no' => '1705', 'name' => 'FRANCIS BAYA MBUI', 'gender' => 'Male'],
                    ['admission_no' => '1706', 'name' => 'HAMISI MWACHUPA MWAHUYA', 'gender' => 'Male'],
                    ['admission_no' => '1707', 'name' => 'MASHUDI RASI MURIPHE', 'gender' => 'Male'],
                    ['admission_no' => '1710', 'name' => 'RAMADHAN KOMBO KATANA', 'gender' => 'Male'],
                    ['admission_no' => '1711', 'name' => 'DANIEL MWERO NGALA', 'gender' => 'Male'],
                    ['admission_no' => '1712', 'name' => 'HAMISI KAMANZA ALI', 'gender' => 'Male'],
                    ['admission_no' => '1713', 'name' => 'JAWA LUPHANDE JAWA', 'gender' => 'Male'],
                    ['admission_no' => '1716', 'name' => 'MWANGONGO NDEGWA', 'gender' => 'Male'],
                    ['admission_no' => '1721', 'name' => 'KISINGU NTHWEMBWA KISINGU', 'gender' => 'Male'],
                    ['admission_no' => '1722', 'name' => 'CHAKA OMAR DANI', 'gender' => 'Male'],
                    ['admission_no' => '1723', 'name' => 'MWANYASI ZAKA', 'gender' => 'Male'],
                    ['admission_no' => '1725', 'name' => 'LUKEMEN NGOWA MANGISI', 'gender' => 'Male'],
                    ['admission_no' => '1726', 'name' => 'KILONZO KASSIM', 'gender' => 'Male'],
                    ['admission_no' => '1729', 'name' => 'ALI KATANA RAMA', 'gender' => 'Male'],
                    ['admission_no' => '1732', 'name' => 'MWAINZI NYAWA TSUMA', 'gender' => 'Male'],
                    ['admission_no' => '1738', 'name' => 'RAVINO KITUNGA', 'gender' => 'Male'],
                    ['admission_no' => '1739', 'name' => 'MWAGOMBA MWAJIRANI', 'gender' => 'Male'],
                    ['admission_no' => '1740', 'name' => 'HAMISI NDEGWA DENIS', 'gender' => 'Male'],
                    ['admission_no' => '1741', 'name' => 'KAZUNGU TSUMA', 'gender' => 'Male'],
                    ['admission_no' => '1743', 'name' => 'CHAKA SHADRACK CHIBANDA', 'gender' => 'Male'],
                    ['admission_no' => '1744', 'name' => 'GABRIEL NYONDO MWAVADU', 'gender' => 'Male'],
                    ['admission_no' => '1746', 'name' => 'CHIBOYA MWAVUO NGOME', 'gender' => 'Male'],
                    ['admission_no' => '1749', 'name' => 'JUNIOR MANGALE NYAWA', 'gender' => 'Male'],
                    ['admission_no' => '1750', 'name' => 'TSIWEZI MWERO CHAPU', 'gender' => 'Male'],
                    ['admission_no' => '1751', 'name' => 'MWADINGO DENIS NYAMAWI', 'gender' => 'Male'],
                    ['admission_no' => '1754', 'name' => 'PAUL MWACHUPA MWERO', 'gender' => 'Male'],
                    ['admission_no' => '1759', 'name' => 'PAUL MBEGA CHAKA', 'gender' => 'Male'],
                    ['admission_no' => '1762', 'name' => 'RAMADHAN BEJA NYAMAWI', 'gender' => 'Male'],
                    ['admission_no' => '1763', 'name' => 'SALIM ZUMA BEJA', 'gender' => 'Male'],
                ],
            ],

            // -------------------------- FORM 3 --------------------------
            [
                'class_level' => 'Form 3',
                'stream' => 'White',
                'education_system' => '8-4-4',
                'students' => [
                    ['admission_no' => '1366', 'name' => 'NGANDO MARIA CHINYAVU', 'gender' => 'Female', 'entry_marks' => 193],
                    ['admission_no' => '1376', 'name' => 'HUSNA KADZO JUMA', 'gender' => 'Female', 'entry_marks' => 190],
                    ['admission_no' => '1378', 'name' => 'ALICE DZAME', 'gender' => 'Female', 'entry_marks' => 173],
                    ['admission_no' => '1379', 'name' => 'FATUMA MBEYU CHIGODI', 'gender' => 'Female', 'entry_marks' => 192],
                    ['admission_no' => '1383', 'name' => 'AGNES UMAZI MAGANGA', 'gender' => 'Female', 'entry_marks' => 193],
                    ['admission_no' => '1389', 'name' => 'PATIENCE MWANYIKA', 'gender' => 'Female', 'entry_marks' => 221],
                    ['admission_no' => '1395', 'name' => 'SERAH MALEI MUTUKU', 'gender' => 'Female', 'entry_marks' => 178],
                    ['admission_no' => '1404', 'name' => 'JOSPHINE MLONGO', 'gender' => 'Female', 'entry_marks' => 198],
                    ['admission_no' => '1410', 'name' => 'UMAZI MWAJOTO JULO', 'gender' => 'Female', 'entry_marks' => 157],
                    ['admission_no' => '1414', 'name' => 'REHEMA KWEKWE MGANDI', 'gender' => 'Female', 'entry_marks' => 197],
                    ['admission_no' => '1416', 'name' => 'PILI NADZUWA YAWA', 'gender' => 'Female', 'entry_marks' => 218],
                    ['admission_no' => '1422', 'name' => 'MUMBO NAOMI LUVUNO', 'gender' => 'Female', 'entry_marks' => 245],
                    ['admission_no' => '1425', 'name' => 'AFSA MJENI JOTO', 'gender' => 'Female', 'entry_marks' => 234],
                    ['admission_no' => '1428', 'name' => 'BAYA ROSE SIDI', 'gender' => 'Female', 'entry_marks' => 233],
                    ['admission_no' => '1433', 'name' => 'ANZARI MEJE MWACHUPA', 'gender' => 'Female', 'entry_marks' => 196],
                    ['admission_no' => '1435', 'name' => 'MILLICENT DICKSON', 'gender' => 'Female', 'entry_marks' => 304],
                    ['admission_no' => '1437', 'name' => 'KAPHUNZA BAHATI KWEKWE', 'gender' => 'Female', 'entry_marks' => 182],
                    ['admission_no' => '1439', 'name' => 'BLESSING MBITHE MUTEI', 'gender' => 'Female', 'entry_marks' => 200],
                    ['admission_no' => '1448', 'name' => 'MWARI MANGALE BUNDI', 'gender' => 'Female', 'entry_marks' => 226],
                    ['admission_no' => '1455', 'name' => 'RUMBA ELIZABETH MUPA', 'gender' => 'Female', 'entry_marks' => 251],
                    ['admission_no' => '1461', 'name' => 'SOPHIA KWEKWE TAO', 'gender' => 'Female', 'entry_marks' => 154],
                    ['admission_no' => '1463', 'name' => 'NAOMI MBEYU NGUTA', 'gender' => 'Female', 'entry_marks' => 195],
                    ['admission_no' => '1467', 'name' => 'KOMBO KAREMBO DZAME', 'gender' => 'Female', 'entry_marks' => 154],
                    ['admission_no' => '1478', 'name' => 'MEJUMAA MBEYU KUFASA', 'gender' => 'Female', 'entry_marks' => 206],
                    ['admission_no' => '1483', 'name' => 'MWAKA MINGI', 'gender' => 'Female', 'entry_marks' => 240],
                    ['admission_no' => '1487', 'name' => 'KADZELE MUNDU BEJA', 'gender' => 'Female', 'entry_marks' => 311],
                    ['admission_no' => '1490', 'name' => 'LOYCE ZUGA', 'gender' => 'Female', 'entry_marks' => 198],
                    ['admission_no' => '1500', 'name' => 'SIDI KARISA THOYA', 'gender' => 'Female', 'entry_marks' => 254],
                    ['admission_no' => '1508', 'name' => 'MWAKA MALACHI', 'gender' => 'Female', 'entry_marks' => 234],
                    ['admission_no' => '1512', 'name' => 'SAUMU ANZAZI MWACHIRUMBI', 'gender' => 'Female', 'entry_marks' => 254],
                    ['admission_no' => '1528', 'name' => 'UMAZI DZONEWA BECHIZI', 'gender' => 'Female', 'entry_marks' => 195],
                    ['admission_no' => '1530', 'name' => 'NURU MWAKA KAPOTE', 'gender' => 'Female', 'entry_marks' => 167],
                    ['admission_no' => '1538', 'name' => 'MONICA AMBOKA', 'gender' => 'Female', 'entry_marks' => 205],
                ],
            ],
            [
                'class_level' => 'Form 3',
                'stream' => 'Red',
                'education_system' => '8-4-4',
                'students' => [
                    ['admission_no' => '1371', 'name' => 'BALOZI JAWA', 'gender' => 'Male', 'entry_marks' => 270],
                    ['admission_no' => '1375', 'name' => 'NYAMAWI CHAGA RASHID', 'gender' => 'Male', 'entry_marks' => 177],
                    ['admission_no' => '1381', 'name' => 'SALIM MWERO NDEGWA', 'gender' => 'Male', 'entry_marks' => 254],
                    ['admission_no' => '1385', 'name' => 'JOSEPH RUWA TSUMA', 'gender' => 'Male', 'entry_marks' => 252],
                    ['admission_no' => '1388', 'name' => 'WILLIAM BENZINGO', 'gender' => 'Male', 'entry_marks' => 189],
                    ['admission_no' => '1391', 'name' => 'JOTO TETE CHITUMBUA', 'gender' => 'Male', 'entry_marks' => 191],
                    ['admission_no' => '1396', 'name' => 'KAMBI MWANZA', 'gender' => 'Male', 'entry_marks' => 207],
                    ['admission_no' => '1400', 'name' => 'HAMISI DENA RUKIA', 'gender' => 'Male', 'entry_marks' => 227],
                    ['admission_no' => '1403', 'name' => 'BONFACE MUTISO KILU', 'gender' => 'Male', 'entry_marks' => 203],
                    ['admission_no' => '1407', 'name' => 'PETER MAKAU MATUU', 'gender' => 'Male', 'entry_marks' => 215],
                    ['admission_no' => '1409', 'name' => 'SAMUEL CHIDUNGA MWAJOTO', 'gender' => 'Male', 'entry_marks' => 252],
                    ['admission_no' => '1413', 'name' => 'YUSUFU MWAHARANGA MGUTA', 'gender' => 'Male', 'entry_marks' => 232],
                    ['admission_no' => '1419', 'name' => 'SAID HAMZA SEIF', 'gender' => 'Male', 'entry_marks' => 219],
                    ['admission_no' => '1421', 'name' => 'SALIM NDORO MALAU', 'gender' => 'Male', 'entry_marks' => 279],
                    ['admission_no' => '1426', 'name' => 'NYAMAWI MWATELA KAMANZA', 'gender' => 'Male', 'entry_marks' => 233],
                    ['admission_no' => '1431', 'name' => 'HUSSEIN BUNDI SALIM', 'gender' => 'Male', 'entry_marks' => 155],
                    ['admission_no' => '1447', 'name' => 'MAKAZI JOSEPH BORA', 'gender' => 'Male', 'entry_marks' => 276],
                    ['admission_no' => '1451', 'name' => 'EMMANUEL KUMBE', 'gender' => 'Male', 'entry_marks' => 269],
                    ['admission_no' => '1457', 'name' => 'STEPHEN NGOME', 'gender' => 'Male', 'entry_marks' => 274],
                    ['admission_no' => '1464', 'name' => 'PATRICK JOTO CHILOLWA', 'gender' => 'Male', 'entry_marks' => 255],
                    ['admission_no' => '1468', 'name' => 'DAVID NDIMRO CHAKA', 'gender' => 'Male', 'entry_marks' => 239],
                    ['admission_no' => '1470', 'name' => 'TSUMA GABRIEL RAI', 'gender' => 'Male', 'entry_marks' => 182],
                    ['admission_no' => '1473', 'name' => 'BEJA JOGA NJEMO', 'gender' => 'Male', 'entry_marks' => 195],
                    ['admission_no' => '1481', 'name' => 'ZUWA NYAWA', 'gender' => 'Male', 'entry_marks' => 248],
                    ['admission_no' => '1485', 'name' => 'ADAM MATARI KOMBO', 'gender' => 'Male', 'entry_marks' => 224],
                    ['admission_no' => '1493', 'name' => 'YAWA MENZA', 'gender' => 'Male', 'entry_marks' => 197],
                    ['admission_no' => '1495', 'name' => 'NYAMAWI NDEGWA', 'gender' => 'Male', 'entry_marks' => 225],
                    ['admission_no' => '1497', 'name' => 'YUSUF CHIKOPHE', 'gender' => 'Male', 'entry_marks' => 250],
                    ['admission_no' => '1502', 'name' => 'MICHEAL KAPHUNZA', 'gender' => 'Male', 'entry_marks' => 197],
                    ['admission_no' => '1510', 'name' => 'LUGWE TSUMA', 'gender' => 'Male', 'entry_marks' => 335],
                    ['admission_no' => '1523', 'name' => 'ATHUMAN MWANONGO MDATA', 'gender' => 'Male', 'entry_marks' => 176],
                    ['admission_no' => '1531', 'name' => 'TSUMA JOTO', 'gender' => 'Male', 'entry_marks' => 257],
                    ['admission_no' => '1534', 'name' => 'RAMA MUSA', 'gender' => 'Male', 'entry_marks' => 182],
                ],
            ],
            [
                'class_level' => 'Form 3',
                'stream' => 'Green',
                'education_system' => '8-4-4',
                'students' => [
                    ['admission_no' => '1229', 'name' => 'ELIZABETH CHIZI JANGAA', 'gender' => 'Female', 'entry_marks' => 206],
                    ['admission_no' => '1367', 'name' => 'KHADIJA DZAME SAID', 'gender' => 'Female', 'entry_marks' => 258],
                    ['admission_no' => '1373', 'name' => 'REHEMA MBODZE MWAMERI', 'gender' => 'Female', 'entry_marks' => 258],
                    ['admission_no' => '1377', 'name' => 'MALOMBO NYANJE', 'gender' => 'Female', 'entry_marks' => 182],
                    ['admission_no' => '1382', 'name' => 'CYNTHIA MUNDU CHIZIGWA', 'gender' => 'Female', 'entry_marks' => 190],
                    ['admission_no' => '1387', 'name' => 'MLONGO TSUMA', 'gender' => 'Female', 'entry_marks' => 250],
                    ['admission_no' => '1401', 'name' => 'SALMA MLONGO NDEGWA', 'gender' => 'Female', 'entry_marks' => 251],
                    ['admission_no' => '1411', 'name' => 'MARGRET MWAKA KADUNI', 'gender' => 'Female', 'entry_marks' => 230],
                    ['admission_no' => '1415', 'name' => 'PAULINE MBODZE KOMBO', 'gender' => 'Female', 'entry_marks' => 235],
                    ['admission_no' => '1418', 'name' => 'CHARLES LOSMER UMAZI', 'gender' => 'Female', 'entry_marks' => 219],
                    ['admission_no' => '1427', 'name' => 'DZAME MWATELA KAMANZA', 'gender' => 'Female', 'entry_marks' => 204],
                    ['admission_no' => '1429', 'name' => 'UMAZI TSUMA NYAWA', 'gender' => 'Female', 'entry_marks' => 221],
                    ['admission_no' => '1434', 'name' => 'MARY MUNINI MUTUA', 'gender' => 'Female', 'entry_marks' => 249],
                    ['admission_no' => '1438', 'name' => 'EUNICE MLONGO JACKSON', 'gender' => 'Female', 'entry_marks' => 180],
                    ['admission_no' => '1440', 'name' => 'MUTEI MARY APONDI', 'gender' => 'Female', 'entry_marks' => 221],
                    ['admission_no' => '1449', 'name' => 'KASAHANI CHIRIMA', 'gender' => 'Female', 'entry_marks' => 289],
                    ['admission_no' => '1456', 'name' => 'FAITH MBULA DZAU', 'gender' => 'Female', 'entry_marks' => 197],
                    ['admission_no' => '1460', 'name' => 'SAMIRA TAO', 'gender' => 'Female', 'entry_marks' => 212],
                    ['admission_no' => '1462', 'name' => 'KALUNDA JOSHUA', 'gender' => 'Female', 'entry_marks' => 264],
                    ['admission_no' => '1471', 'name' => 'CHARI TSUMA GWEDE', 'gender' => 'Female', 'entry_marks' => 208],
                    ['admission_no' => '1476', 'name' => 'MARY MLONGO BENJAMIN', 'gender' => 'Female', 'entry_marks' => 235],
                    ['admission_no' => '1484', 'name' => 'GRACE MJENI', 'gender' => 'Female', 'entry_marks' => 258],
                    ['admission_no' => '1488', 'name' => 'CHIZIMBA CHIZIMBA', 'gender' => 'Female', 'entry_marks' => 165],
                    ['admission_no' => '1499', 'name' => 'MWANAHAMISI MWINYI', 'gender' => 'Female', 'entry_marks' => 173],
                    ['admission_no' => '1501', 'name' => 'FATUMA MBODZE', 'gender' => 'Female', 'entry_marks' => 278],
                    ['admission_no' => '1509', 'name' => 'JOYCE NEEMA', 'gender' => 'Female', 'entry_marks' => 192],
                    ['admission_no' => '1514', 'name' => 'CHIDUNGA MUPA', 'gender' => 'Female', 'entry_marks' => 231],
                    ['admission_no' => '1527', 'name' => 'MWAINZI CHRISTINE UMAZI', 'gender' => 'Female', 'entry_marks' => 174],
                    ['admission_no' => '1536', 'name' => 'MWAKA NDURYA', 'gender' => 'Female', 'entry_marks' => 291],
                    ['admission_no' => '1550', 'name' => 'MARIA NJIRA CHIONZO', 'gender' => 'Female', 'entry_marks' => 203],
                    ['admission_no' => '1560', 'name' => 'BUGUTA KHADIJA', 'gender' => 'Female', 'entry_marks' => 238],
                    ['admission_no' => '1566', 'name' => 'CHINYAVU MREMA MWADALU', 'gender' => 'Female', 'entry_marks' => 278],
                    ['admission_no' => '1572', 'name' => 'SAUMU MBEYU NYANJE', 'gender' => 'Female', 'entry_marks' => 224],
                ],
            ],
            [
                'class_level' => 'Form 3',
                'stream' => 'Blue',
                'education_system' => '8-4-4',
                'students' => [
                    ['admission_no' => '1368', 'name' => 'SWALEH NDARO MWAMBA', 'gender' => 'Male', 'entry_marks' => 198],
                    ['admission_no' => '1370', 'name' => 'MAZERA GEREZA', 'gender' => 'Male', 'entry_marks' => 269],
                    ['admission_no' => '1374', 'name' => 'ELISHA TAO MWAMERI', 'gender' => 'Male', 'entry_marks' => 227],
                    ['admission_no' => '1380', 'name' => 'BAKARI NGAO CHIGODI', 'gender' => 'Male', 'entry_marks' => 228],
                    ['admission_no' => '1384', 'name' => 'ALI BORA RAJIMBO', 'gender' => 'Male', 'entry_marks' => 275],
                    ['admission_no' => '1386', 'name' => 'NGUTA KAMANZA', 'gender' => 'Male', 'entry_marks' => 228],
                    ['admission_no' => '1394', 'name' => 'ALPHONCE WATO BONFACE', 'gender' => 'Male', 'entry_marks' => 251],
                    ['admission_no' => '1397', 'name' => 'ROBERT CHUPHI RUMBA', 'gender' => 'Male', 'entry_marks' => 268],
                    ['admission_no' => '1402', 'name' => 'TIMOTHY KIOKO', 'gender' => 'Male', 'entry_marks' => 200],
                    ['admission_no' => '1408', 'name' => 'MOHAMMED LUGWE MASHUDI', 'gender' => 'Male', 'entry_marks' => 212],
                    ['admission_no' => '1412', 'name' => 'SAMUEL NDORO KADUNI', 'gender' => 'Male', 'entry_marks' => 202],
                    ['admission_no' => '1417', 'name' => 'YUSUF CHIBUNDUGO MAGANGI', 'gender' => 'Male', 'entry_marks' => 225],
                    ['admission_no' => '1430', 'name' => 'YUSUF MWANONGO SALIM', 'gender' => 'Male', 'entry_marks' => 209],
                    ['admission_no' => '1432', 'name' => 'SAID SULEIMAN KIKOLO', 'gender' => 'Male', 'entry_marks' => 189],
                    ['admission_no' => '1444', 'name' => 'RASHID SWALEH KALENDI', 'gender' => 'Male', 'entry_marks' => 261],
                    ['admission_no' => '1459', 'name' => 'KASSIM NYAWA DAU', 'gender' => 'Male', 'entry_marks' => 198],
                    ['admission_no' => '1469', 'name' => 'NDURYA MAZERA MTENDE', 'gender' => 'Male', 'entry_marks' => 179],
                    ['admission_no' => '1472', 'name' => 'JAWA NGOME CHIKOZA', 'gender' => 'Male', 'entry_marks' => 247],
                    ['admission_no' => '1494', 'name' => 'JACKSON JOTO', 'gender' => 'Male', 'entry_marks' => 328],
                    ['admission_no' => '1496', 'name' => 'OMAR NDORO NGAO', 'gender' => 'Male', 'entry_marks' => 216],
                    ['admission_no' => '1498', 'name' => 'KAULU BEJA MBUI', 'gender' => 'Male', 'entry_marks' => 243],
                    ['admission_no' => '1503', 'name' => 'OMAR CHOMBO', 'gender' => 'Male', 'entry_marks' => 181],
                    ['admission_no' => '1524', 'name' => 'PATRICK MWERO NGANYAWA', 'gender' => 'Male', 'entry_marks' => 236],
                    ['admission_no' => '1526', 'name' => 'MTENDE MAZERA MTENDE', 'gender' => 'Male', 'entry_marks' => 239],
                    ['admission_no' => '1539', 'name' => 'JONATHAN MWACHUPA', 'gender' => 'Male', 'entry_marks' => 244],
                    ['admission_no' => '1544', 'name' => 'HUSSEIN CHIDZIPHI CHAKA', 'gender' => 'Male', 'entry_marks' => 258],
                    ['admission_no' => '1546', 'name' => 'RICHARD MWADINGO', 'gender' => 'Male', 'entry_marks' => 216],
                    ['admission_no' => '1553', 'name' => 'MWAZUMA EMMANUEL', 'gender' => 'Male', 'entry_marks' => 303],
                    ['admission_no' => '1555', 'name' => 'PATRICK CHIBOYA', 'gender' => 'Male', 'entry_marks' => 210],
                    ['admission_no' => '1562', 'name' => 'NGOME JEFA CHIBOYA', 'gender' => 'Male', 'entry_marks' => 280],
                    ['admission_no' => '1564', 'name' => 'NDORO RASHID KOMBO', 'gender' => 'Male', 'entry_marks' => 258],
                    ['admission_no' => '1569', 'name' => 'MRABU KOMBO', 'gender' => 'Male', 'entry_marks' => 176],
                    ['admission_no' => '1573', 'name' => 'JULIUS NYANJE KAMBI', 'gender' => 'Male', 'entry_marks' => 236],
                ],
            ],

            // -------------------------- FORM 4 --------------------------
            [
                'class_level' => 'Form 4',
                'stream' => 'Green',
                'education_system' => '8-4-4',
                'students' => [
                    ['admission_no' => '1160', 'name' => 'LOYCE MLONGO MWATAO', 'gender' => 'Female', 'entry_marks' => 252],
                    ['admission_no' => '1187', 'name' => 'DZAME CHITSUNYU MUGANDI', 'gender' => 'Female', 'entry_marks' => 299],
                    ['admission_no' => '1191', 'name' => 'RIZIKI MULOMBI', 'gender' => 'Female', 'entry_marks' => 229],
                    ['admission_no' => '1196', 'name' => 'EUNICE NYAMVULA KIJUMBE', 'gender' => 'Female', 'entry_marks' => 197],
                    ['admission_no' => '1197', 'name' => 'MUPA MANGISI NYAWA', 'gender' => 'Female', 'entry_marks' => 218],
                    ['admission_no' => '1200', 'name' => 'NOEL LUVUNO MWERO', 'gender' => 'Female', 'entry_marks' => 178],
                    ['admission_no' => '1201', 'name' => 'OLIVE MBEYU JAWA', 'gender' => 'Female', 'entry_marks' => 193],
                    ['admission_no' => '1202', 'name' => 'NOEL KADZO JAWA', 'gender' => 'Female', 'entry_marks' => 208],
                    ['admission_no' => '1212', 'name' => 'CHIZI MDZELE', 'gender' => 'Female', 'entry_marks' => 249],
                    ['admission_no' => '1215', 'name' => 'CHIZI KALIMBO MWERO', 'gender' => 'Female', 'entry_marks' => 217],
                    ['admission_no' => '1216', 'name' => 'MLONGO MWANAMISI MUDZO', 'gender' => 'Female', 'entry_marks' => 237],
                    ['admission_no' => '1224', 'name' => 'DUDU ELIZABETH MWAKA', 'gender' => 'Female', 'entry_marks' => 248],
                    ['admission_no' => '1230', 'name' => 'MATANO MARY MBEKE', 'gender' => 'Female', 'entry_marks' => 216],
                    ['admission_no' => '1239', 'name' => 'SALOME KWEKWE', 'gender' => 'Female', 'entry_marks' => 211],
                    ['admission_no' => '1242', 'name' => 'LOYCE MNYAZI', 'gender' => 'Female', 'entry_marks' => 231],
                    ['admission_no' => '1244', 'name' => 'TUMAINI NZARA KUTO', 'gender' => 'Female', 'entry_marks' => 294],
                    ['admission_no' => '1247', 'name' => 'RIZIKI HIMILI', 'gender' => 'Female', 'entry_marks' => 231],
                    ['admission_no' => '1249', 'name' => 'NZARA NDURYA', 'gender' => 'Female', 'entry_marks' => 210],
                    ['admission_no' => '1255', 'name' => 'RUKIA KANGA MWATEMO', 'gender' => 'Female', 'entry_marks' => 201],
                    ['admission_no' => '1256', 'name' => 'JACKLINE JOHN KITUMBUA', 'gender' => 'Female', 'entry_marks' => 288],
                    ['admission_no' => '1258', 'name' => 'ELIZABETH MLONGO NYONDO', 'gender' => 'Female', 'entry_marks' => 302],
                    ['admission_no' => '1260', 'name' => 'SOPHIA UMAZI KUMBE', 'gender' => 'Female', 'entry_marks' => 234],
                    ['admission_no' => '1261', 'name' => 'AMINA MBEYU MRISA', 'gender' => 'Female', 'entry_marks' => 258],
                    ['admission_no' => '1263', 'name' => 'CHARITY MBEYU CHIBUNDUGO', 'gender' => 'Female', 'entry_marks' => 245],
                    ['admission_no' => '1265', 'name' => 'KAMANZA ROSE CHIZI', 'gender' => 'Female', 'entry_marks' => 296],
                    ['admission_no' => '1267', 'name' => 'CECILIA MBEYU DAWA', 'gender' => 'Female', 'entry_marks' => 252],
                    ['admission_no' => '1268', 'name' => 'JACKLINE MBODZE JABU', 'gender' => 'Female', 'entry_marks' => 269],
                    ['admission_no' => '1272', 'name' => 'NIGORO RUWA', 'gender' => 'Female', 'entry_marks' => 248],
                    ['admission_no' => '1274', 'name' => 'LUCY LUVUNO', 'gender' => 'Female', 'entry_marks' => 322],
                    ['admission_no' => '1279', 'name' => 'KASSIM LUVUNO ASHA', 'gender' => 'Female', 'entry_marks' => 210],
                    ['admission_no' => '1281', 'name' => 'LEA LUVUNO GDULO', 'gender' => 'Female', 'entry_marks' => 191],
                    ['admission_no' => '1287', 'name' => 'TERESA NDUNGE MULI', 'gender' => 'Female', 'entry_marks' => 243],
                    ['admission_no' => '1288', 'name' => 'NEEMA NZARA KARISA', 'gender' => 'Female', 'entry_marks' => 202],
                ],
            ],
            [
                'class_level' => 'Form 4',
                'stream' => 'Blue',
                'education_system' => '8-4-4',
                'students' => [
                    ['admission_no' => '1054', 'name' => 'DAUDI NYANJE KUMBE', 'gender' => 'Male', 'entry_marks' => 214],
                    ['admission_no' => '1060', 'name' => 'JUMAA LOMBA MWANGOLA', 'gender' => 'Male', 'entry_marks' => 241],
                    ['admission_no' => '1169', 'name' => 'MWARUWA IMRAN RUWA', 'gender' => 'Male', 'entry_marks' => 341],
                    ['admission_no' => '1190', 'name' => 'CHARO KATANA', 'gender' => 'Male', 'entry_marks' => 265],
                    ['admission_no' => '1193', 'name' => 'SIMON NDURYA', 'gender' => 'Male', 'entry_marks' => 270],
                    ['admission_no' => '1195', 'name' => 'HUSSEIN NGUTA', 'gender' => 'Male', 'entry_marks' => 280],
                    ['admission_no' => '1199', 'name' => 'RASHID CHIYONZO', 'gender' => 'Male', 'entry_marks' => 206],
                    ['admission_no' => '1205', 'name' => 'SAMSON MWERO', 'gender' => 'Male', 'entry_marks' => 266],
                    ['admission_no' => '1207', 'name' => 'AMOS MUKALA', 'gender' => 'Male', 'entry_marks' => 210],
                    ['admission_no' => '1209', 'name' => 'JAMES KITONYI', 'gender' => 'Male', 'entry_marks' => 233],
                    ['admission_no' => '1213', 'name' => 'HAMISI MERI TSUMA', 'gender' => 'Male', 'entry_marks' => 246],
                    ['admission_no' => '1217', 'name' => 'NZUGA ZACHUUS NDEGWA', 'gender' => 'Male', 'entry_marks' => 248],
                    ['admission_no' => '1219', 'name' => 'BERNAD KASONGI MUTUKU', 'gender' => 'Male', 'entry_marks' => 217],
                    ['admission_no' => '1222', 'name' => 'JULIUS KOYA', 'gender' => 'Male', 'entry_marks' => 297],
                    ['admission_no' => '1234', 'name' => 'MDOE MWACHUPA MDOE', 'gender' => 'Male', 'entry_marks' => 312],
                    ['admission_no' => '1238', 'name' => 'BARAKA VYABULE', 'gender' => 'Male', 'entry_marks' => 233],
                    ['admission_no' => '1241', 'name' => 'EMMANUEL LENGAI', 'gender' => 'Male', 'entry_marks' => 263],
                    ['admission_no' => '1251', 'name' => 'EMANUEL MTENZI NYAWA', 'gender' => 'Male', 'entry_marks' => 250],
                    ['admission_no' => '1253', 'name' => 'HAJI KOMBO MRABU', 'gender' => 'Male', 'entry_marks' => 251],
                    ['admission_no' => '1257', 'name' => 'JUMA MNYIKA BEJA', 'gender' => 'Male', 'entry_marks' => 275],
                    ['admission_no' => '1262', 'name' => 'MANGALE ANDREA NYAWA', 'gender' => 'Male', 'entry_marks' => 258],
                    ['admission_no' => '1273', 'name' => 'MTULA MWERO', 'gender' => 'Male', 'entry_marks' => 260],
                    ['admission_no' => '1282', 'name' => 'MWENDWA DENA KARISA', 'gender' => 'Male', 'entry_marks' => 235],
                    ['admission_no' => '1284', 'name' => 'YOHANA SARONI SAMUEL', 'gender' => 'Male', 'entry_marks' => 250],
                    ['admission_no' => '1286', 'name' => 'HERBAT WANGONI CEPHAS', 'gender' => 'Male', 'entry_marks' => 294],
                    ['admission_no' => '1292', 'name' => 'MALAU ABUBAKAR NZAPHILA', 'gender' => 'Male', 'entry_marks' => 266],
                    ['admission_no' => '1295', 'name' => 'HAMISI KWICHA KAVIBA', 'gender' => 'Male', 'entry_marks' => 223],
                    ['admission_no' => '1302', 'name' => 'STANLY KASIKO MDZOMBA', 'gender' => 'Male', 'entry_marks' => 228],
                    ['admission_no' => '1312', 'name' => 'OMAR TILA SALIM', 'gender' => 'Male', 'entry_marks' => 237],
                    ['admission_no' => '1325', 'name' => 'JEFA NZAI JEFA', 'gender' => 'Male', 'entry_marks' => 245],
                    ['admission_no' => '1327', 'name' => 'JULIUS MWARUWA BEDZINE', 'gender' => 'Male', 'entry_marks' => 206],
                    ['admission_no' => '1333', 'name' => 'DAVID CHIKOPHE NYIPO', 'gender' => 'Male', 'entry_marks' => 283],
                    ['admission_no' => '1353', 'name' => 'EMMANUEL GWEDE', 'gender' => 'Male', 'entry_marks' => 343],
                ],
            ],
        ];

        foreach ($classes as $classData) {
            foreach ($classData['students'] as $row) {
                [$firstName, $lastName] = $this->splitName($row['name']);

                Student::query()->updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'admission_no' => (string) $row['admission_no'],
                    ],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'gender' => $row['gender'],
                        'education_system' => $classData['education_system'],
                        'class_level' => $classData['class_level'],
                        'form_level' => $this->extractFormLevel($classData['class_level']), // ✅ FIX
                        'stream' => $classData['stream'],
                        'entry_marks' => $row['entry_marks'] ?? null,
                        'parent_name' => 'Parent of '.$row['name'],
                        'parent_phone' => $this->fakePhone((string) $row['admission_no']),
                        'status' => 'active',
                        'pathway' => null,
                    ]
                );
            }
        }
    }

    private function classKey(string $classLevel, string $stream): string
    {
        return trim($classLevel).'|'.trim($stream);
    }

    private function teacherEmail(string $name, int $schoolId): string
    {
        $slug = Str::of($name)->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->toString();

        return "{$slug}.{$schoolId}@vigurungani.local";
    }

    private function splitName(string $fullName): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $fullName) ?? $fullName);
        $parts = explode(' ', $clean);

        $first = array_shift($parts) ?? $clean;
        $last = trim(implode(' ', $parts));

        return [$first, $last !== '' ? $last : $first];
    }

    private function fakePhone(string $admissionNo): string
    {
        $digits = preg_replace('/\D+/', '', $admissionNo) ?? '0';
        $digits = str_pad(substr($digits, -7), 7, '0', STR_PAD_LEFT);

        return '071'.$digits;
    }

    private function extractFormLevel(string $classLevel): int
    {
        if (preg_match('/(\d+)/', $classLevel, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}