@props([
    'ongoingContentUser',
    'ongoingLearning' => 0,
    'averageProgress' => 0,
])

<section class="learning-section-wrapper">
    <div class="learning-section-padding py-4 px-2 px-md-3 learning-dashboard-container">

        <div class="card border-0 shadow-sm rounded-4 learning-hero learning-section-hero mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary shadow-sm mb-3 px-3 py-2 rounded-pill">
                            <i class="ti ti-flame"></i>
                            <span>{{ $ongoingContentUser->total() ?? 0 }} kelas aktif</span>
                        </div>

                        <h3 class="display-6 mb-2 text-white">
                            Lanjutkan Pembelajaran Anda
                        </h3>

                        <p class="mb-0 text-white-75">
                            Selesaikan materi yang sedang berjalan dan tingkatkan progress learning Anda.
                        </p>
                    </div>

                    <div class="col-lg-5">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="learning-mini-stat">
                                    <div class="learning-mini-stat-label">Total Ongoing</div>
                                    <div class="learning-mini-stat-value">{{ $ongoingLearning }}</div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="learning-mini-stat">
                                    <div class="learning-mini-stat-label">Average Progress</div>
                                    <div class="learning-mini-stat-value">{{ $averageProgress }}%</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <a href="#learningGrid" class="btn btn-light w-100 btn-lg text-primary fw-semibold">
                                    Mulai Belajar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-learning.ongoing-filter />

        @if ($ongoingContentUser->count() > 0)
            <div class="row g-4 mb-4" id="learningGrid">
                @foreach ($ongoingContentUser as $list)
                    @if ($list->content != null)
                        @php
                            $content = $list->content;

                            $classification = $content->classification->classification_name ?? '-';
                            $title = $content->title ?? '-';
                            $slug = $content->slug ?? null;
                            $progress = (int) ($list->progress ?? 0);
                            $duration = $content->duration ?? '-';
                            $type = strtolower($content->type ?? 'content');
                            $program = strtolower($list->status ?? 'optional');

                            $year = $list->created_at
                                ? \Carbon\Carbon::parse($list->created_at)->format('Y')
                                : (
                                    $list->updated_at
                                        ? \Carbon\Carbon::parse($list->updated_at)->format('Y')
                                        : 'unknown'
                                );

                            $thumbnail = null;

                            if (!empty($content->thumbnail)) {
                                $thumbnail = asset('storage/content/thumbnail/' . $content->thumbnail);
                            } elseif (!empty($content->image)) {
                                $thumbnail = asset('storage/content/' . $content->image);
                            } elseif (!empty($content->cover)) {
                                $thumbnail = asset('storage/content/' . $content->cover);
                            }

                            $statusLabel = $list->status ?? 'optional';
                        @endphp

                        <div class="col-md-6 col-xl-4">
                            <x-learning.ongoing-card
                                :title="$title"
                                :slug="$slug"
                                :classification="$classification"
                                :duration="$duration"
                                :type="$type"
                                :progress="$progress"
                                :program="$program"
                                :year="$year"
                                :active="$list->active ?? '-'"
                                :status-label="$statusLabel"
                                :thumbnail="$thumbnail"
                            />
                        </div>
                    @endif
                @endforeach
            </div>

            @if ($ongoingContentUser->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $ongoingContentUser->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-5">
                    <div class="empty-title">Belum ada learning berjalan</div>
                    <div class="empty-subtitle text-muted">
                        Data kelas berjalan akan muncul di sini.
                    </div>
                </div>
            </div>
        @endif

    </div>
</section>