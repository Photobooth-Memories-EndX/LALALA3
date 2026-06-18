@extends('layouts.sidebar-master')

@section('content')

    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Monitoring</h2>
                    <div class="text-muted mt-1">
                        Progress belajar user &middot; <span class="fw-bold">{{ $scopeLabel }}</span>
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                        <li class="breadcrumb-item"><a href="#">Monitoring</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">User</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar User</h3>
                    <div class="card-actions">
                        <form method="GET" action="{{ route('monitoring.index') }}" class="d-flex">
                            <input type="text" name="s" value="{{ $search }}"
                                   class="form-control form-control-sm me-2"
                                   placeholder="Cari nama / NIP / division...">
                            <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Division</th>
                                <th class="text-center">Content Selesai</th>
                                <th>Rata-rata Progress</th>
                                <th>Assessment Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                @php
                                    $avg = (int) ($user->avg_progress ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration + ($users->firstItem() - 1) }}</td>
                                    <td>
                                        <div>{{ $user->name }}</div>
                                        <div class="text-muted small">{{ $user->id_emp ?? '-' }}</div>
                                    </td>
                                    <td>{{ $user->division ?? '-' }}</td>
                                    <td class="text-center">{{ $user->finished_content_count ?? 0 }}</td>
                                    <td style="min-width: 160px;">
                                        <div class="d-flex align-items-center">
                                            <span class="me-2 small">{{ $avg }}%</span>
                                            <div class="progress flex-fill" style="height: 6px;">
                                                <div class="progress-bar" style="width: {{ $avg }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($user->last_assessment_at)
                                            <div>{{ \Carbon\Carbon::parse($user->last_assessment_at)->format('d M Y') }}</div>
                                            <div class="text-muted small">Skor: {{ $user->last_assessment_score ?? 0 }}</div>
                                        @else
                                            <span class="text-muted small">Belum ada</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Tidak ada data user.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex align-items-center">
                    {{ $users->links() }}
                </div>
            </div>

        </div>
    </div>

@endsection
