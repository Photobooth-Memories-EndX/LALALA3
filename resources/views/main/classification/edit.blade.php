@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">

        <div class="row g-2 align-items-center">

            <div class="col">
                <h2 class="page-title">
                    Edit Category
                </h2>

                <div class="text-muted mt-1">
                    Perbarui nama kategori pembelajaran OWL LMS.
                </div>
            </div>

            <div class="col-auto ms-auto d-print-none">
                <ol class="breadcrumb breadcrumb-arrows">
                    <li class="breadcrumb-item">
                        <a href="#">Manage</a>
                    </li>

                    <li class="breadcrumb-item">
                        <a href="{{ route('classification.index') }}">
                            Category
                        </a>
                    </li>

                    <li class="breadcrumb-item active">
                        Edit
                    </li>
                </ol>
            </div>

        </div>

    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- HERO --}}
        <div class="card category-edit-hero border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">

                <div class="row align-items-center">

                    <div class="col-lg-8">
                        <div class="text-muted text-uppercase small fw-semibold mb-1">
                            Category Management
                        </div>

                        <h1 class="mb-2">
                            Update Category
                        </h1>

                        <div class="text-muted">
                            Ubah informasi kategori yang digunakan untuk content,
                            playlist, assessment, dan reporting OWL LMS.
                        </div>
                    </div>

                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <span class="avatar avatar-lg bg-warning-lt text-warning">
                            <i class="ti ti-edit fs-2"></i>
                        </span>
                    </div>

                </div>

            </div>
        </div>

        {{-- FORM --}}
        <x-form
            class="card border-0 shadow-sm rounded-4 overflow-hidden"
            action="{{ route('classification.update', $classification->slug) }}"
            method="put">

            <div class="card-header border-0">

                <div>
                    <h3 class="card-title mb-1">
                        Category Information
                    </h3>

                    <div class="text-muted small">
                        Edit nama kategori yang sudah tersedia.
                    </div>
                </div>

            </div>

            <div class="card-body p-4">

                <div class="row g-4">

                    <div class="col-lg-8">

                        <div class="mb-4">

                            <label class="form-label required">
                                Category Name
                            </label>

                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-category"></i>
                                </span>

                                <x-input
                                    type="text"
                                    name="classification_name"
                                    placeholder="Example: Leadership Development"
                                    value="{{ old('classification_name', $classification->classification_name) }}"
                                />
                            </div>

                            <small class="form-hint">
                                Nama ini akan tampil pada content, learning, assessment, dan filter kategori.
                            </small>

                        </div>

                        <div class="mb-0">

                            <label class="form-label">
                                Current Slug
                            </label>

                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-link"></i>
                                </span>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="{{ $classification->slug }}"
                                    readonly>
                            </div>

                            <small class="form-hint">
                                Slug digunakan sebagai alamat unik kategori.
                            </small>

                        </div>

                    </div>

                    <div class="col-lg-4">

                        <div class="card bg-warning-lt border-0 h-100">
                            <div class="card-body">

                                <div class="fw-semibold mb-2">
                                    Update Tips
                                </div>

                                <ul class="mb-0 small">
                                    <li>
                                        Pastikan nama kategori tetap jelas dan tidak ambigu.
                                    </li>

                                    <li>
                                        Hindari mengubah kategori yang sudah banyak dipakai tanpa kebutuhan jelas.
                                    </li>

                                    <li>
                                        Setelah update, cek kembali filter content dan learning report.
                                    </li>
                                </ul>

                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <div class="card-footer bg-transparent">

                <div class="d-flex justify-content-between flex-wrap gap-2">

                    <a href="{{ route('classification.index') }}"
                       class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="ti ti-arrow-left me-1"></i>
                        Back
                    </a>

                    <x-button class="btn btn-warning rounded-pill px-4">
                        <i class="ti ti-device-floppy me-1"></i>
                        Update Category
                    </x-button>

                </div>

            </div>

        </x-form>

    </div>
</div>

@endsection

@section('style')
<style>
    .rounded-4 {
        border-radius: 20px !important;
    }

    .category-edit-hero {
        background:
            radial-gradient(
                circle at top right,
                rgba(245, 159, 0, .18),
                transparent 35%
            ),
            linear-gradient(
                135deg,
                rgba(245, 159, 0, .06),
                var(--tblr-bg-surface)
            );

        border: 1px solid var(--tblr-border-color);
    }

    [data-bs-theme="dark"] .category-edit-hero,
    body.theme-dark .category-edit-hero {
        background:
            radial-gradient(
                circle at top right,
                rgba(245, 159, 0, .25),
                transparent 35%
            ),
            linear-gradient(
                135deg,
                rgba(245, 159, 0, .10),
                var(--tblr-bg-surface)
            );
    }
</style>
@endsection
