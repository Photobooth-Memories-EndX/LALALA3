@props([
    'contentUser',
    'averageProgress' => 0,
])

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
        <h3 class="mb-1">Learning Record</h3>
        <div class="text-muted">Riwayat pembelajaran yang sudah selesai.</div>

        <div class="mt-4">
            <div class="fw-bold mb-1">Average Progress</div>
            <div class="display-6 text-primary mb-2">{{ $averageProgress }}%</div>
            <div class="progress progress-sm">
                <div class="progress-bar bg-primary" style="width: {{ $averageProgress }}%"></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
        <h3 class="card-title mb-1">Daftar Riwayat Training</h3>
        <div class="text-muted small">Training yang sudah Anda selesaikan.</div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table" id="recordTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Kelas</th>
                    <th>Classification</th>
                    <th>Duration</th>
                    <th>Progress</th>
                    <th>Finish Date</th>
                    <th>Score Test</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($contentUser as $list)
                    @if (($list->finish ?? '0') == '1')
                        @php
                            $score = null;

                            if (!blank($list->assesment ?? null)) {
                                $datas = $list->assesment->scores ?? collect();

                                $filter = $datas->filter(function ($assesment_result) use($list) {
                                    return $assesment_result->user_id == $list->user_id;
                                })->sortByDesc('score')->first();

                                if (!blank($filter)) {
                                    $score = $filter->score;
                                }
                            }
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $list->content->title ?? '-' }}</td>
                            <td>{{ $list->content->classification->classification_name ?? '-' }}</td>
                            <td>{{ $list->content->duration ?? '-' }}</td>
                            <td>{{ $list->progress ?? 0 }}%</td>
                            <td>{{ $list->updated_at ?? $list->created_at ?? '-' }}</td>
                            <td>{{ $score ?? '-' }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>