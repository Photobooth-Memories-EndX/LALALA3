@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-learning-hours-landing-page">
    <div class="container-xl">

        <!-- Page Header -->
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <div class="text-uppercase text-muted small fw-semibold">Manage &bull; Learning Hours</div>
                <h1 class="page-title mb-1">Learning Hour Management</h1>
                <div class="text-muted">Manage learning hours for Content User and Playlist Content User records.</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('manage.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </header>

        <!-- Module Selection Cards -->
        <div class="row g-3">
            {{-- Card 1: Content User Learning Hours --}}
            <div class="col-sm-6 col-lg-4 mb-3">
                <a href="{{ route('manage.learning-hours.content-user.index') }}" class="text-decoration-none text-reset">
                    <div class="card border-0 shadow-sm h-100 admin-menu-card hover-shadow transition">
                        <div class="card-body p-4">
                            <span class="avatar avatar-md bg-primary-lt text-primary mb-3">
                                <i class="ti ti-file-text fs-2"></i>
                            </span>

                            <h3 class="mb-2">
                                Content User Learning Hours
                            </h3>

                            <p class="text-muted small mb-4">
                                Manage and calculate learning hours for Content User records.
                            </p>

                            <div class="d-flex align-items-center text-primary fw-semibold small">
                                Manage Content Users
                                <i class="ti ti-arrow-right ms-1"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Card 2: Playlist Content User Learning Hours --}}
            <div class="col-sm-6 col-lg-4 mb-3">
                <a href="{{ route('manage.learning-hours.playlist-content-user.index') }}" class="text-decoration-none text-reset">
                    <div class="card border-0 shadow-sm h-100 admin-menu-card hover-shadow transition">
                        <div class="card-body p-4">
                            <span class="avatar avatar-md bg-primary-lt text-primary mb-3">
                                <i class="ti ti-playlist fs-2"></i>
                            </span>

                            <h3 class="mb-2">
                                Playlist Content User Learning Hours
                            </h3>

                            <p class="text-muted small mb-4">
                                Manage and inspect actual learning hours for Playlist Content User records.
                            </p>

                            <div class="d-flex align-items-center text-primary fw-semibold small">
                                Manage Playlist Records
                                <i class="ti ti-arrow-right ms-1"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Card 3: Dry Run Migration --}}
            <div class="col-sm-6 col-lg-4 mb-3">
                <a href="{{ route('manage.learning-hours.dry-run.index') }}" class="text-decoration-none text-reset">
                    <div class="card border-0 shadow-sm h-100 admin-menu-card hover-shadow transition">
                        <div class="card-body p-4">
                            <span class="avatar avatar-md bg-warning-lt text-warning mb-3">
                                <i class="ti ti-test-pipe fs-2"></i>
                            </span>

                            <h3 class="mb-2">
                                Dry Run Migration
                            </h3>

                            <p class="text-muted small mb-4">
                                Preview historical learning progress migration from Content User to Playlist Content User.
                            </p>

                            <div class="d-flex align-items-center text-warning fw-semibold small">
                                Open Dry Run
                                <i class="ti ti-arrow-right ms-1"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
