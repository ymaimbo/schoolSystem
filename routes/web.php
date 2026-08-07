<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\DisciplineDepartmentController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\ParentCommunicationController;
use App\Http\Controllers\Admin\ParentGuardianController;
use App\Http\Controllers\Admin\ProgramAdminController;
use App\Http\Controllers\Admin\SportsDepartmentController;
use App\Http\Controllers\Admin\StaffDirectoryController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentFinanceController;
use App\Http\Controllers\Admin\TimetableController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', SchoolPageController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::middleware('role:principal,deputy_principal,dean,secretary,accountant')->group(function () {
            Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        });

        Route::middleware('role:principal,deputy_principal,dean,secretary')->group(function () {
            Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        });

        // accountant removed from update/delete
        Route::middleware('role:principal,deputy_principal,dean')->group(function () {
            Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
            Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        });

        Route::middleware('role:principal,deputy_principal,dean,hod')->group(function () {
            Route::get('/programs', [ProgramAdminController::class, 'index'])->name('programs.index');
            Route::post('/programs', [ProgramAdminController::class, 'store'])->name('programs.store');
            Route::put('/programs/{program}', [ProgramAdminController::class, 'update'])->name('programs.update');
            Route::delete('/programs/{program}', [ProgramAdminController::class, 'destroy'])->name('programs.destroy');

            Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
            Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
            Route::put('/exams/{exam}', [ExamController::class, 'update'])->name('exams.update');
            Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy');

            Route::post('/exams/{exam}/results', [ExamController::class, 'storeResult'])->name('exams.results.store');
            Route::put('/exam-results/{examResult}', [ExamController::class, 'updateResult'])->name('exam-results.update');
            Route::delete('/exam-results/{examResult}', [ExamController::class, 'destroyResult'])->name('exam-results.destroy');
        });

        // finance still principal + accountant only
        Route::middleware('role:principal,accountant')->group(function () {
            Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
            Route::post('/finance', [FinanceController::class, 'store'])->name('finance.store');
            Route::put('/finance/{financeTransaction}', [FinanceController::class, 'update'])->name('finance.update');
            Route::delete('/finance/{financeTransaction}', [FinanceController::class, 'destroy'])->name('finance.destroy');

            Route::get('/student-finance', [StudentFinanceController::class, 'index'])->name('student-finance.index');
            Route::post('/student-finance/account', [StudentFinanceController::class, 'upsertAccount'])->name('student-finance.account.upsert');
            Route::post('/student-finance/payment', [StudentFinanceController::class, 'storePayment'])->name('student-finance.payment.store');
            Route::delete('/student-finance/payment/{payment}', [StudentFinanceController::class, 'destroyPayment'])->name('student-finance.payment.destroy');
        });

        Route::middleware('role:principal,accountant,store_keeper')->group(function () {
            Route::get('/store', [StoreController::class, 'index'])->name('store.index');
            Route::post('/store', [StoreController::class, 'store'])->name('store.store');
            Route::put('/store/{inventoryItem}', [StoreController::class, 'update'])->name('store.update');
        });

        Route::middleware('role:principal,accountant')->group(function () {
            Route::delete('/store/{inventoryItem}', [StoreController::class, 'destroy'])->name('store.destroy');
        });

        Route::middleware('role:principal,deputy_principal,dean')->group(function () {
            Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable.index');
            Route::post('/timetable', [TimetableController::class, 'store'])->name('timetable.store');
            Route::put('/timetable/{timetable}', [TimetableController::class, 'update'])->name('timetable.update');
            Route::delete('/timetable/{timetable}', [TimetableController::class, 'destroy'])->name('timetable.destroy');
        });

        Route::middleware('role:principal,secretary,dean')->group(function () {
            Route::get('/parents', [ParentGuardianController::class, 'index'])->name('parents.index');
            Route::put('/parents/{student}', [ParentGuardianController::class, 'update'])->name('parents.update');
        });

        Route::middleware('role:principal,deputy_principal,dean,hod')->group(function () {
            Route::get('/sports', [SportsDepartmentController::class, 'index'])->name('sports.index');
            Route::post('/sports', [SportsDepartmentController::class, 'store'])->name('sports.store');
            Route::put('/sports/{record}', [SportsDepartmentController::class, 'update'])->name('sports.update');
            Route::delete('/sports/{record}', [SportsDepartmentController::class, 'destroy'])->name('sports.destroy');
        });

        // principal has same rights as deputy on discipline
        Route::middleware('role:principal,deputy_principal,dean')->group(function () {
            Route::get('/discipline', [DisciplineDepartmentController::class, 'index'])->name('discipline.index');
            Route::post('/discipline', [DisciplineDepartmentController::class, 'store'])->name('discipline.store');
            Route::put('/discipline/{case}', [DisciplineDepartmentController::class, 'update'])->name('discipline.update');
            Route::delete('/discipline/{case}', [DisciplineDepartmentController::class, 'destroy'])->name('discipline.destroy');
        });

        Route::middleware('role:principal,deputy_principal,secretary,dean')->group(function () {
            Route::get('/communications', [ParentCommunicationController::class, 'index'])->name('communications.index');
            Route::post('/communications/notice', [ParentCommunicationController::class, 'sendNotice'])->name('communications.notice');
            Route::post('/communications/results', [ParentCommunicationController::class, 'sendResults'])->name('communications.results');
        });

        // Deputy + Principal staff/worker records and duty lists
        Route::middleware('role:principal,deputy_principal')->group(function () {
            Route::get('/staff', [StaffDirectoryController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffDirectoryController::class, 'store'])->name('staff.store');
            Route::put('/staff/{staffRecord}', [StaffDirectoryController::class, 'update'])->name('staff.update');
            Route::delete('/staff/{staffRecord}', [StaffDirectoryController::class, 'destroy'])->name('staff.destroy');
        });
    });
});

require __DIR__ . '/auth.php';