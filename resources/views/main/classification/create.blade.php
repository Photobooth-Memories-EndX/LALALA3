```blade
@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">

        <div class="row g-2 align-items-center">

            <div class="col">
                <h2 class="page-title">
                    Create Category
                </h2>

                <div class="text-muted mt-1">
                    Tambahkan kategori baru untuk mengelompokkan content dan learning module OWL LMS.
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
                        Create
                    </li>
                </ol>

            </div>

        </div>

    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- HERO --}}
        <div class="card category-create-hero border-0 shadow-sm rounded-4 mb-4">

            <div class="card-body p-4">

                <div class="row align-items-center">

                    <div class="col-lg-8">

                        <div class="text-muted text-uppercase small fw-semibold mb-1">
                            Category Management
                        </div>

                        <h1 class="mb-2">
                            Create New Category
                        </h1>

                        <div class="text-muted">
                            Kategori digunakan untuk mengelompokkan learning content, playlist,
                            assessment, dan reporting agar lebih terstruktur.
                        </div>

                    </div>

                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">

                        <span class="avatar avatar-lg bg-primary-lt text-primary">
                            <i class="ti ti-category fs-2"></i>
                        </span>

                    </div>

                </div>

            </div>

        </div>

        {{-- FORM --}}
        <x-form
            class="card border-0 shadow-sm rounded-4 overflow-hidden"
            action="{{ route('classification.store') }}"
            method="post">

            <div class="card-header border-0">

                <div>
                    <h3 class="card-title mb-1">
                        Category Information
                    </h3>

                    <div class="text-muted small">
                        Isi informasi kategori yang akan digunakan pada OWL LMS.
                    </div>
                </div>

            </div>

            <div class="card-body p-4">

                <div class="row">

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
                                    value="{{ old('classification_name') }}"
                                />

                            </div>

                            <small class="form-hint">
                                Nama kategori akan digunakan pada content,
                                playlist, assessment, dan reporting.
                            </small>

                        </div>

                    </div>

                    <div class="col-lg-4">

                        <div class="card bg-primary-lt border-0">

                            <div class="card-body">

                                <div class="fw-semibold mb-2">
                                    Tips
                                </div>

                                <ul class="mb-0 small">

                                    <li>
                                        Gunakan nama yang jelas dan mudah dipahami.
                                    </li>

                                    <li>
                                        Hindari nama kategori yang terlalu panjang.
                                    </li>

                                    <li>
                                        Pastikan kategori tidak duplikat.
                                    </li>

                                </ul>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <div class="card-footer bg-transparent">

                <div class="d-flex justify-content-between">

                    <a href="{{ route('classification.index') }}"
                       class="btn btn-outline-secondary rounded-pill px-4">

                        <i class="ti ti-arrow-left me-1"></i>
                        Back

                    </a>

                    <x-button class="btn btn-primary rounded-pill px-4">

                        <i class="ti ti-device-floppy me-1"></i>
                        Save Category

                    </x-button>

                </div>

            </div>

        </x-form>

    </div>
</div>

@endsection

@section('style')

<style>

    .rounded-4{
        border-radius:20px !important;
    }

    .category-create-hero{
        background:
            radial-gradient(
                circle at top right,
                rgba(32,107,196,.15),
                transparent 35%
            ),
            linear-gradient(
                135deg,
                rgba(32,107,196,.05),
                var(--tblr-bg-surface)
            );

        border:1px solid var(--tblr-border-color);
    }

    [data-bs-theme="dark"] .category-create-hero,
    body.theme-dark .category-create-hero{
        background:
            radial-gradient(
                circle at top right,
                rgba(32,107,196,.25),
                transparent 35%
            ),
            linear-gradient(
                135deg,
                rgba(32,107,196,.10),
                var(--tblr-bg-surface)
            );
    }

</style>

@endsection
```
