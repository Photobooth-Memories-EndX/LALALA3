<?php

namespace App\Http\Controllers;

use App\Models\AssesmentResult;
use App\Models\ContentUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonitoringController extends Controller
{
    /**
     * Monitoring progress belajar user.
     *
     * - Super Admin (3) : semua user.
     * - Admin Training (2) : semua user (data training umum).
     * - Subadmin Division (4) : HANYA user dengan division yang sama.
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $search   = $request->input('s');

        $query = User::query()
            ->select('users.*')
            // Jumlah content yang sudah selesai (finish = '1').
            ->selectSub(
                ContentUser::selectRaw('COUNT(*)')
                    ->whereColumn('content_users.user_id', 'users.id')
                    ->where('finish', '1'),
                'finished_content_count'
            )
            // Rata-rata progress seluruh content user.
            ->selectSub(
                ContentUser::selectRaw('COALESCE(ROUND(AVG(progress)), 0)')
                    ->whereColumn('content_users.user_id', 'users.id'),
                'avg_progress'
            )
            // Waktu assessment terakhir dikerjakan.
            ->selectSub(
                AssesmentResult::select('created_at')
                    ->whereColumn('assesment_results.user_id', 'users.id')
                    ->latest()
                    ->limit(1),
                'last_assessment_at'
            )
            // Skor assessment terakhir.
            ->selectSub(
                AssesmentResult::select('score')
                    ->whereColumn('assesment_results.user_id', 'users.id')
                    ->latest()
                    ->limit(1),
                'last_assessment_score'
            );

        // Subadmin Division hanya boleh melihat division miliknya sendiri.
        if ($authUser->isSubadminDivision()) {
            $query->where('division', $authUser->division);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id_emp', 'like', "%{$search}%")
                    ->orWhere('division', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Label cakupan untuk header halaman.
        $scopeLabel = $authUser->isSubadminDivision()
            ? ('Division: ' . ($authUser->division ?: '-'))
            : 'Semua User';

        return view('main.monitoring.index', [
            'users'      => $users,
            'search'     => $search,
            'scopeLabel' => $scopeLabel,
        ]);
    }
}
