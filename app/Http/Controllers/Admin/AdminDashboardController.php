<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DisciplineCase;
use App\Models\Exam;
use App\Models\FinanceTransaction;
use App\Models\InventoryItem;
use App\Models\ParentMessage;
use App\Models\SportsDepartmentRecord;
use App\Models\StaffRecord;
use App\Models\Student;
use App\Models\Timetable;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $role = $this->normalizeRole(auth()->user()?->role ?? '');

        return Inertia::render('Admin/Dashboard', [
            'role' => $role,
            'cards' => $this->cardsByRole($role),
        ]);
    }

    private function normalizeRole(string $role): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim($role)));
    }

    private function cardsByRole(string $role): array
    {
        $financeIncome = (float) FinanceTransaction::where('type', 'income')->sum('amount');
        $financeExpense = (float) FinanceTransaction::where('type', 'expense')->sum('amount');

        $storeItems = class_exists(InventoryItem::class) ? InventoryItem::count() : 0;
        $timetableSlots = class_exists(Timetable::class) ? Timetable::count() : 0;
        $messages = class_exists(ParentMessage::class) ? ParentMessage::count() : 0;
        $disciplinePending = class_exists(DisciplineCase::class) ? DisciplineCase::where('status', 'pending')->count() : 0;
        $disciplineOngoing = class_exists(DisciplineCase::class) ? DisciplineCase::where('status', 'ongoing')->count() : 0;
        $sportsPlayers = class_exists(SportsDepartmentRecord::class) ? SportsDepartmentRecord::count() : 0;
        $staffCount = class_exists(StaffRecord::class) ? StaffRecord::count() : 0;

        if ($role === 'principal') {
            return [
                ['title' => 'Total Students', 'value' => Student::count(), 'hint' => 'All enrolled student records'],
                ['title' => 'Finance Balance', 'value' => number_format($financeIncome - $financeExpense, 2), 'hint' => 'Income minus expense'],
                ['title' => 'Staff Directory', 'value' => $staffCount, 'hint' => 'Teachers and workers records'],
                ['title' => 'Discipline Pending', 'value' => $disciplinePending, 'hint' => 'Cases awaiting action'],
                ['title' => 'Discipline Ongoing', 'value' => $disciplineOngoing, 'hint' => 'Cases under follow-up'],
                ['title' => 'Sports Players', 'value' => $sportsPlayers, 'hint' => 'Registered players by sport'],
            ];
        }

        if ($role === 'deputy_principal') {
            return [
                ['title' => 'Staff Directory', 'value' => $staffCount, 'hint' => 'Teachers and workers records'],
                ['title' => 'Teachers On Duty', 'value' => class_exists(StaffRecord::class) ? StaffRecord::where('is_on_duty', true)->count() : 0, 'hint' => 'Current duty list'],
                ['title' => 'Published Exams', 'value' => Exam::where('status', 'published')->count(), 'hint' => 'Academic oversight'],
                ['title' => 'Discipline Pending', 'value' => $disciplinePending, 'hint' => 'Cases awaiting action'],
                ['title' => 'Discipline Ongoing', 'value' => $disciplineOngoing, 'hint' => 'Cases under follow-up'],
                ['title' => 'Timetable Slots', 'value' => $timetableSlots, 'hint' => 'Managed class schedule'],
            ];
        }

        if ($role === 'dean') {
            return [
                ['title' => 'Total Students', 'value' => Student::count(), 'hint' => 'Student registry overview'],
                ['title' => 'Active Students', 'value' => Student::where('status', 'active')->count(), 'hint' => 'Current active learners'],
                ['title' => 'Published Exams', 'value' => Exam::where('status', 'published')->count(), 'hint' => 'Exam oversight'],
                ['title' => 'Discipline Pending', 'value' => $disciplinePending, 'hint' => 'Cases awaiting action'],
                ['title' => 'Discipline Ongoing', 'value' => $disciplineOngoing, 'hint' => 'Cases under follow-up'],
                ['title' => 'Messages Sent', 'value' => $messages, 'hint' => 'Parent communication activity'],
            ];
        }

        if ($role === 'hod') {
            return [
                ['title' => 'Published Exams', 'value' => Exam::where('status', 'published')->count(), 'hint' => 'Department-ready exams'],
                ['title' => 'Draft Exams', 'value' => Exam::where('status', 'draft')->count(), 'hint' => 'Needs completion'],
                ['title' => 'Active Students', 'value' => Student::where('status', 'active')->count(), 'hint' => 'Learners under departments'],
                ['title' => 'Sports Players', 'value' => $sportsPlayers, 'hint' => 'Sports participation'],
            ];
        }

        if ($role === 'secretary') {
            return [
                ['title' => 'Total Students', 'value' => Student::count(), 'hint' => 'Student registry overview'],
                ['title' => 'Parent Records', 'value' => Student::whereNotNull('parent_phone')->count(), 'hint' => 'Contact records available'],
                ['title' => 'Guardian Records', 'value' => Student::whereNotNull('guardian_phone')->count(), 'hint' => 'Guardian contact coverage'],
                ['title' => 'Messages Sent', 'value' => $messages, 'hint' => 'Parent communication activity'],
            ];
        }

        if ($role === 'store_keeper') {
            return [
                ['title' => 'Store Items', 'value' => $storeItems, 'hint' => 'Inventory and procurement'],
                ['title' => 'Active Students', 'value' => Student::where('status', 'active')->count(), 'hint' => 'Learner population support'],
            ];
        }

        // accountant
        return [
            ['title' => 'Finance Income', 'value' => number_format($financeIncome, 2), 'hint' => 'Total income records'],
            ['title' => 'Finance Expense', 'value' => number_format($financeExpense, 2), 'hint' => 'Total expense records'],
            ['title' => 'Finance Balance', 'value' => number_format($financeIncome - $financeExpense, 2), 'hint' => 'Current financial standing'],
            ['title' => 'Students', 'value' => Student::count(), 'hint' => 'Student ledger relevance'],
            ['title' => 'Store Items', 'value' => $storeItems, 'hint' => 'Inventory and procurement'],
        ];
    }
}