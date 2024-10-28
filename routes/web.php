<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HomeUserController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\NewsUserController;
use App\Http\Controllers\AssesmentController;
use App\Http\Controllers\NewsImageController;
use App\Http\Controllers\PagesUserController;
use App\Http\Controllers\EventPhotoController;
use App\Http\Controllers\ContentUserController;
use App\Http\Controllers\GalleryUserController;
use App\Http\Controllers\ProfileUserController;
use App\Http\Controllers\PlaylistUserController;
use App\Http\Controllers\TrainingPlanController;
use App\Http\Controllers\AssesmentUserController;
use App\Http\Controllers\ContentDeleteController;
use App\Http\Controllers\AssesmentImageController;
use App\Http\Controllers\AssesmentQuestionBankController;
use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\LearningManageController;
use App\Http\Controllers\AssesmentResultController;
use App\Http\Controllers\AssesmentQuestionController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\QuestionController;

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
Route::get('/change-password', [ProfileController::class, 'changePassword'])->name('user.changepassword')->middleware('auth');
Route::post('/change-password', [ProfileController::class, 'updatePassword'])->name('user.updatepassword')->middleware('auth');

Route::group(['prefix' => 'page'], function () {
    Route::get('/{slug}', [PagesUserController::class, 'view'])->name('pages.user.view');
});

Route::group(['prefix' => 'manage', 'middleware' => 'admin'], function () {

    Route::resource('user', UserController::class);
    Route::put('user/{user}/resetpassword', [UserController::class, 'resetpassword'])->name('user.resetpassword');
    Route::post('user/changestatus', [UserController::class, 'changestatus'])->name('user.changestatus');
    Route::get('user-search', [UserController::class, 'searchUser'])->name('user.search');
    Route::resource('news', NewsController::class);
    Route::put('news/delete-image/{slug}', [NewsImageController::class, 'deleteImage'])->name('news.deleteImage');
    Route::resource('pages', PagesController::class);
    Route::resource('classification', ClassificationController::class);
    Route::resource('admin', AdminController::class)->only(['index', 'store', 'update']);

    // Assessment
    Route::resource('assesment', AssesmentController::class);
    Route::post('assessment/{assessment}/store-question-bank', [AssesmentQuestionBankController::class, 'storeAssessmentQuestionBank'])->name('assessment.store.question.bank');
    Route::get('assessment/{id_assessment_question_bank}/syncronize-questions', [AssesmentQuestionBankController::class, 'syncronizeQuestions'])->name('assessment.syncronize.question.bank');
    Route::get('assessment/{id_assessment_question_bank}/edit', [AssesmentQuestionBankController::class, 'edit'])->name('assessment.edit.question.bank');
    Route::put('assessment/{id_assessment_question_bank}/update', [AssesmentQuestionBankController::class, 'update'])->name('assessment.update.question.bank');
    Route::get('assessment/{id_assessment_question_bank}/delete', [AssesmentQuestionBankController::class, 'delete'])->name('assessment.delete.question.bank');
    Route::get('assesment/{assesment}/result', [AssesmentQuestionController::class, 'result'])->name('assesment.result');
    Route::get('assesment/{assesment}/clone', [AssesmentQuestionController::class, 'clone'])->name('assesment.clone');
    Route::get('assesmentresult', [AssesmentResultController::class, 'index'])->name('assesment.result.all');
    Route::get('assesment/result/{slug}', [AssesmentResultController::class, 'result'])->name('assesment.result.result');
    Route::post('assesment/changestatus', [AssesmentController::class, 'changestatus'])->name('assesment.changestatus');
    Route::get('assesment/{assesment}/newlink', [AssesmentController::class, 'newlink'])->name('assesment.newlink');
    Route::put('assesment/{assesment}/updatelink', [AssesmentController::class, 'updatelink'])->name('assesment.updatelink');
    Route::post('assesment/download', [DownloadController::class, 'assessmentdownload'])->name('assesment.result.download');
    Route::post('assesment/question/download', [DownloadController::class, 'assessmentquestiondownload'])->name('assesment.question.download');
    Route::get('assesment/wrong/{slug}/{id}', [AssesmentResultController::class, 'wrong'])->name('assesment.wrong');
    Route::get('assesment/assign-users/{slug}', [AssesmentController::class, 'assignUsersIndex'])->name('assesment.assign.users.index');
    Route::post('assesment/assign-users-process/{slug}', [AssesmentController::class, 'assignUserProcess'])->name('assesment.assign.users.process');

    // Assessment Questions
    Route::get('assesment/{assesment}/question', [AssesmentQuestionController::class, 'question'])->name('assesment.question');
    Route::post('assesment/{assesment}/store', [AssesmentQuestionController::class, 'store'])->name('assesment.question.store');
    Route::delete('assesment/delete/{assesment}/{id}', [AssesmentQuestionController::class, 'destroy'])->name('assesment.question.destroy');
    Route::get('assesment/edit/{assesment}/{id}', [AssesmentQuestionController::class, 'edit'])->name('assesment.question.edit');
    Route::put('assesment/update/{assesment}/{id}', [AssesmentQuestionController::class, 'update'])->name('assesment.question.update');
    Route::put('assement/delete-image/{id}', [AssesmentImageController::class, 'deleteImage'])->name('assesment.deleteImage');

    // Question Bank
    Route::get('question-bank', [QuestionBankController::class, 'index'])->name('question.bank.index');
    Route::get('question-bank/create', [QuestionBankController::class, 'create'])->name('question.bank.create');
    Route::post('question-bank/store', [QuestionBankController::class, 'store'])->name('question.bank.store');
    Route::get('question-bank/edit/{slug}', [QuestionBankController::class, 'edit'])->name('question.bank.edit');
    Route::put('question-bank/update/{id}', [QuestionBankController::class, 'update'])->name('question.bank.update');
    Route::delete('question-bank/delete/{id}', [QuestionBankController::class, 'destroy'])->name('question.bank.delete');
    Route::get('question-bank/clone/{slug}', [QuestionBankController::class, 'clone'])->name('question.bank.clone');

    // Questions of Questions Bank
    Route::get('question-bank/create-question/{question_bank_slug}', [QuestionController::class, 'create'])->name('question.bank.create.question');
    Route::post('question-bank/store-question/{question_bank_slug}', [QuestionController::class, 'store'])->name('question.bank.store.question');
    Route::get('question-bank/edit-question/{question_bank_slug}/{question_id}', [QuestionController::class, 'edit'])->name('question.bank.edit.question');
    Route::get('question-bank/delete-question-image/{question_id}', [QuestionController::class, 'deleteQuestionImage'])->name('question.bank.delete.question.image');
    Route::put('question-bank/update-question/{question_bank_slug}/{question_id}', [QuestionController::class, 'update'])->name('question.bank.update.question');
    Route::delete('question-bank/update-question/{question_bank_slug}/{question_id}', [QuestionController::class, 'destroy'])->name('question.bank.delete.question');
    Route::get('question-bank/download-question/{question_bank_id}', [QuestionController::class, 'downloadQuestions'])->name('question.bank.download.question');

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

    Route::get('learning', [LearningManageController::class, 'index'])->name('learning.manage.index');
    Route::get('learning/all', [LearningManageController::class, 'all'])->name('learning.manage.all');
    Route::get('learning/category/{slug}', [LearningManageController::class, 'category'])->name('learning.manage.category');
    Route::get('learning/content/{slug}', [LearningManageController::class, 'content'])->name('learning.manage.content');
    Route::get('learning/download', [DownloadController::class, 'learningdownload'])->name('manage.learning.download');


    Route::get('user/profile/{hash}', [ProfileUserController::class, 'index'])->name('user.manage.profile');
});

