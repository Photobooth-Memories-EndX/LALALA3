<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\NewsUserController;
use App\Http\Controllers\AssesmentController;
use App\Http\Controllers\NewsImageController;
use App\Http\Controllers\PagesUserController;
use App\Http\Controllers\TrainingPlanController;
use App\Http\Controllers\AssesmentQuestionController;
use App\Http\Controllers\ClassificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'page'], function () {
    Route::get('/{slug}', [PagesUserController::class, 'view'])->name('pages.user.view');
});

Route::group(['prefix' => 'manage'], function () {

    Route::resource('user', UserController::class);
    Route::resource('news', NewsController::class);
    Route::put('news/delete-image/{slug}', [NewsImageController::class, 'deleteImage'])->name('news.deleteImage');
    Route::resource('pages', PagesController::class);
    Route::resource('classification', ClassificationController::class);

    Route::resource('assesment', AssesmentController::class);
    Route::get('assesment/{assesment}/question', [AssesmentQuestionController::class, 'question'])->name('assesment.question');
    Route::post('assesment/{assesment}/store', [AssesmentQuestionController::class, 'store'])->name('assesment.question.store');
    Route::delete('assesment/delete/{assesment}/{id}', [AssesmentQuestionController::class, 'destroy'])->name('assesment.question.destroy');
    Route::get('assesment/edit/{assesment}/{id}', [AssesmentQuestionController::class, 'edit'])->name('assesment.question.edit');
    Route::put('assesment/update/{assesment}/{id}', [AssesmentQuestionController::class, 'update'])->name('assesment.question.update');

    Route::resource('content', ContentController::class);
    Route::post('content/thumbnail/upload', [ContentController::class, 'uploadthumbnailajax'])->name('content.uploadthumbnailajax');
    Route::get('content/thumbnail/upload/delete', [ContentController::class, 'deletethumbnailajax'])->name('content.deletethumbnailajax');
    Route::post('content/create/upload', [ContentController::class, 'uploadfileajax'])->name('content.uploadfileajax');
    Route::get('content/create/upload/delete', [ContentController::class, 'deletefileajax'])->name('content.deletefileajax');
    Route::put('content/delete/{slug}', [ContentDeleteController::class, 'deletecontentajax'])->name('contentdelete.deletecontentajax');

    Route::get('trainingplan/order/{slug}', [TrainingPlanController::class, 'order'])->name('trainingplan.order');
    Route::resource('trainingplan', TrainingPlanController::class);
});

Route::group(['prefix' => 'news'], function () {

    Route::get('/', [NewsUserController::class, 'index'])->name('news.user.index');
    Route::get('/{slug}', [NewsUserController::class, 'view'])->name('news.user.view');
});
