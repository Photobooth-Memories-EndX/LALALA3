@extends('layouts.sidebar-master')

@section('content')

    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Manage / Playlist</div>
                    <h2 class="owl-page-title mb-1">{{ $title }}</h2>
                    <div class="owl-page-subtitle">Debug: cek path DB, generated URL, dan status file di disk.</div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('playlist.index') }}" class="btn btn-secondary">
                        <i class="ti ti-arrow-left me-1"></i> Kembali ke Manage Playlist
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            <div class="card owl-main-card">
                <div class="card-header">
                    <h3 class="card-title">Training Plan Thumbnail Status</h3>
                    <div class="card-subtitle text-muted mt-1">Total: {{ $rows->count() }} playlist</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px">ID</th>
                                    <th>Title</th>
                                    <th>DB Path</th>
                                    <th>Generated URL</th>
                                    <th style="width:90px">Preview</th>
                                    <th style="width:160px">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        <td class="text-muted">{{ $row['id'] }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ Str::limit($row['title'], 40) }}</span>
                                        </td>
                                        <td>
                                            @if($row['db_path'])
                                                <code class="small">{{ $row['db_path'] }}</code>
                                            @else
                                                <span class="text-muted fst-italic">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($row['generated_url'])
                                                <code class="small text-break">{{ $row['generated_url'] }}</code>
                                            @else
                                                <span class="text-muted fst-italic">null</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($row['generated_url'] && $row['status'] === 'Local OK')
                                                <img src="{{ $row['generated_url'] }}"
                                                     alt="thumb" width="72" height="48"
                                                     style="object-fit:cover; border-radius:4px;"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                                                <span style="display:none;" class="badge bg-danger-lt text-danger">Load Error</span>
                                            @else
                                                <span class="avatar avatar-sm bg-secondary-lt text-secondary">
                                                    <i class="ti ti-photo-off"></i>
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $badgeMap = [
                                                    'Local OK'              => ['bg-success-lt text-success', 'ti-circle-check'],
                                                    'File Missing'          => ['bg-danger-lt text-danger',  'ti-file-x'],
                                                    'Empty'                 => ['bg-secondary-lt text-secondary', 'ti-minus'],
                                                    'External URL Ignored'  => ['bg-warning-lt text-warning', 'ti-link-off'],
                                                ];
                                                [$badgeClass, $icon] = $badgeMap[$row['status']] ?? ['bg-secondary-lt text-secondary', 'ti-help'];
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">
                                                <i class="ti {{ $icon }} me-1"></i>
                                                {{ $row['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada data Training Plan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Legend --}}
            <div class="card mt-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-auto">
                            <span class="badge bg-success-lt text-success"><i class="ti ti-circle-check me-1"></i>Local OK</span>
                            <span class="text-muted small ms-1">File ada di <code>storage/app/public/files/trainingplan/</code></span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-danger-lt text-danger"><i class="ti ti-file-x me-1"></i>File Missing</span>
                            <span class="text-muted small ms-1">Path di DB tapi file tidak ada di disk</span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-secondary-lt text-secondary"><i class="ti ti-minus me-1"></i>Empty</span>
                            <span class="text-muted small ms-1">Kolom thumbnail kosong / null</span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-warning-lt text-warning"><i class="ti ti-link-off me-1"></i>External URL Ignored</span>
                            <span class="text-muted small ms-1">Data lama berisi URL eksternal (pledge/SSO) — diabaikan</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection
