<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PagesUserController;
use App\Http\Controllers\NewsUserController;
use App\Http\Controllers\NewsImageController;

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

Route::get('/{slug}', [PagesUserController::class, 'view'])->name('pages.user.view');

Route::group(['prefix' => 'manage'], function () {

    Route::resource('user', UserController::class);
    Route::resource('news', NewsController::class);
    Route::put('news/delete-image/{slug}', [NewsImageController::class, 'deleteImage'])->name('news.deleteImage');
    Route::resource('pages', PagesController::class);

});

Route::group(['prefix' => 'news'], function () {

    Route::get('/', [NewsUserController::class, 'index'])->name('news.user.index');
    Route::get('/{slug}', [NewsUserController::class, 'view'])->name('news.user.view');

});
