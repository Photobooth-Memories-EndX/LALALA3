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
use App\Http\Controllers\LearningRecordController;
use App\Http\Controllers\LearningMonitoringController;
use App\Http\Controllers\LearningOngoingController;
use App\Http\Controllers\NewsUserController;
use App\Http\Controllers\AssesmentController;
use App\Http\Controllers\AssesmentPreviewController;
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
use App\Http\Controllers\OfflineTrainingController;
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
use App\Http\Controllers\EngagementTimeController;
use App\Http\Controllers\Manage\LearningHours\ContentUserLearningHourController;
use App\Http\Controllers\Manage\LearningHours\PlaylistContentUserLearningHourController;
use App\Http\Controllers\Manage\LearningHours\LearningHoursLandingController;
use App\Http\Controllers\Manage\LearningHours\PlaylistProgressDryRunController;
use App\Http\Controllers\Manage\Record\RecordOnlineController;
use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Http\Controllers\Manage\Record\RecordAllController;
use App\Http\Controllers\Manage\Record\RecordExportController;
use App\Http\Controllers\Manage\Record\OfflineTrainingOptionController;
use App\Http\Controllers\Manage\Record\OfflineTrainingImportController;
use App\Http\Controllers\Manage\Record\Offline\PtkpImportController;

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


    Route::get('/trainingplan', [OfflineTrainingController::class, 'userIndex'])->name('trainingplan.user.index');
    Route::get('/trainingplan/history/download', [OfflineTrainingController::class, 'downloadMyHistory'])->name('trainingplan.my-history.download');
    Route::get('/training-schedule/{id}/download-syllabus', [OfflineTrainingController::class, 'downloadSyllabusPdf'])->name('training.download-syllabus');
    Route::post('/trainingplan/{training}/register', [OfflineTrainingController::class, 'register'])->name('trainingplan.register');
    Route::post('/trainingplan/{registration}/cancel', [OfflineTrainingController::class, 'cancelRegistration'])->name('trainingplan.cancel');

    Route::post('/assessment-access-by-unique-code', [HomeUserController::class, 'accessAssessmentByUniqueCode'])
        ->name('home.user.access.assessment.by.unique.code');

    Route::get('/myprofile', [ProfileController::class, 'index'])->name('user.profile');
    Route::post('/myprofile', [ProfileController::class, 'update'])->name('user.updateprofile');
    Route::get('/myprofile/deleteavatar', [ProfileController::class, 'deleteavatar'])->name('user.deleteavatar');
    Route::get('/mycomment', [ProfileController::class, 'comment'])->name('user.comment');

    Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
    Route::get('/search/{searchtype?}', [SearchController::class, 'index'])->name('search');
    Route::get('/api/search-autocomplete', [SearchController::class, 'autocomplete'])->name('api.search.autocomplete');

    Route::get('/change-password', [ProfileController::class, 'changePassword'])->name('user.changepassword');
    Route::post('/change-password', [ProfileController::class, 'updatePassword'])->name('user.updatepassword');

    Route::get('/my-learning', [LearningController::class, 'dashboard'])->name('user.learning.dashboard');
    Route::get('/my-learning/partial/{tab}', [LearningController::class, 'dashboardPartial'])->name('user.learning.dashboard.partial');
    Route::get('/my-learning/assessment/export', [LearningAssessmentController::class, 'download'])->name('user.learning.assessment.export');
    Route::get('/my-learning/learning-record/export', [LearningRecordExportController::class, 'download'])->name('user.learning.record.export');
    Route::get('/my-learning/learning-record/detail/{source}/{id}', [LearningRecordController::class, 'detail'])->name('user.learning.record.detail');

    // Learning On Going — dua mode terpisah (fragment AJAX): Content & Playlist.
    Route::get('/my-learning/ongoing/export', [LearningOngoingController::class, 'download'])->name('user.learning.ongoing.export');
    Route::get('/my-learning/content', [LearningOngoingController::class, 'content'])->name('user.learning.ongoing.content');
    Route::get('/my-learning/playlist', [LearningOngoingController::class, 'playlist'])->name('user.learning.ongoing.playlist');
    Route::get('/user/learning/ongoing/search-autocomplete', [LearningOngoingController::class, 'autocomplete'])->name('user.learning.ongoing.autocomplete');
    Route::get('/my-learning/ongoing/search-autocomplete', [LearningOngoingController::class, 'autocomplete'])->name('user.learning.ongoing.autocomplete.alias');

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

    // Total Engagement Time Heartbeat (restricted to the 2 designated learning items)
    Route::post('/engagement-time/heartbeat', [EngagementTimeController::class, 'heartbeat'])->name('engagement-time.heartbeat');
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
Route::get('/user/news/search-autocomplete', [NewsUserController::class, 'autocomplete'])->middleware('auth')->name('user.news.autocomplete');
Route::group(['prefix' => 'news', 'middleware' => 'auth'], function () {
    Route::get('/', [NewsUserController::class, 'index'])->name('news.user.index');
    Route::get('/search-autocomplete', [NewsUserController::class, 'autocomplete'])->name('news.user.autocomplete');
    Route::get('/{slug}', [NewsUserController::class, 'view'])->name('news.user.view');
    Route::post('/{slug}/comment/store', [CommentController::class, 'storenews'])->name('news.comment.store');
});

