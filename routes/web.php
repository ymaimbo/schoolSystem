<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\ProgramAdminController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\StudentController;
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

        // STUDENTS
        Route::middleware('role:principal,deputy_principal,accountant,secretary')->group(function () {
            Route::get('/students', [StudentController::class, 'index'])->name('students.index');
            Route::post('/students', [StudentController::class, 'store'])->name('students.store');
            Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        });

        // secretary cannot delete students
        Route::middleware('role:principal,deputy_principal,accountant')->group(function () {
            Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        });

        // PROGRAMS + EXAMS
        Route::middleware('role:principal,deputy_principal,hod')->group(function () {
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

        // FINANCE
        Route::middleware('role:principal,accountant')->group(function () {
            Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
            Route::post('/finance', [FinanceController::class, 'store'])->name('finance.store');
            Route::put('/finance/{financeTransaction}', [FinanceController::class, 'update'])->name('finance.update');
            Route::delete('/finance/{financeTransaction}', [FinanceController::class, 'destroy'])->name('finance.destroy');
        });

        // STORE (store_keeper can view/add/update)
        Route::middleware('role:principal,accountant,store_keeper')->group(function () {
            Route::get('/store', [StoreController::class, 'index'])->name('store.index');
            Route::post('/store', [StoreController::class, 'store'])->name('store.store');
            Route::put('/store/{inventoryItem}', [StoreController::class, 'update'])->name('store.update');
        });

        // STORE delete only principal + accountant
        Route::middleware('role:principal,accountant')->group(function () {
            Route::delete('/store/{inventoryItem}', [StoreController::class, 'destroy'])->name('store.destroy');
        });
    });
});

require __DIR__ . '/auth.php';