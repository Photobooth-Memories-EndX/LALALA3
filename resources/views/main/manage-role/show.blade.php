@extends('layouts.sidebar-master')

@section('content')

    {{-- Reuse design system --}}
    @include('main.monitoring.components.style')

    <div class="page-header d-print-none mb-0 pb-0">
        <div class="container-xl">
            <!-- Back Navigation Link -->
            <a href="{{ route('manage.role.index') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
                <i class="ti ti-arrow-left fs-2"></i> Kembali ke Manage Role
            </a>

            <!-- User Detail Hero Card -->
            <div class="card border border-light-subtle shadow-sm p-4 mb-4 rounded-3 bg-white hero-header-card">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 text-white fw-bold shadow-sm"
                             style="width: 72px; height: 72px; background: linear-gradient(135deg, #003b6d 0%, #0284c7 100%); font-size: 1.75rem;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <h2 class="h3 fw-bold mb-0 text-dark header-title">{{ $user->name }}</h2>
                                <span class="badge bg-blue-lt text-blue px-2 py-1">{{ $user->role_name }}</span>
                                @if($user->active == 1)
                                    <span class="badge bg-success-lt text-success px-2 py-1"><i class="ti ti-check me-1"></i>LMS Aktif</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary px-2 py-1"><i class="ti ti-x me-1"></i>LMS Nonaktif</span>
                                @endif
                                @if($user->trashed())
                                    <span class="badge bg-danger-lt text-danger px-2 py-1"><i class="ti ti-trash me-1"></i>Soft-Deleted</span>
                                @endif
                            </div>
                            <div class="text-muted small d-flex align-items-center gap-3 flex-wrap">
                                <span><i class="ti ti-id me-1"></i>NIP: <strong>{{ $user->id_emp ?: '-' }}</strong></span>
                                <span><i class="ti ti-mail me-1"></i>{{ $user->email ?: '-' }}</span>
                                <span><i class="ti ti-building me-1"></i>{{ $user->department ?: '-' }} / {{ $user->division ?: '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons on Header -->
                    <div class="d-flex align-items-center gap-2">
                        @if (!$user->isSuperAdmin() && $user->id !== auth()->id() && !$user->trashed())
                            <button type="button"
                                    class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 py-2"
                                    style="background-color: #003b6d; border-color: #003b6d;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editUserModal{{ $user->id }}">
                                <i class="ti ti-edit fs-2"></i>
                                <span>Edit User</span>
                            </button>
                        @endif

                        <a href="{{ route('manage.role.index') }}" class="btn btn-outline-secondary px-3 py-2">
                            <i class="ti ti-arrow-back-up me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body mt-0 pt-0">
        <div class="container-xl">

            <x-alert type="success" />
            <x-alert type="warning" />

            <div class="row g-4">

                <!-- Section A: Informasi Akun OWL -->
                <div class="col-lg-4 col-md-6">
                    <div class="card border border-light-subtle shadow-sm rounded-3 h-100 detail-info-card">
                        <div class="card-header bg-transparent border-bottom py-3 px-3 d-flex align-items-center gap-2">
                            <div class="rounded-2 p-1 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="ti ti-user-shield fs-2"></i>
                            </div>
                            <h4 class="card-title fw-bold text-dark mb-0">A. Informasi Akun OWL</h4>
                        </div>
                        <div class="card-body p-3">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">ID Internal Database</span>
                                    <span class="fw-semibold small text-dark">#{{ $user->id }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">ID User Sistem</span>
                                    <span class="fw-semibold small text-dark">{{ $user->id_user ?: '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">NIP / Employee ID</span>
                                    <span class="badge bg-light text-dark border">{{ $user->id_emp ?: '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Nama Lengkap</span>
                                    <span class="fw-semibold small text-dark text-end" style="max-width: 200px;">{{ $user->name ?: '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Alamat Email</span>
                                    <span class="fw-semibold small text-dark text-truncate" style="max-width: 200px;">{{ $user->email ?: '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Role Sistem</span>
                                    <span class="badge bg-blue-lt text-blue">{{ $user->role_name }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Status Akses LMS</span>
                                    @if($user->active == 1)
                                        <span class="badge bg-success-lt text-success">Aktif (1)</span>
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary">Nonaktif (0)</span>
                                    @endif
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Verifikasi Email</span>
                                    <span class="small text-muted">
                                        {{ $user->email_verified_at ? $user->email_verified_at->format('d M Y H:i') : 'Belum Diverifikasi' }}
                                    </span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Tanggal Dibuat</span>
                                    <span class="small text-muted">{{ $user->created_at ? $user->created_at->format('d M Y H:i') : '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Terakhir Diperbarui</span>
                                    <span class="small text-muted">{{ $user->updated_at ? $user->updated_at->format('d M Y H:i') : '-' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Section B: Informasi Organisasi -->
                <div class="col-lg-4 col-md-6">
                    <div class="card border border-light-subtle shadow-sm rounded-3 h-100 detail-info-card">
                        <div class="card-header bg-transparent border-bottom py-3 px-3 d-flex align-items-center gap-2">
                            <div class="rounded-2 p-1 bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="ti ti-building-community fs-2"></i>
                            </div>
                            <h4 class="card-title fw-bold text-dark mb-0">B. Informasi Organisasi</h4>
                        </div>
                        <div class="card-body p-3">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Perusahaan / Company</span>
                                    <span class="fw-semibold small text-dark">{{ $user->company_name ?: 'PT Krakatau Posco' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">ID Organization Unit</span>
                                    <span class="badge bg-light text-dark border">{{ $user->id_org_unit ?: '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                                    <span class="text-muted small">Nama Organization Unit</span>
                                    <span class="fw-semibold small text-dark text-end" style="max-width: 190px;">{{ $user->orgunit ?: '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Division</span>
                                    <div class="text-end">
                                        <div class="fw-semibold small text-dark">{{ $user->division ?: '-' }}</div>
                                        @if($user->id_division)
                                            <span class="badge bg-secondary-lt extra-small">{{ $user->id_division }}</span>
                                        @endif
                                    </div>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Department</span>
                                    <div class="text-end">
                                        <div class="fw-semibold small text-dark">{{ $user->department ?: '-' }}</div>
                                        @if($user->id_department)
                                            <span class="badge bg-secondary-lt extra-small">{{ $user->id_department }}</span>
                                        @endif
                                    </div>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Team</span>
                                    <div class="text-end">
                                        <div class="fw-semibold small text-dark">{{ $user->team ?: '-' }}</div>
                                        @if($user->id_team)
                                            <span class="badge bg-secondary-lt extra-small">{{ $user->id_team }}</span>
                                        @endif
                                    </div>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">SAP Code</span>
                                    <span class="badge bg-light text-dark border">{{ $user->sap_code ?: '-' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Section C: Informasi HR & Jabatan -->
                <div class="col-lg-4 col-md-12">
                    <div class="card border border-light-subtle shadow-sm rounded-3 h-100 detail-info-card">
                        <div class="card-header bg-transparent border-bottom py-3 px-3 d-flex align-items-center gap-2">
                            <div class="rounded-2 p-1 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="ti ti-briefcase fs-2"></i>
                            </div>
                            <h4 class="card-title fw-bold text-dark mb-0">C. Informasi HR & Jabatan</h4>
                        </div>
                        <div class="card-body p-3">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Nomor Telepon</span>
                                    <span class="fw-semibold small text-dark">{{ $user->cell_phone ?: '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Jenis Kelamin</span>
                                    <span class="fw-semibold small text-dark">
                                        @if($user->sex === 'M')
                                            <i class="ti ti-gender-male text-primary me-1"></i> Laki-Laki (M)
                                        @elseif($user->sex === 'F')
                                            <i class="ti ti-gender-female text-danger me-1"></i> Perempuan (F)
                                        @else
                                            {{ $user->sex ?: '-' }}
                                        @endif
                                    </span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Jabatan / Job Position</span>
                                    <div class="text-end">
                                        <div class="fw-semibold small text-dark">{{ $user->jobposition ?: '-' }}</div>
                                        @if($user->id_job_position)
                                            <span class="badge bg-secondary-lt extra-small">{{ $user->id_job_position }}</span>
                                        @endif
                                    </div>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Position ID</span>
                                    <span class="badge bg-light text-dark border">{{ $user->positionID ?: '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Job Grade</span>
                                    <span class="badge bg-primary-lt text-primary">{{ $user->jobgrade ?: '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Job Clan (Rumpun)</span>
                                    <div class="text-end">
                                        <div class="fw-semibold small text-dark">{{ $user->job_clan ?: '-' }}</div>
                                        @if($user->id_job_clan)
                                            <span class="badge bg-secondary-lt extra-small">{{ $user->id_job_clan }}</span>
                                        @endif
                                    </div>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Job Family (Keluarga)</span>
                                    <div class="text-end">
                                        <div class="fw-semibold small text-dark">{{ $user->job_family ?: '-' }}</div>
                                        @if($user->id_job_family)
                                            <span class="badge bg-secondary-lt extra-small">{{ $user->id_job_family }}</span>
                                        @endif
                                    </div>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Approver Layer 1</span>
                                    <span class="badge bg-light text-dark border">{{ $user->appr1 ?: '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Approver Layer 2</span>
                                    <span class="badge bg-light text-dark border">{{ $user->appr2 ?: '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Status Resign</span>
                                    @if($user->resign === 'Y')
                                        <span class="badge bg-danger text-white">Resigned (Y)</span>
                                    @else
                                        <span class="badge bg-success-lt text-success">Aktif Bekerja (N)</span>
                                    @endif
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Flag Karyawan HR</span>
                                    <span class="badge bg-light text-dark border">{{ $user->flag !== null ? $user->flag : '-' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Autentikasi SSO POSCO</span>
                                    <span class="badge bg-green-lt text-green">
                                        <i class="ti ti-shield-check me-1"></i> Terhubung (SSO Active)
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Include edit modal for this user --}}
            @if (!$user->isSuperAdmin() && $user->id !== auth()->id() && !$user->trashed())
                @include('main.manage-role.components.edit-user-modal', ['user' => $user])
            @endif

        </div>
    </div>

@endsection