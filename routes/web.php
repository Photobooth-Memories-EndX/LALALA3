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
use App\Http\Controllers\LearningAssessmentController;
use App\Http\Controllers\LearningRecordExportController;
use App\Http\Controllers\LearningMonitoringController;
use App\Http\Controllers\LearningOngoingController;
use App\Http\Controllers\NewsUserController;
use App\Http\Controllers\AssesmentController;
use App\Http\Controllers\AssesmentEditScoreController;
use App\Http\Controllers\NewsImageController;
use App\Http\Controllers\PagesUserController;
use App\Http\Controllers\EventPhotoController;
use App\Http\Controllers\ContentUserController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GalleryUserController;
use App\Http\Controllers\ProfileUserController;
use App\Http\Controllers\PlaylistUserController;
use App\Http\Controllers\PlaylistProgressController;
use App\Http\Controllers\TrainingPlanController;
use App\Http\Controllers\AssesmentUserController;
use App\Http\Controllers\ContentDeleteController;
use App\Http\Controllers\AssesmentImageController;
use App\Http\Controllers\AssesmentQuestionBankController;
use App\Http\Controllers\LearningHourSyncController;
use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\LearningManageController;
use App\Http\Controllers\AssesmentResultController;
use App\Http\Controllers\AssesmentQuestionController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ManageDashboardController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\UserCertificateController;
use App\Http\Controllers\ManageRoleController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ManageAccessPermissionController;
use App\Http\Controllers\PlaylistDebugController;
use App\Http\Controllers\PlaylistCertificateDebugController;
use App\Http\Controllers\Manage\LearningHours\ContentUserLearningHourController;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'index'])->name('loginpage')->middleware('guest');
Route::get('/assessment-access', [LoginController::class, 'assessmentAccess'])->name('assessment.access')->middleware('guest');
Route::post('/login', [LoginController::class, 'authenticate'])->name('authuser');
Route::get('/sso', [LoginController::class, 'checksso'])->name('checksso');
Route::post('/logout', [LoginController::class, 'logout'])->name('logoutpage');

