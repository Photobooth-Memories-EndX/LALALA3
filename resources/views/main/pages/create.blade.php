@extends('layouts.sidebar-master')

@section('content')
@include('main.monitoring.components.style')

{{-- Page Header --}}
<div class="page-header d-print-none mb-0 pb-0">
    <div class="container-xl">
        <!-- Back Navigation Link -->
        <a href="{{ route('pages.index') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
            <i class="ti ti-arrow-left fs-2"></i> Back
        </a>

        <!-- Create Page Hero Header Card -->
        <div class="card border border-light-subtle shadow-sm p-4 mb-3 rounded-3 bg-white hero-header-card">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 hero-icon-box" 
                         style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                        <i class="ti ti-file-plus" style="font-size: 2rem !important;"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark header-title">Create Page</h2>
                        <p class="text-muted small mb-0 header-subtitle">Create a new static page, policy, or terms document in OWL LMS.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Page Body --}}
<div class="page-body mt-0 pt-0">
    <div class="container-xl">

        @if($errors->any())
            <div class="alert alert-danger shadow-sm mb-3">
                {!! implode('', $errors->all('<div>:message</div>')) !!}
            </div>
        @endif

        <div class="card border border-light-subtle shadow-sm rounded-3 page-form-card">
            <div class="card-header bg-transparent border-0 pb-0 pt-3 px-3 px-md-4">
                <h3 class="card-title fw-bold text-dark m-0">Page Information</h3>
            </div>

            <x-form action="{{ route('pages.store') }}" method="post">
                <div class="card-body p-3 p-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark required" for="title">Title</label>
                        <input type="text" id="title" name="title" class="form-control rounded-3" placeholder="Enter page title (e.g. Terms of Service)" value="{{ old('title') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark required" for="tinymce-mytextarea">Content Description</label>
                        <textarea name="description" id="tinymce-mytextarea" rows="12" class="form-control rounded-3" placeholder="Write page content here...">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="card-footer bg-transparent border-top d-flex align-items-center justify-content-between p-3 p-md-4">
                    <a href="{{ route('pages.index') }}" class="btn btn-outline-secondary rounded-3 px-3">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-3 d-inline-flex align-items-center gap-2" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check fs-2"></i> Submit Page
                    </button>
                </div>
            </x-form>
        </div>

    </div>
</div>

@endsection

@section('style')
    <style>
        .hero-header-card,
        .page-form-card {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.75rem !important;
        }

        /* Dark Mode Support */
        [data-bs-theme="dark"] .hero-header-card,
        [data-theme="dark"] .hero-header-card,
        body.theme-dark .hero-header-card,
        [data-bs-theme="dark"] .page-form-card,
        [data-theme="dark"] .page-form-card,
        body.theme-dark .page-form-card {
            background-color: #1e293b !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] .hero-icon-box,
        [data-theme="dark"] .hero-icon-box,
        body.theme-dark .hero-icon-box {
            background-color: rgba(0, 59, 109, 0.35) !important;
            color: #60a5fa !important;
        }

        [data-bs-theme="dark"] .header-title,
        [data-theme="dark"] .header-title,
        body.theme-dark .header-title,
        [data-bs-theme="dark"] .card-title,
        [data-theme="dark"] .card-title,
        body.theme-dark .card-title,
        [data-bs-theme="dark"] .form-label,
        [data-theme="dark"] .form-label,
        body.theme-dark .form-label {
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] .header-subtitle,
        [data-theme="dark"] .header-subtitle,
        body.theme-dark .header-subtitle {
            color: #94a3b8 !important;
        }

        [data-bs-theme="dark"] .form-control,
        [data-theme="dark"] .form-control,
        body.theme-dark .form-control {
            background-color: #0f172a !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] .card-footer,
        [data-theme="dark"] .card-footer,
        body.theme-dark .card-footer {
            border-color: #334155 !important;
        }
    </style>
@endsection

@section('script')
<script src="{{ asset('Tabler/dist/libs/tinymce/tinymce.min.js') }}" defer></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
      let options = {
        selector: '#tinymce-mytextarea',
        height: 350,
        menubar: false,
        statusbar: false,
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | formatselect | ' +
          'bold italic forecolor backcolor | alignleft aligncenter ' +
          'alignright alignjustify | bullist numlist outdent indent | ' +
          'removeformat | table code',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }'
      };
      if (document.documentElement.dataset.bsTheme === 'dark' || localStorage.getItem('owl-dark-mode') === 'dark') {
        options.skin = 'oxide-dark';
        options.content_css = 'dark';
      }
      if (typeof tinyMCE !== 'undefined') {
        tinyMCE.init(options);
      }
    });
</script>
@endsection

