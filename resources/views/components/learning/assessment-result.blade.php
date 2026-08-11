@props([
    'result',
])

@php
    $resultItems = collect($result ?? []);
@endphp

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-4 border-bottom">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h3 class="card-title mb-1">Assessment Result</h3>

                <div class="text-muted small">
                    Riwayat hasil assessment yang sudah Anda kerjakan.
                </div>
            </div>

            <div class="col-md-4">
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i class="ti ti-search"></i>
                    </span>

                    <input type="text"
                           id="resultSearch"
                           class="form-control rounded-3"
                           placeholder="Cari hasil assessment">
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        @if ($resultItems->count() > 0)

            <table class="table table-vcenter table-hover card-table mb-0"
                   id="MyTable">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Datetime</th>
                        <th>Assessment</th>
                        <th>Type</th>
                        <th>Score</th>
                        <th>Batch</th>
                        <th>Result</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($resultItems as $list)

                        @php
                            $assessment = $list->assesment ?? null;

                            $typeLabel = ($assessment->type ?? '') == 'trainingplan'
                                ? 'Playlist'
                                : ucwords(str_replace('_', ' ', $assessment->type ?? '-'));
                        @endphp

                        <tr>
                            <td class="text-muted">
                                {{ $loop->iteration }}
                            </td>

                            <td style="min-width: 160px;">
                                {{ $list->created_at ? date("d/m/y H:i:s", strtotime($list->created_at)) : '-' }}
                            </td>

                            <td style="min-width: 260px;">
                                <div class="d-flex align-items-center gap-2">

                                    <span class="avatar avatar-sm bg-primary-lt text-primary">
                                        {{ strtoupper(substr($assessment->title ?? 'A', 0, 1)) }}
                                    </span>

                                    <div>
                                        <div class="fw-semibold line-clamp-2">
                                            {{ $assessment->title ?? '-' }}
                                        </div>

                                        @if (($assessment->type ?? '') == 'trainingplan')
                                            <div class="small text-muted">
                                                {{ $assessment->trainingplan->title ?? 'Playlist Assessment' }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="badge bg-blue-lt text-blue">
                                    {{ $typeLabel }}
                                </span>
                            </td>

                            <td>
                                <div class="fw-bold text-primary">
                                    {{ $list->score ?? '-' }}
                                </div>
                            </td>

                            <td>
                                {{ $list->batch ?? '-' }}
                            </td>

                            <td>
                                @if ($list->pass)

                                    <span class="badge bg-green-lt text-green">
                                        Pass
                                    </span>

                                @else

                                    <span class="badge bg-red-lt text-red">
                                        Failed
                                    </span>

                                @endif
                            </td>
                        </tr>

                    @endforeach
                </tbody>
            </table>

        @else

            <div class="empty py-5">
                <div class="empty-title">
                    Belum ada hasil assessment
                </div>

                <div class="empty-subtitle text-muted">
                    Hasil assessment Anda akan tampil setelah submit jawaban.
                </div>
            </div>

        @endif
    </div>
</div>