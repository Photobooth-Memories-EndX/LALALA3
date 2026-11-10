<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckManagePermission
{
    public function handle(Request $request, Closure $next, ...$moduleKeys)
    {
        if (!Auth::check()) {
            return redirect()->route('loginpage');
        }

        $keys = [];
        foreach ($moduleKeys as $moduleKey) {
            foreach (explode(',', (string) $moduleKey) as $k) {
                $trimmed = trim($k);
                if ($trimmed !== '') {
                    $keys[] = $trimmed;
                }
            }
        }

        if (empty($keys)) {
            return $next($request);
        }

        $user = auth()->user();
        foreach ($keys as $key) {
            if ($user->canAccessManageModule($key)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki akses ke module ini.');
    }
}
