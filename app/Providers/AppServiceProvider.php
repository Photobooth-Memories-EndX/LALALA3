<?php

namespace App\Providers;

use App\Models\User;
use App\Services\LearningAnalyticsService;
use Illuminate\Support\Str;
use App\Models\Classification;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();

        view()->composer(['partials.header', 'layouts.header'], function ($view) {
            $view->with('category', Classification::select('classification_name', 'slug')->get());
        });
        view()->composer(['partials.header', 'layouts.header'], function ($view) {
            $view->with(
                'mylearningongoing',
                app(LearningAnalyticsService::class)->ongoingLearningCount((int) auth()->id())
            );
        });

        Str::macro('initials', fn ($value, $sep = ' ', $glue = '') => trim(collect(explode($sep, $value))->map(function ($segment) {
            return $segment[0] ?? '';
        })->join($glue)));

        Gate::define('admin', function (User $user) { //use on 'can'/'canany' blade
            return $user->role === '2';
        });
        Gate::define('superadmin', function (User $user) {
            return $user->role === '3';
        });
        Gate::define('learning_management', function (?User $user = null) {
            return (bool) $user?->canAccessManageModule('learning_management');
        });

        Blade::if('canDownloadLearning', function () {
            return (bool) auth()->user()?->canAccessManageModule('learning_management');
        });
    }
}
