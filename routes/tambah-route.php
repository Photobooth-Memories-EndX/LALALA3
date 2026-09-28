<?php

/**
 * ─────────────────────────────────────────────────────────────────────────
 *  ROUTE TASK MANAGEMENT (revisi) — tempel ke routes/web.php
 * ─────────────────────────────────────────────────────────────────────────
 *
 * 1. Tambahkan import di atas file routes/web.php:
 *
 *      use App\Http\Controllers\MyTaskController;
 *      // TaskManageController kemungkinan SUDAH di-import, cek dulu.
 *
 * 2. Di dalam group admin yang sudah ada
 *    (middleware 'mpermission:learning_management'), GANTI isi group
 *    'task' yang lama dengan ini:
 */

use App\Http\Controllers\MyTaskController;
use App\Http\Controllers\TaskManageController;

// ── Task Management (ADMIN, course-first) ─────────────────────────────
Route::prefix('task')->name('manage.task.')->group(function () {
    // Langkah 1: pilih training plan (course)
    Route::get('/', [TaskManageController::class, 'index'])->name('index');

    // Langkah 2: daftar task milik satu training plan
    Route::get('/plan/{trainingPlan}', [TaskManageController::class, 'planTasks'])->name('plan');

    Route::get('/create', [TaskManageController::class, 'create'])->name('create');
    Route::post('/', [TaskManageController::class, 'store'])->name('store');
    Route::get('/ajax/training-plan-contents/{trainingPlan}', [TaskManageController::class, 'trainingPlanContents'])
        ->name('training-plan-contents');
    Route::get('/{task}/edit', [TaskManageController::class, 'edit'])->name('edit');
    Route::put('/{task}', [TaskManageController::class, 'update'])->name('update');
    Route::delete('/{task}', [TaskManageController::class, 'destroy'])->name('destroy');
    Route::post('/{task}/publish', [TaskManageController::class, 'publish'])->name('publish');

    // Koreksi submission user → tandai selesai
    Route::post('/{task}/submissions/{submission}/complete', [TaskManageController::class, 'completeSubmission'])
        ->name('submissions.complete');
});

/**
 * 3. Route USER (My Task) — taruh di luar group admin, misal sejajar dengan
 *    group 'content' / 'assesment' milik user (butuh login):
 */

Route::group(['prefix' => 'my-task', 'middleware' => 'auth'], function () {
    Route::get('/', [MyTaskController::class, 'index'])->name('my-task.index');
    Route::post('/{task}/submit', [MyTaskController::class, 'submit'])->name('my-task.submit');
});
