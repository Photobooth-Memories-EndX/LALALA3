<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckManagePermission
{
    public function handle(Request $request, Closure $next, string $moduleKey)
    {
        if (!Auth::check()) {
            return redirect()->route('loginpage');
        }

        if (!auth()->user()->canAccessManageModule($moduleKey)) {
            abort(403, 'Anda tidak memiliki akses ke module ini.');
        }

        return $next($request);
    }
}