/*
|--------------------------------------------------------------------------
| User Area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeUserController::class, 'index'])->name('home.user.index');
    Route::get('/upcoming-deadline',[HomeUserController::class, 'upcomingDeadline'])->name('home.upcoming.deadline');
    Route::post('/hero-background', [HomeUserController::class, 'updateHeroBackground'])
        ->name('home.user.hero-background.update');
    Route::post('/hero-background/remove', [HomeUserController::class, 'removeHeroBackground'])
        ->name('home.user.hero-background.remove');


    Route::view('/trainingplan', 'user.trainingplan-user.index')->name('trainingplan.user.index');

    Route::post('/assessment-access-by-unique-code', [HomeUserController::class, 'accessAssessmentByUniqueCode'])
        ->name('home.user.access.assessment.by.unique.code');

    Route::get('/myprofile', [ProfileController::class, 'index'])->name('user.profile');
    Route::post('/myprofile', [ProfileController::class, 'update'])->name('user.updateprofile');
    Route::get('/myprofile/deleteavatar', [ProfileController::class, 'deleteavatar'])->name('user.deleteavatar');
    Route::get('/mycomment', [ProfileController::class, 'comment'])->name('user.comment');

    Route::get('/search/{searchtype}', [SearchController::class, 'index'])->name('search');
    Route::get('/api/search-autocomplete', [SearchController::class, 'autocomplete'])->name('api.search.autocomplete');

    Route::get('/change-password', [ProfileController::class, 'changePassword'])->name('user.changepassword');
    Route::post('/change-password', [ProfileController::class, 'updatePassword'])->name('user.updatepassword');

    Route::get('/my-learning', [LearningController::class, 'dashboard'])->name('user.learning.dashboard');
    Route::get('/my-learning/partial/{tab}', [LearningController::class, 'dashboardPartial'])->name('user.learning.dashboard.partial');
    Route::get('/my-learning/assessment/export', [LearningAssessmentController::class, 'download'])->name('user.learning.assessment.export');
    Route::get('/my-learning/learning-record/export', [LearningRecordExportController::class, 'download'])->name('user.learning.record.export');

    // Learning On Going — dua mode terpisah (fragment AJAX): Content & Playlist.
    Route::get('/my-learning/content', [LearningOngoingController::class, 'content'])->name('user.learning.ongoing.content');
    Route::get('/my-learning/playlist', [LearningOngoingController::class, 'playlist'])->name('user.learning.ongoing.playlist');

    // My Learning monitoring (role 3/4): tabel user scope + detail profile.
    Route::get('/my-learning/monitoring-user-datatable', [LearningMonitoringController::class, 'monitoringUserDatatable'])
        ->name('user.learning.monitoring.user.datatable');

    Route::prefix('my-learning/monitoring')->group(function () {
        Route::get('/{hash}', [LearningMonitoringController::class, 'monitoringProfile'])
            ->name('user.learning.monitoring.profile');
        Route::get('/{hash}/learning-datatable', [LearningMonitoringController::class, 'monitoringProfileLearningDatatable'])
            ->name('user.learning.monitoring.profile.learning.datatable');
        Route::get('/{hash}/assessment-datatable', [LearningMonitoringController::class, 'monitoringProfileAssessmentDatatable'])
            ->name('user.learning.monitoring.profile.assessment.datatable');
        Route::get('/{hash}/playlist-datatable', [LearningMonitoringController::class, 'monitoringProfilePlaylistDatatable'])
            ->name('user.learning.monitoring.profile.playlist.datatable');
    });

    Route::post('/comment/{id}/like', [CommentController::class, 'toggleLike'])->name('comment.like');
    Route::delete('/comments/{id}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

/*
|--------------------------------------------------------------------------
| Public Page
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'page'], function () {
    Route::get('/{slug}', [PagesUserController::class, 'view'])->name('pages.user.view');
});

/*
|--------------------------------------------------------------------------
| News User
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'news', 'middleware' => 'auth'], function () {
    Route::get('/', [NewsUserController::class, 'index'])->name('news.user.index');
    Route::get('/{slug}', [NewsUserController::class, 'view'])->name('news.user.view');
    Route::post('/{slug}/comment/store', [CommentController::class, 'storenews'])->name('news.comment.store');
});

/*
|--------------------------------------------------------------------------
| Content User
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'content', 'middleware' => 'auth'], function () {
    Route::get('/', [ContentUserController::class, 'index'])->name('content.user.index');
    Route::get('/{slug}', [ContentUserController::class, 'view'])->name('content.user.view');
    Route::post('progress/update', [ContentUserController::class, 'updateProgress'])->name('content.user.progress.update');
    Route::put('progress/{slug}', [ContentUserController::class, 'progress'])->name('content.user.progress');
    Route::post('/{slug}/bookmark', [ContentUserController::class, 'toggleBookmark'])->name('content.user.bookmark');
    Route::post('/{slug}/comment/store', [CommentController::class, 'storecontent'])->name('content.comment.store');
    Route::post('/{slug}/commentplaylist/store', [CommentController::class, 'storecontentplaylist'])
        ->name('contentplaylist.comment.store');
});

/*
|--------------------------------------------------------------------------
| Assessment User
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'assesment', 'middleware' => 'auth'], function () {
    Route::get('/', [AssesmentUserController::class, 'index'])->name('assesment.user.index');

    Route::group(['middleware' => 'prevent-back-button'], function () {
        Route::get('/{slug}', [AssesmentUserController::class, 'view'])->name('assesment.user.view');
        Route::post('/{slug}/store', [AssesmentUserController::class, 'store'])->name('assesment.user.store');
        Route::get('/{slug}/result', [AssesmentUserController::class, 'result'])->name('assesment.user.result');
        Route::post('/{slug}/autosave', [AssesmentUserController::class, 'autosave'])->name('assesment.user.autosave');
    });
});
Route::get('/assessment-hub', [AssesmentUserController::class, 'index'])->middleware('auth')->name('assessment.index');

/*
|--------------------------------------------------------------------------
| Gallery User
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'gallery', 'middleware' => 'auth'], function () {
    Route::get('/', [GalleryUserController::class, 'index'])->name('gallery.user.index');
    Route::get('/{slug}', [GalleryUserController::class, 'view'])->name('gallery.user.view');
    Route::post('/{slug}/comment/store', [CommentController::class, 'storeeventphoto'])->name('gallery.comment.store');
});

/*
|--------------------------------------------------------------------------
| Playlist User
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'playlist', 'middleware' => 'auth'], function () {
    Route::get('/', [PlaylistUserController::class, 'index'])->name('playlist.user.index');
    Route::post('/progress/update', [PlaylistProgressController::class, 'updateProgress'])->name('playlist.user.progress.update');
    Route::post('/{trainingplan}/debug-state', [PlaylistDebugController::class, 'update'])
        ->name('playlist.debug.update');
    Route::delete('/{trainingplan}/debug-state', [PlaylistDebugController::class, 'reset'])
        ->name('playlist.debug.reset');
    Route::get('/{trainingplan}/debug-certificate-preview', [PlaylistCertificateDebugController::class, 'show'])
        ->name('playlist.debug.certificate.preview');
    Route::delete('/{trainingplan}/debug-certificate-preview', [PlaylistCertificateDebugController::class, 'destroy'])
        ->name('playlist.debug.certificate.preview.exit');
    Route::get('/{slug}/{vidslug}', [PlaylistUserController::class, 'view'])->name('playlist.user.view');
});

/*
|--------------------------------------------------------------------------
| Certificate User
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/certificate/generate/{contentUser}', [UserCertificateController::class, 'generate'])
        ->name('certificate.generate');

    Route::post('/certificate/generate-setting/{certificateSetting}', [UserCertificateController::class, 'generateSetting'])
        ->name('certificate.generate.setting');

    Route::get('/certificate/{credentialId}/preview', [UserCertificateController::class, 'preview'])
        ->name('certificate.preview');

    Route::get('/certificate/{credentialId}/viewer-document', [UserCertificateController::class, 'viewerDocument'])
        ->name('certificate.viewer.document');

    Route::get('/certificate/{credentialId}/viewer', [UserCertificateController::class, 'viewer'])
        ->name('certificate.viewer');

    Route::get('/certificate/{credentialId}', [UserCertificateController::class, 'show'])
        ->name('certificate.show');

    Route::get('/certificate/{credentialId}/download', [UserCertificateController::class, 'download'])
        ->name('certificate.download');

    Route::get('/certificate/{credentialId}/download-pdf', [UserCertificateController::class, 'downloadPdf'])->name('certificate.download.pdf');

    Route::get('/certificate/{credentialId}/download-custom', [UserCertificateController::class, 'downloadCustom'])
        ->name('certificate.download.custom');
});

Route::get('/certificate/verify/{credentialId}', [UserCertificateController::class, 'verify'])
    ->name('certificate.verify');

/*
|--------------------------------------------------------------------------
| Manage LMS
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'manage', 'middleware' => 'auth'], function () {

    Route::get('/', [ManageDashboardController::class, 'index'])->name('manage.dashboard');
    Route::get('/', [ManageDashboardController::class, 'index'])->name('manage.index');

    Route::group(['middleware' => 'mpermission:admin_management'], function () {
        Route::resource('admin', AdminController::class)->only(['index', 'store', 'update']);
    });

    Route::group(['middleware' => 'mpermission:user_management'], function () {
        Route::resource('user', UserController::class);
        Route::get('user/{user}/detail', [UserController::class, 'detail'])->name('user.detail');
        Route::get('user-outsourcing/create', [UserController::class, 'createOutsourcing'])->name('manage.create.user.outsourcing');
        Route::post('user-outsourcing/store', [UserController::class, 'storeOutsourcing'])->name('manage.store.user.outsourcing');
        Route::put('user-outsourcing/{user}/update', [UserController::class, 'updateOutsourcing'])->name('manage.update.user.outsourcing');
        Route::put('user/{user}/resetpassword', [UserController::class, 'resetpassword'])->name('user.resetpassword');
        Route::post('user/changestatus', [UserController::class, 'changestatus'])->name('user.changestatus');
        Route::get('user-search', [UserController::class, 'searchUser'])->name('user.search');
        Route::get('user/get-employee/{id_emp}', [UserController::class, 'getEmployeeData'])->name('user.getEmployeeData');
        Route::get('user/profile/{hash}', [ProfileUserController::class, 'index'])->name('user.manage.profile');
    });

    Route::group(['middleware' => 'mpermission:certificate_setting'], function () {
        Route::get('certificate-setting', [CertificateController::class, 'index'])->name('certificate.setting.index');
        Route::get('certificate-setting/datatable', [CertificateController::class, 'datatable'])->name('certificate.setting.datatable');
        Route::post('certificate-setting', [CertificateController::class, 'store'])->name('certificate.setting.store');
        Route::put('certificate-setting/{certificateSetting}', [CertificateController::class, 'update'])->name('certificate.setting.update');
        Route::delete('certificate-setting/{certificateSetting}', [CertificateController::class, 'destroy'])->name('certificate.setting.destroy');
        Route::get('certificate-setting/ajax/items', [CertificateController::class, 'getItemsByType'])->name('certificate.setting.ajax.items');
        Route::get('certificate-setting/ajax/assessments', [CertificateController::class, 'getAssessmentsByType'])->name('certificate.setting.ajax.assessments');
        Route::get('certificate-setting/{certificateSetting}/preview-template', [CertificateController::class, 'previewTemplate'])->name('certificate.setting.preview.template');
        Route::get('certificate-setting/{certificateSetting}/preview-generated', [CertificateController::class, 'previewGenerated'])->name('certificate.setting.preview.generated');
        Route::get('certificate-setting/{certificateSetting}/preview-custom-template', [CertificateController::class, 'previewCustomTemplate'])->name('certificate.setting.preview.custom-template');

    });

    Route::group(['middleware' => 'mpermission:news_announcement'], function () {
        Route::resource('news', NewsController::class);
        Route::get('news', [NewsController::class, 'index'])->name('manage.news.index');
        Route::put('news/delete-image/{slug}', [NewsImageController::class, 'deleteImage'])->name('news.deleteImage');
    });

    Route::group(['middleware' => 'mpermission:page_management'], function () {
        Route::resource('pages', PagesController::class);
    });

    Route::group(['middleware' => 'mpermission:category_classification'], function () {
        Route::resource('classification', ClassificationController::class);
    });

    Route::group(['middleware' => 'mpermission:assessment_management'], function () {
        Route::resource('assesment', AssesmentController::class);
        Route::post('assessment/{assessment}/store-question-bank', [AssesmentQuestionBankController::class, 'storeAssessmentQuestionBank'])->name('assessment.store.question.bank');
        Route::get('assessment/{id_assessment_question_bank}/syncronize-questions', [AssesmentQuestionBankController::class, 'syncronizeQuestions'])->name('assessment.syncronize.question.bank');
        Route::get('assessment/{id_assessment_question_bank}/edit', [AssesmentQuestionBankController::class, 'edit'])->name('assessment.edit.question.bank');
        Route::put('assessment/{id_assessment_question_bank}/update', [AssesmentQuestionBankController::class, 'update'])->name('assessment.update.question.bank');
        Route::get('assessment/{id_assessment_question_bank}/delete', [AssesmentQuestionBankController::class, 'delete'])->name('assessment.delete.question.bank');
        Route::get('assesment/{assesment}/result', [AssesmentQuestionController::class, 'result'])->name('assesment.result');
        Route::delete('assesment/{assesment}/result/{id}', [AssesmentQuestionController::class, 'destroyResult'])->name('assesment.result.destroy');
        Route::match(['put', 'patch', 'post'], 'assesment/{assesment}/result/{id}/score', [AssesmentQuestionController::class, 'updateScore'])->name('assesment.result.update-score');
        Route::match(['get', 'post'], 'assesment/{assesment}/clone', [AssesmentQuestionController::class, 'clone'])->name('assesment.clone');
        Route::match(['get', 'post'], 'assesment/{assesment}/duplicate', [AssesmentQuestionController::class, 'clone'])->name('assesment.duplicate');
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
        Route::delete('assesment/delete-assigned-user/{id}', [AssesmentController::class, 'deleteAssignedUser'])->name('assesment.assign.users.delete');

        Route::group(['prefix' => 'assessment/edit-score'], function () {
            Route::get('/', [AssesmentEditScoreController::class, 'index'])->name('assesment.edit.score.index');
            Route::post('/update-score', [AssesmentEditScoreController::class, 'updateScore'])->name('assesment.edit.score.update');
        });

        Route::get('assesment/{assesment}/question', [AssesmentQuestionController::class, 'question'])->name('assesment.question');
        Route::post('assesment/{assesment}/store', [AssesmentQuestionController::class, 'store'])->name('assesment.question.store');
        Route::delete('assesment/delete/{assesment}/{id}', [AssesmentQuestionController::class, 'destroy'])->name('assesment.question.destroy');
        Route::get('assesment/edit/{assesment}/{id}', [AssesmentQuestionController::class, 'edit'])->name('assesment.question.edit');
        Route::put('assesment/update/{assesment}/{id}', [AssesmentQuestionController::class, 'update'])->name('assesment.question.update');
        Route::put('assement/delete-image/{id}', [AssesmentImageController::class, 'deleteImage'])->name('assesment.deleteImage');
    });

    Route::group(['middleware' => 'mpermission:question_bank'], function () {
        Route::get('question-bank', [QuestionBankController::class, 'index'])->name('question.bank.index');
        Route::get('question-bank/create', [QuestionBankController::class, 'create'])->name('question.bank.create');
        Route::post('question-bank/store', [QuestionBankController::class, 'store'])->name('question.bank.store');
        Route::get('question-bank/edit/{slug}', [QuestionBankController::class, 'edit'])->name('question.bank.edit');
        Route::put('question-bank/update/{id}', [QuestionBankController::class, 'update'])->name('question.bank.update');
        Route::delete('question-bank/delete/{id}', [QuestionBankController::class, 'destroy'])->name('question.bank.delete');
        Route::get('question-bank/clone/{slug}', [QuestionBankController::class, 'clone'])->name('question.bank.clone');

        Route::get('question-bank/create-question/{question_bank_slug}', [QuestionController::class, 'create'])->name('question.bank.create.question');
        Route::post('question-bank/store-question/{question_bank_slug}', [QuestionController::class, 'store'])->name('question.bank.store.question');
        Route::get('question-bank/edit-question/{question_bank_slug}/{question_id}', [QuestionController::class, 'edit'])->name('question.bank.edit.question');
        Route::get('question-bank/delete-question-image/{question_id}', [QuestionController::class, 'deleteQuestionImage'])->name('question.bank.delete.question.image');
        Route::put('question-bank/update-question/{question_bank_slug}/{question_id}', [QuestionController::class, 'update'])->name('question.bank.update.question');
        Route::delete('question-bank/update-question/{question_bank_slug}/{question_id}', [QuestionController::class, 'destroy'])->name('question.bank.delete.question');
        Route::get('question-bank/download-question/{question_bank_id}', [QuestionController::class, 'downloadQuestions'])->name('question.bank.download.question');
    });

    Route::group(['middleware' => 'mpermission:content_repository'], function () {
        Route::resource('content', ContentController::class);
        Route::post('content/thumbnail/upload', [ContentController::class, 'uploadthumbnailajax'])->name('content.uploadthumbnailajax');
        Route::get('content/thumbnail/upload/delete', [ContentController::class, 'deletethumbnailajax'])->name('content.deletethumbnailajax');
        Route::post('content/create/upload', [ContentController::class, 'uploadfileajax'])->name('content.uploadfileajax');
        Route::get('content/create/upload/delete', [ContentController::class, 'deletefileajax'])->name('content.deletefileajax');
        Route::get('content/list/ongoing', [ContentController::class, 'ongoingcontent'])->name('content.ongoing');
        Route::put('content/delete/{slug}', [ContentDeleteController::class, 'deletecontentajax'])->name('contentdelete.deletecontentajax');
        Route::put('content/delete-thumb/{slug}', [ContentDeleteController::class, 'deletethumbajax'])->name('contentdelete.deletethumbajax');
        Route::post('content/assign', [ContentController::class, 'assign'])->name('content.assign');
        Route::post('content/{content}/synchronize-learning-hours', [ContentController::class, 'synchronizeLearningHours'])->name('content.synchronize-learning-hours');
        Route::get('content/ajax/document-pdf-list', [ContentController::class, 'documentPdfList'])->name('content.document-pdf-list');
        Route::post('content/ajax/sync-total-page', [ContentController::class, 'syncTotalPage'])->name('content.sync-total-page');
    });

    Route::group(['middleware' => 'mpermission:playlist_learning_path'], function () {
        Route::get('playlist/order/{slug}', [TrainingPlanController::class, 'order'])->name('playlist.order');
        Route::get('playlist-thumbnail-test', [TrainingPlanController::class, 'thumbnailTest'])->name('playlist.thumbnailTest');
        Route::post('playlist/{trainingplan?}/synchronize-learning-hours', [TrainingPlanController::class, 'synchronizeLearningHours'])->name('playlist.synchronize-learning-hours');
        Route::resource('playlist', TrainingPlanController::class)->parameters(['playlist' => 'trainingplan']);
    });

    Route::post('learning-hours/sync', [LearningHourSyncController::class, 'sync'])->name('learning-hours.sync');

    Route::group(['middleware' => 'mpermission:event_documentation'], function () {
        Route::resource('eventphoto', EventPhotoController::class);
        Route::get('eventphoto', [EventPhotoController::class, 'index'])->name('manage.eventphoto.index');
        Route::post('eventphoto/create/upload', [EventPhotoController::class, 'uploadfileajax'])->name('eventphoto.uploadfileajax');
        Route::get('eventphoto/create/upload/delete', [EventPhotoController::class, 'deletefileajax'])->name('eventphoto.deletefileajax');
        Route::post('eventphoto/delete', [EventPhotoController::class, 'deleteImage'])->name('eventphoto.deleteImage');
        Route::get('/gallery/search', [GalleryController::class, 'search'])->name('gallery.search');
    });

    Route::group(['middleware' => 'mpermission:learning_management'], function () {
        Route::get('learning/datatable', [LearningManageController::class, 'datatable'])->name('learning.manage.datatable');
        Route::get('learning', [LearningManageController::class, 'index'])->name('learning.manage.index');
        Route::get('learning/all', [LearningManageController::class, 'all'])->name('learning.manage.all');
        Route::get('learning/category/{slug}', [LearningManageController::class, 'category'])->name('learning.manage.category');
        Route::get('learning/content/{slug}', [LearningManageController::class, 'content'])->name('learning.manage.content');
        Route::get('learning/download', [DownloadController::class, 'learningdownload'])->name('manage.learning.download');
    });

    Route::group(['middleware' => 'mpermission:learning_hours_management'], function () {
        Route::prefix('learning-hours/content-user')->name('manage.learning-hours.content-user.')->group(function () {
            Route::get('/', [ContentUserLearningHourController::class, 'index'])->name('index');
            Route::get('/datatable', [ContentUserLearningHourController::class, 'datatable'])->name('datatable');
            Route::get('/statistics', [ContentUserLearningHourController::class, 'statistics'])->name('statistics');
            Route::post('/calculate-selected', [ContentUserLearningHourController::class, 'calculateSelected'])->name('calculate-selected');
            Route::post('/recalculate-selected', [ContentUserLearningHourController::class, 'recalculateSelected'])->name('recalculate-selected');
            Route::post('/batch/start', [ContentUserLearningHourController::class, 'startBatch'])->name('batch.start');
            Route::post('/batch/process', [ContentUserLearningHourController::class, 'processBatch'])->name('batch.process');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Manage Role
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'manage/role', 'middleware' => ['auth', 'superadmin']], function () {
    Route::get('/', [ManageRoleController::class, 'index'])->name('manage.role.index');
    Route::put('/{user}', [ManageRoleController::class, 'update'])->name('manage.role.update');
});

/*
|--------------------------------------------------------------------------
| Monitoring
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'monitoring', 'middleware' => ['auth', 'mpermission:monitoring']], function () {
    Route::get('/', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/datatable', [MonitoringController::class, 'datatable'])->name('monitoring.datatable');
    Route::get('/profile/{hash}', [MonitoringController::class, 'profile'])->name('monitoring.profile');

    Route::get('/profile/{hash}/learning-datatable', [MonitoringController::class, 'learningDatatable'])->name('monitoring.profile.learning.datatable');
    Route::get('/profile/{hash}/assessment-datatable', [MonitoringController::class, 'assessmentDatatable'])->name('monitoring.profile.assessment.datatable');
    Route::get('/profile/{hash}/playlist-datatable', [MonitoringController::class, 'playlistDatatable'])->name('monitoring.profile.playlist.datatable');

    Route::get('/group/{type}/{value}', [MonitoringController::class, 'group'])->name('monitoring.group');
    Route::get('/group/{type}/{value}/datatable', [MonitoringController::class, 'groupDatatable'])->name('monitoring.group.datatable');
});

/*
|--------------------------------------------------------------------------
| Manage Access Permission
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'manage/access-permission', 'middleware' => ['auth', 'superadmin']], function () {
    Route::get('/', [ManageAccessPermissionController::class, 'index'])
        ->name('manage.access-permission.index');

    Route::get('/{role}/edit', [ManageAccessPermissionController::class, 'edit'])
        ->name('manage.access-permission.edit');

    Route::put('/{role}', [ManageAccessPermissionController::class, 'update'])
        ->name('manage.access-permission.update');
});
