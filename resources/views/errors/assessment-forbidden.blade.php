@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">

                <div class="card border-0 shadow-sm mt-4" style="border-radius: 1rem; overflow: hidden;">
                    <div class="card-status-top bg-danger"></div>

                    <div class="card-body text-center py-5 px-4">

                        <span class="avatar avatar-xl bg-red-lt text-danger mb-4" style="font-size: 1.75rem; width: 4rem; height: 4rem;">
                            <i class="ti ti-lock"></i>
                        </span>

                        <h2 class="mb-2">Akses Assessment Ditolak</h2>
                        <p class="text-muted">
                            Anda tidak termasuk peserta yang ditugaskan untuk assessment ini.
                        </p>

                        @isset($assesment)
                            <div class="mt-2 mb-3">
                                <span class="badge bg-red-lt text-danger px-3 py-2" style="font-size: 0.85rem;">
                                    {{ $assesment->title ?? '-' }}
                                </span>
                            </div>
                        @endisset

                        @isset($user)
                            <div class="card border-0 text-start mt-3 mb-3" style="background: var(--tblr-bg-surface-secondary, #f8fafc);">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="ti ti-user-circle text-muted"></i>
                                        <span class="text-muted small fw-semibold text-uppercase tracking-wide">
                                            Informasi Akun Anda
                                        </span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="text-muted small">Name</div>
                                            <div class="fw-bold">{{ $user->name ?? '-' }}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Team</div>
                                            <div class="fw-bold">{{ $user->team ?? '-' }}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Department</div>
                                            <div class="fw-bold">{{ $user->department ?? '-' }}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Division</div>
                                            <div class="fw-bold">{{ $user->division ?? '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endisset

                        <div class="alert alert-warning text-start">
                            <i class="ti ti-alert-triangle me-1"></i>
                            Jika Anda merasa seharusnya memiliki akses untuk assessment ini, silakan hubungi
                            <strong>Administrator LMS</strong> atau <strong>HR Training</strong>.
                        </div>

                        <div class="d-flex justify-content-center flex-wrap gap-2 mt-4">
                            <a href="{{ route('home.user.index') }}" class="btn btn-primary">
                                <i class="ti ti-home me-1"></i>
                                Back to Dashboard
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
