<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthMonitoring
{
    /**
     * Handle an incoming request.
     *
     * Hanya role yang boleh memantau (Admin Training, Super Admin,
     * Subadmin Division) yang diizinkan. Pembatasan data per-division
     * untuk Subadmin Division ditangani di controller.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('loginpage');
        }

        if (!auth()->user()->canMonitor()) {
            abort(403);
        }

        return $next($request);
    }
}
