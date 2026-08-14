@extends('layouts.sidebar-master')

@section('content')
    <div class="assessment-page-header page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">
                    <div class="row align-items-center g-2">
                        <div class="col">
                            <div class="page-pretitle text-muted">
                                Learning Management System
                            </div>
                            <h2 class="page-title fw-bold mb-0">
                                <i class="fas fa-link me-2 text-primary"></i>
                                Manage Assessment
                            </h2>
                        </div>
                        <div class="col-auto">
                            <ol class="breadcrumb breadcrumb-arrows mb-0">
                                <li class="breadcrumb-item"><a href="#">Manage</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('assesment.index') }}">Assessment</a></li>
                                <li class="breadcrumb-item active">New Link</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">

                    {{-- Dynamic Assessment Title Area displayed above Card --}}
                    <div class="mb-3">
                        <h2 class="page-title fw-bold text-primary" id="dynamic_page_title">
                            {{ $assesment->title }}
                        </h2>
                    </div>

                    <x-form class="card shadow-sm border-0 mb-4" action="{{ route('assesment.updatelink', $assesment->slug) }}" method="put">
                        <div class="card-header bg-primary-lt">
                            <div class="row align-items-center w-100">
                                <div class="col">
                                    <h3 class="card-title fw-bold mb-1">
                                        <i class="fas fa-edit me-2 text-primary"></i>
                                        New Link Assessment
                                    </h3>
                                    <div class="text-muted small">
                                        Update assessment link, batch number, and assessment access time.
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-primary-lt text-primary">
                                        {{ strtoupper($assesment->slug ?? 'ASSESSMENT') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            {{-- 1. Link Configuration --}}
                            @include('main.assesment.partials.section-link-configuration', ['assesment' => $assesment])

                            {{-- 2. Access Period & Time Limit --}}
                            @include('main.assesment.partials.section-period-timer', ['assesment' => $assesment, 'hideRemedials' => true])

                            {{-- 3. Assessment Actions --}}
                            <div class="owl-section-block pt-3 border-top">
                                <div class="owl-section-header mb-3 pb-2 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <span class="owl-section-number me-2">3</span>
                                        <h3 class="owl-section-title mb-0 fw-bold">Assessment Actions</h3>
                                    </div>
                                    <div class="text-muted small ms-4 ps-1">
                                        Save or cancel link changes.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('assesment.index') }}" class="btn btn-light px-4">
                                        Cancel
                                    </a>
                                    <x-button class="btn btn-primary px-5">
                                        <i class="fas fa-save me-2"></i>
                                        Submit
                                    </x-button>
                                </div>
                            </div>
                        </div>
                    </x-form>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    @include('main.assesment.partials.form-styles')
@endsection

@section('script')
    @include('main.assesment.partials.form-scripts')
@endsection