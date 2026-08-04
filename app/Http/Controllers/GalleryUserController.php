<?php

namespace App\Http\Controllers;

use App\Http\Requests\GalleryFilterRequest;
use App\Models\Comment;
use App\Models\EventPhoto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GalleryUserController extends Controller
{
    public function index(GalleryFilterRequest $request): View|JsonResponse
    {
        $filters = $this->normalizedFilters($request->validated());
        $query = EventPhoto::query()
            ->withCount('comments')
            ->where('active', 'Active');

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters['sort']);

        $eventphoto = $query->paginate(9)->withQueryString();
        $hasFilters = $this->hasActiveFilters($filters);

        $optionQuery = EventPhoto::query()->where('active', 'Active');
        $uploaders = (clone $optionQuery)
            ->whereNotNull('uploader')
            ->where('uploader', '!=', '')
            ->distinct()
            ->orderBy('uploader')
            ->pluck('uploader');
        $categories = (clone $optionQuery)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $years = (clone $optionQuery)
            ->whereNotNull('eventdate')
            ->selectRaw($this->yearExpression() . ' AS gallery_year')
            ->distinct()
            ->orderByDesc('gallery_year')
            ->pluck('gallery_year')
            ->map(static fn ($year) => (int) $year)
            ->filter()
            ->values();

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('user.gallery.components.gallery-results', compact('eventphoto', 'hasFilters'))->render(),
                'filters_html' => view('user.gallery.components.gallery-filters', compact(
                    'uploaders',
                    'categories',
                    'years',
                    'filters',
                    'hasFilters'
                ))->render(),
                'count' => $eventphoto->total(),
            ]);
        }

        $latestGallery = (clone $optionQuery)->latest()->first();

        return view('user.gallery.index', [
            'eventphoto'  => $eventphoto,
            'uploaders'   => $uploaders,
            'categories'  => $categories,
            'years'       => $years,
            'filters'     => $filters,
            'hasFilters'  => $hasFilters,
            'latestGallery' => $latestGallery,
        ]);
    }

    public function view($slug)
    {
        $eventphoto = EventPhoto::where('active', 'Active')
            ->where('slug', $slug)
            ->firstOrFail();

        EventPhoto::whereKey($eventphoto->id)->update([
            'views' => DB::raw($this->numericExpression('views') . ' + 1'),
        ]);
        $eventphoto->refresh();

        $photosArr  = $eventphoto->photos ? (json_decode($eventphoto->photos, true) ?? []) : [];
        $photoCount = is_array($photosArr) ? count($photosArr) : 0;

        $comment = Comment::with([
                'user',
                'replies.user',
                'replies.likes',
                'replies.likedByUser',
                'likes',
                'likedByUser',
            ])
            ->withCount('replies')
            ->whereNull('parent_id')
            ->where('type', 'eventphoto')
            ->where('eventphoto_id', $eventphoto->id)
            ->latest()
            ->paginate(10);

        return view('user.gallery.view', [
            'eventphoto' => $eventphoto,
            'comment'    => $comment,
            'photoCount' => $photoCount,
            'photos'     => is_array($photosArr) ? $photosArr : [],
        ]);
    }

    private function normalizedFilters(array $validated): array
    {
        $sort = $validated['sort'] ?? 'latest';
        $views = $validated['views'] ?? (str_ends_with($sort, '_viewed') ? $sort : null);
        $likes = $validated['likes'] ?? (str_ends_with($sort, '_liked') ? $sort : null);

        if ($views) {
            $sort = $views;
        }

        if ($likes) {
            $sort = $likes;
        }

        return [
            'q' => trim((string) ($validated['q'] ?? '')),
            'uploader' => $validated['uploader'] ?? null,
            'category' => array_values(array_unique($validated['category'] ?? [])),
            'year' => array_map('intval', array_values(array_unique($validated['year'] ?? []))),
            'month' => array_map('intval', array_values(array_unique($validated['month'] ?? []))),
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'views' => $views,
            'likes' => $likes,
            'sort' => $sort,
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['q'] !== '') {
            $query->where('title', 'like', '%' . $filters['q'] . '%');
        }

        if ($filters['uploader']) {
            $query->where('uploader', $filters['uploader']);
        }

        if ($filters['category']) {
            $query->whereIn('category', $filters['category']);
        }

        if ($filters['year']) {
            $query->whereRaw(
                $this->yearExpression() . ' IN (' . implode(',', array_fill(0, count($filters['year']), '?')) . ')',
                $filters['year']
            );
        }

        if ($filters['month']) {
            $query->whereRaw(
                $this->monthExpression() . ' IN (' . implode(',', array_fill(0, count($filters['month']), '?')) . ')',
                $filters['month']
            );
        }

        if ($filters['date_from']) {
            $query->whereDate('eventdate', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('eventdate', '<=', $filters['date_to']);
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        if (in_array($sort, ['most_viewed', 'least_viewed'], true)) {
            $direction = $sort === 'most_viewed' ? 'desc' : 'asc';
            $query->orderByRaw($this->numericExpression('views') . ' ' . $direction)
                ->orderByDesc('id');
            return;
        }

        if (in_array($sort, ['most_liked', 'least_liked'], true)) {
            $direction = $sort === 'most_liked' ? 'desc' : 'asc';
            $query->orderByRaw($this->numericExpression('likes') . ' ' . $direction)
                ->orderByDesc('id');
            return;
        }

        if (in_array($sort, ['az', 'za'], true)) {
            $direction = $sort === 'az' ? 'asc' : 'desc';
            $query->orderByRaw("CASE WHEN title IS NULL OR title = '' THEN 1 ELSE 0 END")
                ->orderBy('title', $direction)
                ->orderByDesc('id');
            return;
        }

        if ($sort === 'oldest') {
            $query->orderBy('created_at')->orderBy('id');
            return;
        }

        $query->orderByDesc('created_at')->orderByDesc('id');
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || !empty($filters['uploader'])
            || !empty($filters['category'])
            || !empty($filters['year'])
            || !empty($filters['month'])
            || !empty($filters['date_from'])
            || !empty($filters['date_to'])
            || !empty($filters['views'])
            || !empty($filters['likes'])
            || $filters['sort'] !== 'latest';
    }

    private function numericExpression(string $column): string
    {
        $driver = EventPhoto::query()->getConnection()->getDriverName();

        return match ($driver) {
            'sqlite' => "CAST(COALESCE(NULLIF({$column}, ''), '0') AS INTEGER)",
            'pgsql' => "CASE WHEN COALESCE({$column}, '') ~ '^[0-9]+$' THEN CAST({$column} AS BIGINT) ELSE 0 END",
            'sqlsrv' => "COALESCE(TRY_CAST({$column} AS BIGINT), 0)",
            default => "CAST(COALESCE(NULLIF({$column}, ''), '0') AS UNSIGNED)",
        };
    }

    private function yearExpression(): string
    {
        return match (EventPhoto::query()->getConnection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%Y', eventdate) AS INTEGER)",
            'pgsql' => 'EXTRACT(YEAR FROM eventdate)',
            'sqlsrv' => 'DATEPART(year, eventdate)',
            default => 'YEAR(eventdate)',
        };
    }

    private function monthExpression(): string
    {
        return match (EventPhoto::query()->getConnection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', eventdate) AS INTEGER)",
            'pgsql' => 'EXTRACT(MONTH FROM eventdate)',
            'sqlsrv' => 'DATEPART(month, eventdate)',
            default => 'MONTH(eventdate)',
        };
    }
}