/*
|--------------------------------------------------------------------------
| Content User
|--------------------------------------------------------------------------
*/
Route::get('/user/content/search-autocomplete', [ContentUserController::class, 'autocomplete'])->middleware('auth')->name('user.content.autocomplete');
Route::group(['prefix' => 'content', 'middleware' => 'auth'], function () {
    Route::get('/', [ContentUserController::class, 'index'])->name('content.user.index');
    Route::get('/search-autocomplete', [ContentUserController::class, 'autocomplete'])->name('content.user.autocomplete');
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
Route::get('/user/assessment/search-autocomplete', [AssesmentUserController::class, 'autocomplete'])->middleware('auth')->name('user.assessment.autocomplete');
Route::group(['prefix' => 'assesment', 'middleware' => 'auth'], function () {
    Route::get('/', [AssesmentUserController::class, 'index'])->name('assesment.user.index');
    Route::get('/search-autocomplete', [AssesmentUserController::class, 'autocomplete'])->name('assesment.user.autocomplete');

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
Route::get('/user/gallery/search-autocomplete', [GalleryUserController::class, 'autocomplete'])->middleware('auth')->name('user.gallery.autocomplete');
Route::group(['prefix' => 'gallery', 'middleware' => 'auth'], function () {
    Route::get('/', [GalleryUserController::class, 'index'])->name('gallery.user.index');
    Route::get('/search-autocomplete', [GalleryUserController::class, 'autocomplete'])->name('gallery.user.autocomplete');
    Route::get('/{slug}', [GalleryUserController::class, 'view'])->name('gallery.user.view');
    Route::post('/{slug}/comment/store', [CommentController::class, 'storeeventphoto'])->name('gallery.comment.store');
});

/*
|--------------------------------------------------------------------------
| Playlist User
|--------------------------------------------------------------------------
*/
Route::get('/user/playlist/search-autocomplete', [PlaylistUserController::class, 'autocomplete'])->middleware('auth')->name('user.playlist.autocomplete');
Route::group(['prefix' => 'playlist', 'middleware' => 'auth'], function () {
    Route::get('/', [PlaylistUserController::class, 'index'])->name('playlist.user.index');
    Route::get('/search-autocomplete', [PlaylistUserController::class, 'autocomplete'])->name('playlist.user.autocomplete');
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
    Route::get('/{slug}', [PlaylistUserController::class, 'show'])->name('user.playlist.show');
});

/*
|--------------------------------------------------------------------------
| Certificate User
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/user/certificate/search-autocomplete', [CertificateController::class, 'autocomplete'])
        ->name('user.certificate.autocomplete');
    Route::get('/certificate/search-autocomplete', [CertificateController::class, 'autocomplete'])
        ->name('certificate.autocomplete');

    Route::get('/certificate/generate/{contentUser}', [UserCertificateController::class, 'generate'])
        ->name('certificate.generate');

    Route::post('/certificate/generate-setting/{certificateSetting}', [UserCertificateController::class, 'generateSetting'])
        ->name('certificate.generate.setting');

    Route::get('/certificate/generate-offline/{offlineTrainingUser}', [UserCertificateController::class, 'generateOffline'])
        ->name('certificate.generate.offline');

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

    Route::get('/', [ManageDashboardController::class, 'index'])->name('manage.index');
    Route::get('/dashboard', [ManageDashboardController::class, 'index'])->name('manage.dashboard');
    Route::get('/training-schedule', [OfflineTrainingController::class, 'index'])->name('manage.training-schedule.index');
    Route::get('/training-schedule/export', [OfflineTrainingController::class, 'exportExcel'])->name('manage.training.export');
    Route::get('/training-schedule/export/excel', [OfflineTrainingController::class, 'exportExcel'])->name('manage.training.export.excel');
    Route::get('/training-schedule/export/pdf', [OfflineTrainingController::class, 'exportPdf'])->name('manage.training.export.pdf');
    Route::get('/training-schedule/export/jpg', [OfflineTrainingController::class, 'exportJpg'])->name('manage.training.export.jpg');
    Route::get('/training-schedule/export-roster', [OfflineTrainingController::class, 'exportRoster'])->name('manage.roster.export');
    Route::get('/training-schedule/create', [OfflineTrainingController::class, 'create'])->name('manage.training-schedule.create');
    Route::post('/training-schedule', [OfflineTrainingController::class, 'store'])->name('manage.training-schedule.store');
    Route::post('/training-schedule/options', [OfflineTrainingController::class, 'storeOption'])->name('manage.training-schedule.options.store');
    Route::get('/training-schedule/{id}/edit', [OfflineTrainingController::class, 'edit'])->name('training-schedule.edit');
    Route::put('/training-schedule/{id}', [OfflineTrainingController::class, 'update'])->name('manage.training-schedule.update');
    Route::delete('/training-schedule/{id}', [OfflineTrainingController::class, 'destroy'])->name('manage.training-schedule.destroy');
    Route::get('/training-schedule/{id}/participants', [OfflineTrainingController::class, 'participants'])->name('manage.training-schedule.participants');
    Route::patch('/training-schedule/registrations/{registration}/status', [OfflineTrainingController::class, 'updateRegistrationStatus'])->name('manage.training-schedule.registrations.status');
    Route::post('/training-schedule/registrations/{registration}/complete', [OfflineTrainingController::class, 'completeRegistration'])->name('manage.training-schedule.registrations.complete');

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
        Route::get('user/profile/{hash}/learning-datatable', [ProfileUserController::class, 'learningDatatable'])->name('user.manage.profile.learning.datatable');
        Route::get('user/profile/{hash}/export', [ProfileUserController::class, 'exportExcel'])->name('user.manage.profile.export.excel');
        Route::get('user/profile/{hash}/export/csv', [ProfileUserController::class, 'exportCsv'])->name('user.manage.profile.export.csv');
        Route::get('user/profile/{hash}/export/pdf', [ProfileUserController::class, 'exportPdf'])->name('user.manage.profile.export.pdf');
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
        Route::get('news/search-autocomplete', [NewsController::class, 'autocompleteSearch'])->name('manage.news.autocomplete');
        Route::get('news/autocomplete', [NewsController::class, 'autocompleteSearch']);
        Route::resource('news', NewsController::class);
        Route::get('news', [NewsController::class, 'index'])->name('manage.news.index');
        Route::put('news/delete-image/{slug}', [NewsImageController::class, 'deleteImage'])->name('news.deleteImage');
    });

    Route::group(['middleware' => 'mpermission:page_management'], function () {
        Route::resource('pages', PagesController::class);
    });

    Route::group(['middleware' => 'mpermission:category_classification'], function () {
        Route::get('classification/search-autocomplete', [ClassificationController::class, 'autocompleteSearch'])->name('manage.classification.autocomplete');
        Route::get('classification/autocomplete', [ClassificationController::class, 'autocompleteSearch']);

        // Option Management routes
        Route::prefix('classification/options')->name('manage.classification.options.')->group(function () {
            Route::post('/', [ClassificationController::class, 'storeOption'])->name('store');
            Route::get('{id}', [ClassificationController::class, 'getOption'])->name('show');
            Route::put('{id}', [ClassificationController::class, 'updateOption'])->name('update');
            Route::patch('{id}/toggle-status', [ClassificationController::class, 'toggleOptionStatus'])->name('toggle-status');
            Route::get('{id}/usage', [ClassificationController::class, 'checkOptionUsage'])->name('usage');
            Route::delete('{id}', [ClassificationController::class, 'destroyOption'])->name('destroy');
            Route::get('parents/{type}', [ClassificationController::class, 'getParentOptions'])->name('parents');
        });

        Route::resource('classification', ClassificationController::class)->names([
            'index' => 'manage.classification.index',
            'create' => 'manage.classification.create',
            'store' => 'manage.classification.store',
            'show' => 'manage.classification.show',
            'edit' => 'manage.classification.edit',
            'update' => 'manage.classification.update',
            'destroy' => 'manage.classification.destroy',
        ]);
    });

    Route::group(['middleware' => 'mpermission:assessment_management'], function () {
        Route::get('assessment/add', [AssesmentController::class, 'create'])->name('assessment.add');
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
        Route::get('assesment/{assesment}/preview', [AssesmentPreviewController::class, 'preview'])->name('assesment.preview');
        Route::post('assesment/{assesment}/preview/result', [AssesmentPreviewController::class, 'result'])->name('assesment.preview.result');
        Route::get('assesmentresult', [AssesmentResultController::class, 'index'])->name('assesment.result.all');
        Route::get('assesment/result/{slug}', [AssesmentResultController::class, 'result'])->name('assesment.result.result');
        Route::get('assesment/bulk-duplicate/sources', [AssesmentController::class, 'getActiveContentSources'])->name('assesment.bulk-duplicate.sources');
        Route::get('assesment/playlist/{id}/contents', [AssesmentController::class, 'getPlaylistContents'])->name('assesment.playlist.contents');
        Route::get('assessment/playlist/{id}/contents', [AssesmentController::class, 'getPlaylistContents']);
        Route::post('assesment/bulk-duplicate/preview', [AssesmentController::class, 'previewBulkDuplicate'])->name('assesment.bulk-duplicate.preview');
        Route::post('assesment/bulk-duplicate', [AssesmentController::class, 'bulkDuplicate'])->name('assesment.bulk-duplicate');
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
        Route::get('question-bank/search-autocomplete', [QuestionBankController::class, 'autocompleteSearch'])->name('manage.question.bank.autocomplete');
        Route::get('question-bank/autocomplete', [QuestionBankController::class, 'autocompleteSearch']);
        Route::get('question-bank', [QuestionBankController::class, 'index'])->name('question.bank.index');
        Route::get('question-bank/index', [QuestionBankController::class, 'index'])->name('manage.question-bank.index');
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
        Route::get('content/search-autocomplete', [ContentController::class, 'autocompleteSearch'])->name('manage.content.autocomplete');
        Route::get('content/autocomplete', [ContentController::class, 'autocompleteSearch']);
        Route::get('content/{content}/details', [ContentController::class, 'details'])->name('content.details');
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
        Route::post('learning-hours/sync', [LearningHourSyncController::class, 'sync'])->name('learning-hours.sync');
    });

    Route::group(['middleware' => 'mpermission:playlist_learning_path'], function () {
        Route::get('playlist/search-autocomplete', [TrainingPlanController::class, 'autocompleteSearch'])->name('manage.trainingplan.autocomplete');
        Route::get('playlist/{playlist}/order', [TrainingPlanController::class, 'order'])->name('manage.playlist.order');
        Route::get('playlist-thumbnail-test', [TrainingPlanController::class, 'thumbnailTest'])->name('playlist.thumbnailTest');
        Route::post('playlist/synchronize-durations', [TrainingPlanController::class, 'synchronizeDurations'])->name('manage.playlist.synchronize-durations');
        Route::post('playlist/synchronize-learning-hours', [TrainingPlanController::class, 'synchronizeAllLearningHours'])->name('manage.playlist.synchronize-learning-hours');
        Route::get('playlist/{id}/details', [TrainingPlanController::class, 'details'])->name('manage.playlist.details');
        Route::post('playlist/{trainingplan?}/synchronize-learning-hours', [TrainingPlanController::class, 'synchronizeLearningHours'])->name('playlist.synchronize-learning-hours');
        Route::resource('playlist', TrainingPlanController::class)->parameters(['playlist' => 'trainingplan']);
        Route::get('playlist/create', [TrainingPlanController::class, 'create'])->name('playlist.create');
        Route::get('playlist/new', [TrainingPlanController::class, 'create'])->name('manage.playlist.create');
        Route::get('playlist/index', [TrainingPlanController::class, 'index'])->name('manage.playlist.index');
    });

    Route::group(['middleware' => 'mpermission:event_documentation'], function () {
        Route::get('eventphoto/search-autocomplete', [EventPhotoController::class, 'autocompleteSearch'])->name('manage.eventphoto.autocomplete');
        Route::get('eventphoto/autocomplete', [EventPhotoController::class, 'autocompleteSearch']);
        Route::resource('eventphoto', EventPhotoController::class);
        Route::get('eventphoto', [EventPhotoController::class, 'index'])->name('manage.eventphoto.index');
        Route::post('eventphoto/create/upload', [EventPhotoController::class, 'uploadfileajax'])->name('eventphoto.uploadfileajax');
        Route::get('eventphoto/create/upload/delete', [EventPhotoController::class, 'deletefileajax'])->name('eventphoto.deletefileajax');
        Route::post('eventphoto/delete', [EventPhotoController::class, 'deleteImage'])->name('eventphoto.deleteImage');
        Route::get('/gallery/search', [GalleryController::class, 'search'])->name('gallery.search');
    });

    Route::group(['middleware' => 'mpermission:learning_management'], function () {
        Route::get('learning/search-employees', [LearningManageController::class, 'searchEmployees'])->name('learning.manage.search-employees');
        Route::get('learning/search-titles', [LearningManageController::class, 'searchTitles'])->name('learning.manage.search-titles');
        Route::get('learning/datatable', [LearningManageController::class, 'datatable'])->name('learning.manage.datatable');
        Route::get('learning', [LearningManageController::class, 'index'])->name('learning.manage.index');
        Route::get('learning/all', [LearningManageController::class, 'all'])->name('learning.manage.all');
        Route::get('learning/category/{slug}', [LearningManageController::class, 'category'])->name('learning.manage.category');
        Route::get('learning/content/{slug}', [LearningManageController::class, 'content'])->name('learning.manage.content');
        Route::get('learning/playlist/{id}', [LearningManageController::class, 'playlistDetail'])->name('learning.manage.playlist');
        Route::get('learning/record-detail/{type}/{id}', [LearningManageController::class, 'recordDetail'])->name('learning.manage.record-detail');
        Route::match(['put', 'patch'], 'learning/record/{type}/{id}', [LearningManageController::class, 'updateRecord'])->name('learning.manage.record.update');
        Route::post('learning/record/{type}/{id}/reset', [LearningManageController::class, 'resetRecord'])->name('learning.manage.record.reset');
        Route::get('learning/download', [LearningManageController::class, 'download'])->name('manage.learning.download');
        Route::get('learning/download/pdf', [LearningManageController::class, 'downloadPdf'])->name('manage.learning.download.pdf');
        Route::get('learning/download/csv', [LearningManageController::class, 'downloadCsv'])->name('manage.learning.download.csv');
    });

    Route::group(['middleware' => 'mpermission:record_management'], function () {
        Route::prefix('record')->name('manage.record.')->group(function () {
            Route::get('online', [RecordOnlineController::class, 'index'])->name('online');
            Route::get('online/course/{id?}', [\App\Http\Controllers\ManageRecordController::class, 'onlineCourse'])->name('online.course');
            Route::get('online/ajax/summary', [RecordOnlineController::class, 'ajaxSummary'])->name('online.ajax.summary');
            Route::get('online/ajax/classification', [RecordOnlineController::class, 'ajaxClassification'])->name('online.ajax.classification');
            Route::get('online/datatable', [RecordOnlineController::class, 'datatable'])->name('online.datatable');
            Route::get('online/search-employees', [RecordOnlineController::class, 'searchEmployees'])->name('online.search-employees');
            Route::get('online/search-titles', [RecordOnlineController::class, 'searchTitles'])->name('online.search-titles');
            Route::get('online/export', [RecordOnlineController::class, 'export'])->name('online.export');
            Route::get('online/export/pdf', [RecordOnlineController::class, 'exportPdf'])->name('online.export.pdf');
            Route::get('online/export/csv', [RecordOnlineController::class, 'exportCsv'])->name('online.export.csv');
            Route::get('online/download', [RecordOnlineController::class, 'download'])->name('online.download');
            Route::get('online/{type}/{id}/detail', [RecordOnlineController::class, 'detail'])->name('online.detail');
            Route::delete('online/{type}/{id}', [RecordOnlineController::class, 'destroy'])->name('online.destroy');

            Route::get('offline', [RecordOfflineController::class, 'index'])->name('offline');
            Route::get('offline/ajax/summary', [RecordOfflineController::class, 'ajaxSummary'])->name('offline.ajax.summary');
            Route::get('offline/ajax/classification', [RecordOfflineController::class, 'ajaxClassification'])->name('offline.ajax.classification');
            Route::get('offline/classification/{classification}', [RecordOfflineController::class, 'classification'])->name('offline.classification');
            Route::get('offline/classification/{classification}/datatable', [RecordOfflineController::class, 'classificationDatatable'])->name('offline.classification.datatable');
            Route::get('offline/classification/{classification}/download/{format?}', [RecordOfflineController::class, 'classificationDownload'])->name('offline.classification.download');
            Route::get('offline/classification/{classification}/export/excel', [RecordOfflineController::class, 'classificationExportExcel'])->name('offline.classification.export.excel');
            Route::get('offline/classification/{classification}/export/csv', [RecordOfflineController::class, 'classificationExportCsv'])->name('offline.classification.export.csv');
            Route::get('offline/classification/{classification}/export/pdf', [RecordOfflineController::class, 'classificationExportPdf'])->name('offline.classification.export.pdf');
            Route::get('offline/participant/{identifier}', [RecordOfflineController::class, 'participantDetail'])->name('offline.participant');
            Route::get('offline/participant/{identifier}/datatable', [RecordOfflineController::class, 'participantDatatable'])->name('offline.participant.datatable');
            Route::get('offline/participant/{identifier}/export/excel', [RecordOfflineController::class, 'participantExportExcel'])->name('offline.participant.export.excel');
            Route::get('offline/participant/{identifier}/export/csv', [RecordOfflineController::class, 'participantExportCsv'])->name('offline.participant.export.csv');
            Route::get('offline/participant/{identifier}/export/pdf', [RecordOfflineController::class, 'participantExportPdf'])->name('offline.participant.export.pdf');
            Route::get('offline/course/{training}', [RecordOfflineController::class, 'courseDetail'])->name('offline.course');
            Route::get('offline/course/{training}/datatable', [RecordOfflineController::class, 'courseDatatable'])->name('offline.course.datatable');
            Route::get('offline/course/{training}/export/excel', [RecordOfflineController::class, 'courseExportExcel'])->name('offline.course.export.excel');
            Route::get('offline/course/{training}/export/csv', [RecordOfflineController::class, 'courseExportCsv'])->name('offline.course.export.csv');
            Route::get('offline/course/{training}/export/pdf', [RecordOfflineController::class, 'courseExportPdf'])->name('offline.course.export.pdf');
            Route::get('offline/datatable', [RecordOfflineController::class, 'datatable'])->name('offline.datatable');
            Route::get('offline/options', [OfflineTrainingOptionController::class, 'index'])->name('offline.options.index');
            Route::post('offline/options', [OfflineTrainingOptionController::class, 'store'])->name('offline.options.store');
            Route::get('offline/create', [RecordOfflineController::class, 'create'])->name('offline.create');
            Route::get('offline/search-employees', [RecordOfflineController::class, 'searchEmployees'])->name('offline.search-employees');
            Route::post('offline/store', [RecordOfflineController::class, 'store'])->name('offline.store');
            Route::get('offline/import-non-ptkp', [OfflineTrainingImportController::class, 'index'])->name('offline.import-non-ptkp');
            Route::get('offline/import', [OfflineTrainingImportController::class, 'index'])->name('offline.import');
            Route::get('offline/import/template', [OfflineTrainingImportController::class, 'downloadTemplate'])->name('offline.import.template');
            Route::get('offline/template', [OfflineTrainingImportController::class, 'downloadTemplate'])->name('offline.template');
            Route::post('offline/import/preview', [OfflineTrainingImportController::class, 'preview'])->name('offline.import.preview');
            Route::post('offline/import/confirm', [OfflineTrainingImportController::class, 'confirm'])->name('offline.import.confirm');
            Route::post('offline/import/cancel', [OfflineTrainingImportController::class, 'cancel'])->name('offline.import.cancel');
            Route::get('offline/import-non-ptkp/template', [OfflineTrainingImportController::class, 'downloadTemplate'])->name('offline.import-non-ptkp.template');
            Route::post('offline/import-non-ptkp/preview', [OfflineTrainingImportController::class, 'preview'])->name('offline.import-non-ptkp.preview');
            Route::post('offline/import-non-ptkp/confirm', [OfflineTrainingImportController::class, 'confirm'])->name('offline.import-non-ptkp.confirm');
            Route::post('offline/import-non-ptkp/cancel', [OfflineTrainingImportController::class, 'cancel'])->name('offline.import-non-ptkp.cancel');
            Route::get('offline/import-ptkp', [PtkpImportController::class, 'index'])->name('offline.import-ptkp');
            Route::get('offline/import-ptkp/template', [PtkpImportController::class, 'downloadTemplate'])->name('offline.import-ptkp.template');
            Route::post('offline/import-ptkp/preview', [PtkpImportController::class, 'preview'])->name('offline.import-ptkp.preview');
            Route::post('offline/import-ptkp/confirm', [PtkpImportController::class, 'confirm'])->name('offline.import-ptkp.confirm');
            Route::post('offline/import-ptkp/cancel', [PtkpImportController::class, 'cancel'])->name('offline.import-ptkp.cancel');
            Route::get('offline/export', [RecordOfflineController::class, 'export'])->name('offline.export');
            Route::get('offline/export/pdf', [RecordOfflineController::class, 'exportPdf'])->name('offline.export.pdf');
            Route::get('offline/export/csv', [RecordOfflineController::class, 'exportCsv'])->name('offline.export.csv');
            Route::get('offline/{id}/detail', [RecordOfflineController::class, 'detail'])->name('offline.detail');
            Route::get('offline/{id}/edit', [RecordOfflineController::class, 'edit'])->name('offline.edit');
            Route::put('offline/{id}', [RecordOfflineController::class, 'update'])->name('offline.update');
            Route::delete('offline/{id}', [RecordOfflineController::class, 'destroy'])->name('offline.destroy');
            Route::get('all', [RecordAllController::class, 'index'])->name('all');
            Route::get('all/ajax/summary', [RecordAllController::class, 'ajaxSummary'])->name('all.ajax.summary');
            Route::get('all/ajax/classification', [RecordAllController::class, 'ajaxClassification'])->name('all.ajax.classification');
            Route::get('all/classification/{classification}', [RecordAllController::class, 'classification'])->name('all.classification');
            Route::get('all/classification/{classification}/datatable', [RecordAllController::class, 'classificationDatatable'])->name('all.classification.datatable');
            Route::get('all/classification/{classification}/download/{format?}', [RecordAllController::class, 'classificationDownload'])->name('all.classification.download');
            Route::get('all/datatable', [RecordAllController::class, 'datatable'])->name('all.datatable');
            Route::get('all/participant/{identifier}', [RecordAllController::class, 'participantDetail'])->name('all.participant');
            Route::get('all/participant/{identifier}/datatable', [RecordAllController::class, 'participantDatatable'])->name('all.participant.datatable');
            Route::get('all/participant/{identifier}/summary', [RecordAllController::class, 'participantAjaxSummary'])->name('all.participant.summary');
            Route::get('all/participant/{identifier}/export/excel', [RecordAllController::class, 'participantExportExcel'])->name('all.participant.export.excel');
            Route::get('all/participant/{identifier}/export/csv', [RecordAllController::class, 'participantExportCsv'])->name('all.participant.export.csv');
            Route::get('all/participant/{identifier}/export/pdf', [RecordAllController::class, 'participantExportPdf'])->name('all.participant.export.pdf');
            Route::get('all/course/{type}/{id}', [RecordAllController::class, 'courseRedirect'])->name('all.course');
            Route::get('export-all', [RecordExportController::class, 'index'])->name('export-all');
            Route::match(['get', 'post'], 'export-all/preview', [RecordExportController::class, 'preview'])->name('export-all.preview');
            Route::get('export-all/download', [RecordExportController::class, 'download'])->name('export-all.download');
            Route::get('export-all/sub-classifications', [RecordExportController::class, 'subClassifications'])->name('export-all.sub-classifications');
            Route::get('export-all/scope-options', [RecordExportController::class, 'scopeOptions'])->name('export-all.scope-options');
            Route::get('export/online', [\App\Http\Controllers\ManageRecordExportController::class, 'exportOnline'])->name('export.online');
            Route::get('export/offline', [\App\Http\Controllers\ManageRecordExportController::class, 'exportOffline'])->name('export.offline');
            Route::get('export/all', [\App\Http\Controllers\ManageRecordExportController::class, 'exportAll'])->name('export.all');
            Route::get('export/classification', [\App\Http\Controllers\ManageRecordExportController::class, 'exportClassification'])->name('export.classification');
            Route::get('export/classification/online/{classification?}', [\App\Http\Controllers\ManageRecordExportController::class, 'exportClassificationOnline'])->name('export.classification.online');
            Route::get('export/classification/offline/{classification?}', [\App\Http\Controllers\ManageRecordExportController::class, 'exportClassificationOffline'])->name('export.classification.offline');
            Route::get('export/classification/all/{classification?}', [\App\Http\Controllers\ManageRecordExportController::class, 'exportClassificationAll'])->name('export.classification.all');
            Route::get('{id}/edit', [\App\Http\Controllers\ManageRecordController::class, 'edit'])->name('edit');
            Route::get('detail/{id}', [\App\Http\Controllers\ManageRecordController::class, 'getMoreInformationDetail'])->name('detail');
            Route::get('{id}/detail', [\App\Http\Controllers\ManageRecordController::class, 'getMoreInformationDetail']);
            Route::get('{id}/more-info', [\App\Http\Controllers\ManageRecordController::class, 'moreInformation'])->name('more-info');
        });
    });

    Route::group(['middleware' => 'mpermission:learning_hours_management'], function () {
        Route::get('learning-hours', [LearningHoursLandingController::class, 'index'])->name('manage.learning-hours.index');

        Route::prefix('learning-hours/content-user')->name('manage.learning-hours.content-user.')->group(function () {
            Route::get('/', [ContentUserLearningHourController::class, 'index'])->name('index');
            Route::get('/datatable', [ContentUserLearningHourController::class, 'datatable'])->name('datatable');
            Route::get('/statistics', [ContentUserLearningHourController::class, 'statistics'])->name('statistics');
            Route::post('/calculate-selected', [ContentUserLearningHourController::class, 'calculateSelected'])->name('calculate-selected');
            Route::post('/recalculate-selected', [ContentUserLearningHourController::class, 'recalculateSelected'])->name('recalculate-selected');
            Route::post('/batch/start', [ContentUserLearningHourController::class, 'startBatch'])->name('batch.start');
            Route::post('/batch/process', [ContentUserLearningHourController::class, 'processBatch'])->name('batch.process');
        });

        Route::prefix('learning-hours/playlist-content-user')->name('manage.learning-hours.playlist-content-user.')->group(function () {
            Route::get('/', [PlaylistContentUserLearningHourController::class, 'index'])->name('index');
            Route::get('/datatable', [PlaylistContentUserLearningHourController::class, 'datatable'])->name('datatable');
            Route::get('/statistics', [PlaylistContentUserLearningHourController::class, 'statistics'])->name('statistics');
            Route::post('/calculate', [PlaylistContentUserLearningHourController::class, 'calculate'])->name('calculate');
        });

        Route::prefix('learning-hours/dry-run')->name('manage.learning-hours.dry-run.')->group(function () {
            Route::get('/', [PlaylistProgressDryRunController::class, 'index'])->name('index');
            Route::post('/execute', [PlaylistProgressDryRunController::class, 'executeStart'])->name('execute');
            Route::post('/execute-step', [PlaylistProgressDryRunController::class, 'executeStep'])->name('execute-step');
            Route::get('/status/{executionId}', [PlaylistProgressDryRunController::class, 'status'])->name('status');
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
    Route::get('/live-search', [ManageRoleController::class, 'liveSearch'])->name('manage.role.live-search');
    Route::get('/search-autocomplete', [ManageRoleController::class, 'liveSearch'])->name('manage.role.autocomplete');
    Route::get('/autocomplete', [ManageRoleController::class, 'liveSearch']);
    Route::post('/sync', [ManageRoleController::class, 'sync'])->name('manage.role.sync');
    Route::post('/sync-dry-run', [ManageRoleController::class, 'syncDryRun'])->name('manage.role.sync.dry-run');
    Route::get('/{user}/show', [ManageRoleController::class, 'show'])->name('manage.role.show');
    Route::get('/{user}', [ManageRoleController::class, 'show']);
    Route::put('/{user}', [ManageRoleController::class, 'update'])->name('manage.role.update');
    Route::delete('/{user}', [ManageRoleController::class, 'destroy'])->name('manage.role.destroy');
});

/*
|--------------------------------------------------------------------------
| Monitoring
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'monitoring', 'middleware' => ['auth', 'mpermission:monitoring']], function () {
    Route::get('/', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/live-search', [MonitoringController::class, 'liveSearch'])->name('monitoring.live-search');
    Route::get('/autocomplete', [MonitoringController::class, 'liveSearch']);
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
