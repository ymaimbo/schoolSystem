<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\FinanceTransaction;
use App\Models\InventoryItem;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        $role = $this->normalizeRole($user->role ?? '');

        $cards = $this->cardsByRole($role);

        return Inertia::render('Admin/Dashboard', [
            'role' => $role,
            'cards' => $cards,
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
        $storeItemsCount = class_exists(InventoryItem::class) ? InventoryItem::count() : 0;

        $common = [
            [
                'title' => 'Total Students',
                'value' => Student::count(),
                'hint' => 'All enrolled student records',
            ],
            [
                'title' => 'Active Students',
                'value' => Student::where('status', 'active')->count(),
                'hint' => 'Currently active learners',
            ],
        ];

        if ($role === 'principal') {
            return array_merge($common, [
                [
                    'title' => 'Published Exams',
                    'value' => Exam::where('status', 'published')->count(),
                    'hint' => 'Exams available to departments',
                ],
                [
                    'title' => 'Finance Balance',
                    'value' => number_format($financeIncome - $financeExpense, 2),
                    'hint' => 'Income minus expense',
                ],
                [
                    'title' => 'Store Items',
                    'value' => $storeItemsCount,
                    'hint' => 'Current inventory records',
                ],
            ]);
        }

        if ($role === 'deputy_principal') {
            return [
                $common[0],
                $common[1],
                [
                    'title' => 'Published Exams',
                    'value' => Exam::where('status', 'published')->count(),
                    'hint' => 'Academic progress control',
                ],
                [
                    'title' => 'Draft Exams',
                    'value' => Exam::where('status', 'draft')->count(),
                    'hint' => 'Pending publication',
                ],
            ];
        }

        if ($role === 'hod') {
            return [
                [
                    'title' => 'Published Exams',
                    'value' => Exam::where('status', 'published')->count(),
                    'hint' => 'Department-ready exams',
                ],
                [
                    'title' => 'Draft Exams',
                    'value' => Exam::where('status', 'draft')->count(),
                    'hint' => 'Needs completion',
                ],
                [
                    'title' => 'Active Students',
                    'value' => Student::where('status', 'active')->count(),
                    'hint' => 'Learners under departments',
                ],
            ];
        }

        if ($role === 'secretary') {
            return [
                [
                    'title' => 'Total Students',
                    'value' => Student::count(),
                    'hint' => 'Student registry overview',
                ],
                [
                    'title' => 'Active Students',
                    'value' => Student::where('status', 'active')->count(),
                    'hint' => 'Current active learners',
                ],
                [
                    'title' => 'Published Exams',
                    'value' => Exam::where('status', 'published')->count(),
                    'hint' => 'Exam visibility for updates',
                ],
            ];
        }

        if ($role === 'store_keeper') {
            return [
                [
                    'title' => 'Store Items',
                    'value' => $storeItemsCount,
                    'hint' => 'Inventory and procurement',
                ],
                [
                    'title' => 'Active Students',
                    'value' => Student::where('status', 'active')->count(),
                    'hint' => 'Learner population support',
                ],
            ];
        }

        // accountant default
        return [
            [
                'title' => 'Finance Income',
                'value' => number_format($financeIncome, 2),
                'hint' => 'Total income records',
            ],
            [
                'title' => 'Finance Expense',
                'value' => number_format($financeExpense, 2),
                'hint' => 'Total expense records',
            ],
            [
                'title' => 'Finance Balance',
                'value' => number_format($financeIncome - $financeExpense, 2),
                'hint' => 'Current financial standing',
            ],
            [
                'title' => 'Students',
                'value' => Student::count(),
                'hint' => 'Student ledger relevance',
            ],
            [
                'title' => 'Store Items',
                'value' => $storeItemsCount,
                'hint' => 'Inventory and procurement',
            ],
        ];
    }
}