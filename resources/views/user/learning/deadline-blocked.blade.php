@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">
        <div class="row justify-content-center mt-4">
            <div class="col-md-7 col-lg-6">

                <div class="card border-0 shadow-sm" style="border-radius: 1rem; overflow: hidden;">

                    <div class="card-status-top {{ $status === 'expired' ? 'bg-danger' : 'bg-warning' }}"></div>

                    <div class="card-body text-center py-5 px-4">

                        @if($status === 'expired')
                            <span class="avatar avatar-xl mb-4"
                                  style="font-size: 1.75rem; width: 4rem; height: 4rem; background: var(--tblr-red-lt, #fce8e8); color: var(--tblr-danger, #d63939);">
                                <i class="ti ti-lock"></i>
                            </span>
                            <h2 class="mb-2">Akses Tidak Tersedia</h2>
                            <p class="text-muted">
                                <strong>{{ $title }}</strong> sudah melewati deadline.
                                Silakan hubungi Admin atau Team Training.
                            </p>
                        @else
                            <span class="avatar avatar-xl mb-4"
                                  style="font-size: 1.75rem; width: 4rem; height: 4rem; background: var(--tblr-yellow-lt, #fef6e3); color: var(--tblr-warning, #f59f00);">
                                <i class="ti ti-calendar-time"></i>
                            </span>
                            <h2 class="mb-2">Konten Belum Tersedia</h2>
                            <p class="text-muted">
                                <strong>{{ $title }}</strong> belum tersedia.
                                Silakan hubungi Admin atau Team Training.
                            </p>
                        @endif

                        @if(!empty($fromDate) || !empty($toDate))
                            <div class="mb-4">
                                <span class="badge {{ $status === 'expired' ? 'bg-danger-lt text-danger' : 'bg-warning-lt text-warning' }} px-3 py-2"
                                      style="font-size: 0.85rem;">
                                    <i class="ti ti-calendar me-1"></i>
                                    Periode Akses:
                                    {{ $fromDate ? $fromDate->format('d M Y') : '—' }}
                                    –
                                    {{ $toDate ? $toDate->format('d M Y') : '—' }}
                                </span>
                            </div>
                        @endif

                        <div class="alert alert-{{ $status === 'expired' ? 'danger' : 'warning' }} text-start mb-4" style="border-radius: .75rem;">
                            <i class="ti ti-info-circle me-1"></i>
                            Jika Anda memerlukan akses lebih lanjut, silakan hubungi
                            <strong>Administrator LMS</strong> atau <strong>Team Training</strong>.
                        </div>

                        <div class="d-flex justify-content-center flex-wrap gap-2">
                            <a href="{{ route('user.learning.dashboard') }}" class="btn btn-primary">
                                <i class="ti ti-books me-1"></i>
                                My Learning
                            </a>
                            @if($type === 'content')
                                <a href="{{ route('content.user.index') }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-arrow-left me-1"></i>
                                    Kembali ke Konten
                                </a>
                            @else
                                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('user.learning.dashboard') }}"
                                   class="btn btn-outline-secondary">
                                    <i class="ti ti-arrow-left me-1"></i>
                                    Kembali
                                </a>
                            @endif
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