Route::group(['prefix' => 'news', 'middleware' => 'auth'], function () {
    Route::get('/', [NewsUserController::class, 'index'])->name('news.user.index');
    Route::get('/{slug}', [NewsUserController::class, 'view'])->name('news.user.view');
    Route::post('/{slug}/comment/store', [CommentController::class, 'storenews'])->name('news.comment.store');
});

Route::group(['prefix' => 'content', 'middleware' => 'auth'], function () {
    Route::get('/', [ContentUserController::class, 'index'])->name('content.user.index');
    Route::get('/{slug}', [ContentUserController::class, 'view'])->name('content.user.view');
    Route::get('category/{slug}', [ContentUserController::class, 'category'])->name('content.user.category');
    Route::put('progress/{slug}', [ContentUserController::class, 'progress'])->name('content.user.progress');
    Route::post('/{slug}/comment/store', [CommentController::class, 'storecontent'])->name('content.comment.store');
    Route::post('/{slug}/commentplaylist/store', [CommentController::class, 'storecontentplaylist'])->name('contentplaylist.comment.store');
});

Route::group(['prefix' => 'learning', 'middleware' => 'auth'], function () {
    Route::get('on-going', [LearningController::class, 'ongoing'])->name('learning.ongoing');
    Route::get('record', [LearningController::class, 'record'])->name('learning.record');
    Route::get('assesment', [LearningController::class, 'assesment'])->name('learning.assesment');
});

Route::group(['prefix' => 'assesment', 'middleware' => 'auth'], function () {
    Route::get('/', [AssesmentUserController::class, 'index'])->name('assesment.user.index');
    Route::group(['middleware' => 'prevent-back-button'], function () {
        Route::get('/{slug}', [AssesmentUserController::class, 'view'])->name('assesment.user.view');
        Route::post('/{slug}/store', [AssesmentUserController::class, 'store'])->name('assesment.user.store');
        Route::get('/{slug}/result', [AssesmentUserController::class, 'result'])->name('assesment.user.result');
        Route::post('/{slug}/autosave', [AssesmentUserController::class, 'autosave'])->name('assesment.user.autosave');
    });
});
Route::group(['prefix' => 'gallery', 'middleware' => 'auth'], function () {

    Route::get('/', [GalleryUserController::class, 'index'])->name('gallery.user.index');
    Route::get('/{slug}', [GalleryUserController::class, 'view'])->name('gallery.user.view');
    Route::post('/{slug}/comment/store', [CommentController::class, 'storeeventphoto'])->name('gallery.comment.store');
    // Route::get('/{slug}', [GalleryUserController::class, 'view'])->name('gallery.user.view');
});
Route::group(['prefix' => 'playlist', 'middleware' => 'auth'], function () {

    Route::get('/', [PlaylistUserController::class, 'index'])->name('playlist.user.index');
    Route::get('/{slug}/{vidslug}', [PlaylistUserController::class, 'view'])->name('playlist.user.view');
});
Route::get('/login', [LoginController::class, 'index'])->name('loginpage')->middleware('guest');
Route::post('/login', [LoginController::class, 'authenticate'])->name('authuser');
Route::get('/sso', [LoginController::class, 'checksso'])->name('checksso');
Route::post('/logout', [LoginController::class, 'logout'])->name('logoutpage');
