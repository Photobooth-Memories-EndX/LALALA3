<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeUserController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\NewsUserController;
use App\Http\Controllers\AssesmentController;
use App\Http\Controllers\NewsImageController;
use App\Http\Controllers\PagesUserController;
use App\Http\Controllers\EventPhotoController;
use App\Http\Controllers\ContentUserController;
use App\Http\Controllers\GalleryUserController;
use App\Http\Controllers\TrainingPlanController;
use App\Http\Controllers\AssesmentUserController;
use App\Http\Controllers\ContentDeleteController;
use App\Http\Controllers\AssesmentImageController;
use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\AssesmentQuestionController;

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

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', [HomeUserController::class, 'index'])->name('home.user.index')->middleware('auth');
Route::get('/myprofile', [ProfileController::class, 'index'])->name('user.profile')->middleware('auth');
Route::post('/myprofile', [ProfileController::class, 'update'])->name('user.updateprofile')->middleware('auth');
Route::get('/myprofile/deleteavatar', [ProfileController::class, 'deleteavatar'])->name('user.deleteavatar')->middleware('auth');
Route::get('/mycomment', [ProfileController::class, 'comment'])->name('user.comment')->middleware('auth');
Route::get('/search/{searchtype}', [SearchController::class, 'index'])->name('search')->middleware('auth');

Route::group(['prefix' => 'page'], function () {
    Route::get('/{slug}', [PagesUserController::class, 'view'])->name('pages.user.view');
});

Route::group(['prefix' => 'manage', 'middleware' => 'auth'], function () {

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
    Route::put('assement/delete-image/{id}', [AssesmentImageController::class, 'deleteImage'])->name('assesment.deleteImage');

    Route::resource('content', ContentController::class);
    Route::post('content/thumbnail/upload', [ContentController::class, 'uploadthumbnailajax'])->name('content.uploadthumbnailajax');
    Route::get('content/thumbnail/upload/delete', [ContentController::class, 'deletethumbnailajax'])->name('content.deletethumbnailajax');
    Route::post('content/create/upload', [ContentController::class, 'uploadfileajax'])->name('content.uploadfileajax');
    Route::get('content/create/upload/delete', [ContentController::class, 'deletefileajax'])->name('content.deletefileajax');
    Route::get('content/list/ongoing', [ContentController::class, 'ongoingcontent'])->name('content.ongoing');
    Route::put('content/delete/{slug}', [ContentDeleteController::class, 'deletecontentajax'])->name('contentdelete.deletecontentajax');
    Route::post('content/assign', [ContentController::class, 'assign'])->name('content.assign');
    Route::get('trainingplan/order/{slug}', [TrainingPlanController::class, 'order'])->name('trainingplan.order');
    Route::resource('trainingplan', TrainingPlanController::class);
    Route::resource('eventphoto', EventPhotoController::class);
    Route::post('eventphoto/create/upload', [EventPhotoController::class, 'uploadfileajax'])->name('eventphoto.uploadfileajax');
    Route::post('eventphoto/delete', [EventPhotoController::class, 'deleteImage'])->name('eventphoto.deleteImage');
});

Route::group(['prefix' => 'news', 'middleware' => 'auth'], function () {
    Route::get('/', [NewsUserController::class, 'index'])->name('news.user.index');
    Route::get('/{slug}', [NewsUserController::class, 'view'])->name('news.user.view');
    Route::post('/{slug}/comment/store', [CommentController::class, 'storenews'])->name('news.comment.store');
});

Route::group(['prefix' => 'content', 'middleware' => 'auth'], function () {
    Route::get('/{slug}', [ContentUserController::class, 'view'])->name('content.user.view');
    Route::get('category/{slug}', [ContentUserController::class, 'category'])->name('content.user.category');
    Route::put('progress/{slug}', [ContentUserController::class, 'progress'])->name('content.user.progress');
    Route::post('/{slug}/comment/store', [CommentController::class, 'storecontent'])->name('content.comment.store');
});

Route::group(['prefix' => 'learning', 'middleware' => 'auth'], function () {
    Route::get('on-going', [LearningController::class, 'ongoing'])->name('learning.ongoing');
    Route::get('record', [LearningController::class, 'record'])->name('learning.record');
});

Route::group(['prefix' => 'assesment', 'middleware' => 'auth'], function () {
    Route::get('/', [AssesmentUserController::class, 'index'])->name('assesment.user.index');
    Route::get('/{slug}', [AssesmentUserController::class, 'view'])->name('assesment.user.view');
    Route::post('/{slug}/store', [AssesmentUserController::class, 'store'])->name('assesment.user.store');
    Route::get('/{slug}/result', [AssesmentUserController::class, 'result'])->name('assesment.user.result');
});
Route::group(['prefix' => 'gallery', 'middleware' => 'auth'], function () {

    Route::get('/', [GalleryUserController::class, 'index'])->name('gallery.user.index');
    Route::get('/{slug}', [GalleryUserController::class, 'view'])->name('gallery.user.view');
    Route::post('/{slug}/comment/store', [CommentController::class, 'storeeventphoto'])->name('gallery.comment.store');
    // Route::get('/{slug}', [GalleryUserController::class, 'view'])->name('gallery.user.view');
});
Route::get('/login', [LoginController::class, 'index'])->name('loginpage')->middleware('guest');
Route::post('/login', [LoginController::class, 'authenticate'])->name('authuser');
Route::get('/sso', [LoginController::class, 'checksso'])->name('checksso');
Route::post('/logout', [LoginController::class, 'logout'])->name('logoutpage');
