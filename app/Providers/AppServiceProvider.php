<?php

namespace App\Providers;

use Illuminate\Support\Str;
use App\Models\Classification;
use App\Models\ContentUser;
use Illuminate\Pagination\Paginator;
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

        view()->composer('partials.header', function ($view) {
            $view->with('category', Classification::select('classification_name', 'slug')->get());
        });
        view()->composer('partials.header', function ($view) {
            $view->with('mylearningongoing', ContentUser::where('user_id', auth()->user()->id)->where('finish', '0')->count());
        });

        Str::macro('initials', fn ($value, $sep = ' ', $glue = '') => trim(collect(explode($sep, $value))->map(function ($segment) {
            return $segment[0] ?? '';
        })->join($glue)));
    }
}
