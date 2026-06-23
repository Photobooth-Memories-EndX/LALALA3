@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">

                @php
                    $reason   = $reason ?? 'inactive';
                    $iconMap  = [
                        'before_start' => 'ti-clock-hour-3',
                        'expired'      => 'ti-calendar-x',
                        'inactive'     => 'ti-circle-x',
                    ];
                    $colorMap = [
                        'before_start' => 'blue',
                        'expired'      => 'orange',
                        'inactive'     => 'red',
                    ];
                    $titleMap = [
                        'before_start' => 'Assessment Belum Dibuka',
                        'expired'      => 'Assessment Sudah Berakhir',
                        'inactive'     => 'Assessment Tidak Aktif',
                    ];
                    $msgMap   = [
                        'before_start' => 'Assessment ini baru dapat diakses sesuai jadwal yang ditentukan.',
                        'expired'      => 'Periode pengerjaan assessment ini telah berakhir.',
                        'inactive'     => 'Assessment ini sedang tidak aktif. Silakan hubungi Administrator LMS.',
                    ];
                    $icon    = $iconMap[$reason]  ?? 'ti-circle-x';
                    $color   = $colorMap[$reason] ?? 'red';
                    $title   = $titleMap[$reason] ?? 'Assessment Tidak Tersedia';
                    $message = $msgMap[$reason]   ?? '';
                @endphp

                <div class="card border-0 shadow-sm mt-4" style="border-radius: 1rem; overflow: hidden;">
                    <div class="card-status-top bg-{{ $color }}"></div>

                    <div class="card-body text-center py-5 px-4">

                        <span class="avatar avatar-xl bg-{{ $color }}-lt text-{{ $color }} mb-4" style="font-size: 1.75rem; width: 4rem; height: 4rem;">
                            <i class="ti {{ $icon }}"></i>
                        </span>

                        <h2 class="mb-2">{{ $title }}</h2>
                        <p class="text-muted">{{ $message }}</p>

                        @isset($assesment)
                            <div class="mt-2 mb-3">
                                <span class="badge bg-{{ $color }}-lt text-{{ $color }} px-3 py-2" style="font-size: 0.85rem;">
                                    {{ $assesment->title ?? '-' }}
                                </span>
                            </div>
                        @endisset

                        @if(in_array($reason, ['before_start', 'expired']) && isset($fromdate, $todate))
                            <div class="alert alert-warning text-start mt-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="ti ti-calendar-event"></i>
                                    <strong>Jadwal Assessment</strong>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="text-muted small">Mulai</div>
                                        <div class="fw-bold">
                                            {{ \Carbon\Carbon::parse($fromdate)->locale('id')->translatedFormat('d F Y, H:i') }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted small">Berakhir</div>
                                        <div class="fw-bold">
                                            {{ \Carbon\Carbon::parse($todate)->locale('id')->translatedFormat('d F Y, H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($reason === 'expired')
                            <div class="alert alert-info text-start">
                                <i class="ti ti-info-circle me-1"></i>
                                Jika Anda merasa seharusnya masih memiliki akses, silakan hubungi
                                <strong>Administrator LMS</strong> atau <strong>HR Training</strong>.
                            </div>
                        @endif

                        <div class="d-flex justify-content-center flex-wrap gap-2 mt-4">
                            <a href="{{ route('home.user.index') }}" class="btn btn-primary">
                                <i class="ti ti-home me-1"></i>
                                Kembali ke Dashboard
                            </a>
                            <a href="{{ route('user.learning.dashboard') }}" class="btn btn-outline-secondary">
                                <i class="ti ti-books me-1"></i>
                                My Learning
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
