<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\News;
use App\Models\Content;
use App\Models\Classification;
use App\Models\TrainingPlan;
use App\Models\Assesment;
use App\Models\EventPhoto;
use App\Models\CertificateSetting;


class ManageDashboardController extends Controller
{
     public function index()
    {
        $role = (int) auth()->user()->role;

        abort_unless(
            in_array($role, [2, 3, 4], true),
            403
        );

        $startMonth = now()->startOfMonth();
        $startSixMonths = now()->subMonths(6)->startOfDay();

        $monthlyUploads = [
            'content' => Content::where('created_at', '>=', $startMonth)->count(),
            'assessment' => Assesment::where('created_at', '>=', $startMonth)->count(),
            'playlist' => TrainingPlan::where('created_at', '>=', $startMonth)->count(),
            'news' => News::where('created_at', '>=', $startMonth)->count(),
            'certificate_setting' => CertificateSetting::where('created_at', '>=', $startMonth)->count(),
        ];

        $recentActivities = collect()
            ->merge(Content::latest()->take(3)->get()->map(fn ($item) => [
                'type' => 'Content',
                'title' => $item->title,
                'icon' => 'ti ti-book-2',
                'color' => 'primary',
                'time' => $item->created_at,
                'url' => url('manage/content/' . $item->slug . '/edit'),
            ]))
            ->merge(Assesment::latest()->take(3)->get()->map(fn ($item) => [
                'type' => 'Assessment',
                'title' => $item->title ?? $item->assesment_title ?? 'Assessment',
                'icon' => 'ti ti-clipboard-check',
                'color' => 'orange',
                'time' => $item->created_at,
                'url' => url('manage/assesment/' . $item->slug . '/edit'),
            ]))
            ->merge(TrainingPlan::latest()->take(3)->get()->map(fn ($item) => [
                'type' => 'Playlist',
                'title' => $item->title ?? $item->trainingplan_title ?? 'Playlist',
                'icon' => 'ti ti-route',
                'color' => 'green',
                'time' => $item->created_at,
                'url' => url('manage/trainingplan/' . $item->slug . '/edit'),
            ]))
            ->merge(News::latest()->take(3)->get()->map(fn ($item) => [
                'type' => 'News',
                'title' => $item->title ?? 'News',
                'icon' => 'ti ti-news',
                'color' => 'blue',
                'time' => $item->created_at,
                'url' => url('manage/news/' . $item->slug . '/edit'),
            ]))
            ->merge(CertificateSetting::latest()->take(3)->get()->map(fn ($item) => [
                'type' => 'Certificate Setting',
                'title' => $item->issuer_name ?? 'Certificate Setting',
                'icon' => 'ti ti-award',
                'color' => 'purple',
                'time' => $item->created_at,
                'url' => route('certificate.setting.index'),
            ]))
            ->sortByDesc('time')
            ->take(4)
            ->values();

        $topContributors = Content::select('author')
            ->selectRaw('COUNT(*) as total_upload')
            ->whereNotNull('author')
            ->where('author', '!=', '')
            ->where('created_at', '>=', $startSixMonths)
            ->groupBy('author')
            ->orderByDesc('total_upload')
            ->take(5)
            ->get();

        return view('main.manage.dashboard', [
            'totalUsers' => User::count(),
            'totalContent' => Content::count(),
            'totalNews' => News::count(),
            'totalCategories' => Classification::count(),
            'totalAssessments' => Assesment::count(),
            'totalPlaylists' => TrainingPlan::count(),
            'totalGallery' => EventPhoto::count(),
            'totalCertificateSettings' => CertificateSetting::count(),
            'monthlyUploads' => $monthlyUploads,
            'recentActivities' => $recentActivities,
            'topContributors' => $topContributors,
        ]);
    }
}
