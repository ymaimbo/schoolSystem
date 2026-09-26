<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\ClassTeacherAssignmentController;
use App\Http\Controllers\Admin\ClassTeacherPortalController;
use App\Http\Controllers\Admin\DisciplineDepartmentController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\FeeStructureController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\ParentCommunicationController;
use App\Http\Controllers\Admin\ParentGuardianController;
use App\Http\Controllers\Admin\ProgramAdminController;
use App\Http\Controllers\Admin\SportsDepartmentController;
use App\Http\Controllers\Admin\StaffDirectoryController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentFinanceController;
use App\Http\Controllers\Admin\SubjectTeacherPortalController;
use App\Http\Controllers\Admin\TimetableController;
use App\Http\Controllers\PlatformHomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolPageController;
use App\Http\Controllers\SchoolRegistrationController;
use App\Http\Controllers\SuperAdmin\InvoiceManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', PlatformHomeController::class)->name('home');
Route::get('/schools/{school:slug}', SchoolPageController::class)->name('schools.show');

Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->prefix('super-admin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/dashboard', [SchoolRegistrationController::class, 'superAdminDashboard'])->name('dashboard');

        Route::get('/schools/register', [SchoolRegistrationController::class, 'create'])->name('schools.register');
        Route::post('/schools/register', [SchoolRegistrationController::class, 'store'])->name('schools.store');
        Route::post('/schools/{school}/create-principal', [SchoolRegistrationController::class, 'createPrincipalForSchool'])->name('schools.principal.store');
        Route::put('/schools/{school}', [SchoolRegistrationController::class, 'updateSchool'])->name('schools.update');
        Route::put('/principals/{principal}', [SchoolRegistrationController::class, 'updatePrincipal'])->name('principals.update');

        Route::get('/billing', [InvoiceManagementController::class, 'index'])->name('billing.index');
        Route::post('/billing', [InvoiceManagementController::class, 'store'])->name('billing.store');
        Route::get('/billing/{invoice}/edit', [InvoiceManagementController::class, 'edit'])->name('billing.edit');
        Route::put('/billing/{invoice}', [InvoiceManagementController::class, 'update'])->name('billing.update');
    });

Route::middleware(['auth', 'verified', 'resolve.school'])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        // Students
        Route::middleware('role:principal,deputy_principal,dean,secretary,accountant')->group(function () {
            Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        });

        Route::middleware('role:principal,deputy_principal,dean,secretary')->group(function () {
            Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        });

        Route::middleware('role:principal,deputy_principal,dean')->group(function () {
            Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
            Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        });

        // Programs + Exams (full control roles)
        Route::middleware('role:principal,deputy_principal,dean')->group(function () {
            // Programs
            Route::get('/programs', [ProgramAdminController::class, 'index'])->name('programs.index');
            Route::post('/programs', [ProgramAdminController::class, 'store'])->name('programs.store');
            Route::put('/programs/{program}', [ProgramAdminController::class, 'update'])->name('programs.update');
            Route::delete('/programs/{program}', [ProgramAdminController::class, 'destroy'])->name('programs.destroy');

            // Exam CRUD (full rights)
            Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
            Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
            Route::put('/exams/{exam}', [ExamController::class, 'update'])->name('exams.update');
            Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy');
        });

        // Results mutation routes:
        // allow full roles + class_teacher + subject_teacher
        // strict data-level scope is enforced by ExamResultPolicy/controller authorize calls.
        Route::middleware('role:principal,deputy_principal,dean,class_teacher,subject_teacher')->group(function () {
            Route::post('/exams/{exam}/results', [ExamController::class, 'storeResult'])->name('exams.results.store');
            Route::post('/exams/{exam}/missing-results', [ExamController::class, 'storeMissingResults'])->name('exams.results.missing.store');
            Route::put('/exams/{exam}/results/{examResult}', [ExamController::class, 'updateResult'])->name('exam-results.update');
            Route::delete('/exams/{exam}/results/{examResult}', [ExamController::class, 'destroyResult'])->name('exam-results.destroy');
        });

        // Timetable
        Route::middleware('role:principal,deputy_principal,dean')->group(function () {
            Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable.index');
            Route::post('/timetable', [TimetableController::class, 'store'])->name('timetable.store');
            Route::put('/timetable/{timetable}', [TimetableController::class, 'update'])->name('timetable.update');
            Route::delete('/timetable/{timetable}', [TimetableController::class, 'destroy'])->name('timetable.destroy');
        });

        // Finance
        Route::middleware('role:principal,accountant')->group(function () {
            Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
            Route::post('/finance', [FinanceController::class, 'store'])->name('finance.store');
            Route::put('/finance/{financeTransaction}', [FinanceController::class, 'update'])->name('finance.update');
            Route::delete('/finance/{financeTransaction}', [FinanceController::class, 'destroy'])->name('finance.destroy');

            Route::post('/finance/voucher', [FinanceController::class, 'storeVoucher'])->name('finance.voucher.store');
            Route::delete('/finance/voucher/{voucher}', [FinanceController::class, 'destroyVoucher'])->name('finance.voucher.destroy');
            Route::get('/finance/voucher/{voucher}/print', [FinanceController::class, 'printVoucher'])->name('finance.voucher.print');

            Route::get('/finance/fee-structures', [FeeStructureController::class, 'index'])->name('finance.fee-structures.index');
            Route::post('/finance/fee-structures', [FeeStructureController::class, 'store'])->name('finance.fee-structures.store');
            Route::get('/finance/fee-structures/{feeStructure}/edit', [FeeStructureController::class, 'edit'])->name('finance.fee-structures.edit');
            Route::put('/finance/fee-structures/{feeStructure}', [FeeStructureController::class, 'update'])->name('finance.fee-structures.update');
            Route::delete('/finance/fee-structures/{feeStructure}', [FeeStructureController::class, 'destroy'])->name('finance.fee-structures.destroy');

            Route::get('/student-finance', [StudentFinanceController::class, 'index'])->name('student-finance.index');
            Route::post('/student-finance/account', [StudentFinanceController::class, 'storeAccount'])->name('student-finance.account.upsert');
            Route::post('/student-finance/payment', [StudentFinanceController::class, 'storePayment'])->name('student-finance.payment.store');
            Route::delete('/student-finance/payment/{payment}', [StudentFinanceController::class, 'destroyPayment'])->name('student-finance.payment.destroy');
            Route::get('/student-finance/receipt/{payment}/print', [StudentFinanceController::class, 'printReceipt'])->name('student-finance.receipt.print');

            Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
            Route::get('/billing/invoices/{invoice}', [BillingController::class, 'show'])->name('billing.show');
            Route::post('/billing/invoices/{invoice}/payments', [BillingController::class, 'storePayment'])->name('billing.payments.store');
            Route::get('/billing/receipts/{payment}/print', [BillingController::class, 'printReceipt'])->name('billing.receipts.print');
            Route::get('/billing/receipts/{payment}/mpesa', [BillingController::class, 'mpesaReceipt'])->name('billing.receipts.mpesa');
            Route::get('/billing/receipts/{payment}/pdf', [BillingController::class, 'downloadReceiptPdf'])->name('billing.receipts.pdf');
        });

        // Store
        Route::middleware('role:principal,accountant,store_keeper')->group(function () {
            Route::get('/store', [StoreController::class, 'index'])->name('store.index');
            Route::post('/store', [StoreController::class, 'store'])->name('store.store');
            Route::put('/store/{inventoryItem}', [StoreController::class, 'update'])->name('store.update');
        });

        Route::middleware('role:principal,accountant')->group(function () {
            Route::delete('/store/{inventoryItem}', [StoreController::class, 'destroy'])->name('store.destroy');
        });

        // Parents
        Route::middleware('role:principal,secretary,dean')->group(function () {
            Route::get('/parents', [ParentGuardianController::class, 'index'])->name('parents.index');
            Route::put('/parents/{student}', [ParentGuardianController::class, 'update'])->name('parents.update');
        });

        // Sports
        Route::middleware('role:principal,deputy_principal,dean')->group(function () {
            Route::get('/sports', [SportsDepartmentController::class, 'index'])->name('sports.index');
            Route::post('/sports', [SportsDepartmentController::class, 'store'])->name('sports.store');
            Route::put('/sports/{record}', [SportsDepartmentController::class, 'update'])->name('sports.update');
            Route::delete('/sports/{record}', [SportsDepartmentController::class, 'destroy'])->name('sports.destroy');
        });

        // Discipline
        Route::middleware('role:principal,deputy_principal,dean')->group(function () {
            Route::get('/discipline', [DisciplineDepartmentController::class, 'index'])->name('discipline.index');
            Route::post('/discipline', [DisciplineDepartmentController::class, 'store'])->name('discipline.store');
            Route::put('/discipline/{case}', [DisciplineDepartmentController::class, 'update'])->name('discipline.update');
            Route::delete('/discipline/{case}', [DisciplineDepartmentController::class, 'destroy'])->name('discipline.destroy');
        });

        // Communications
        Route::middleware('role:principal,deputy_principal,secretary,dean')->group(function () {
            Route::get('/communications', [ParentCommunicationController::class, 'index'])->name('communications.index');
            Route::post('/communications/notice', [ParentCommunicationController::class, 'sendNotice'])->name('communications.notice');
            Route::post('/communications/results', [ParentCommunicationController::class, 'sendResults'])->name('communications.results');
        });

        // Staff
        Route::middleware('role:principal,deputy_principal')->group(function () {
            Route::get('/staff', [StaffDirectoryController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffDirectoryController::class, 'store'])->name('staff.store');
            Route::put('/staff/{staffRecord}', [StaffDirectoryController::class, 'update'])->name('staff.update');
            Route::delete('/staff/{staffRecord}', [StaffDirectoryController::class, 'destroy'])->name('staff.destroy');
        });

        // Class teacher assignment admin
        Route::middleware('role:principal')->group(function () {
            Route::get('/class-teacher-assignments', [ClassTeacherAssignmentController::class, 'index'])->name('class-teacher-assignments.index');
            Route::post('/class-teacher-assignments', [ClassTeacherAssignmentController::class, 'store'])->name('class-teacher-assignments.store');
            Route::put('/class-teacher-assignments/{assignment}', [ClassTeacherAssignmentController::class, 'update'])->name('class-teacher-assignments.update');
            Route::delete('/class-teacher-assignments/{assignment}', [ClassTeacherAssignmentController::class, 'destroy'])->name('class-teacher-assignments.destroy');
        });

        // Class teacher portal
        Route::middleware(['role:class_teacher', 'class.teacher.scope'])
            ->prefix('class-room')
            ->name('class-room.')
            ->group(function () {
                Route::get('/', [ClassTeacherPortalController::class, 'index'])->name('index');
                Route::get('/discipline', [ClassTeacherPortalController::class, 'discipline'])->name('discipline');
                Route::post('/exams/{exam}/marks', [ClassTeacherPortalController::class, 'storeExamMarks'])->name('exams.marks.store');
            });

        // Subject teacher portal
        Route::middleware(['role:subject_teacher', 'subject.teacher.scope'])
            ->prefix('subject-room')
            ->name('subject-room.')
            ->group(function () {
                Route::get('/', [SubjectTeacherPortalController::class, 'index'])->name('index');
                Route::get('/discipline', [SubjectTeacherPortalController::class, 'discipline'])->name('discipline');
                Route::post('/exams/{exam}/marks', [SubjectTeacherPortalController::class, 'storeExamMarks'])->name('exams.marks.store');
            });
    });
});

require __DIR__ . '/auth.php';