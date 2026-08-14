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
                                <i class="fas fa-file-alt me-2 text-primary"></i>
                                Manage Assessment
                            </h2>
                        </div>
                        <div class="col-auto">
                            <ol class="breadcrumb breadcrumb-arrows mb-0">
                                <li class="breadcrumb-item"><a href="#">Manage</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('assesment.index') }}">Assessment</a></li>
                                <li class="breadcrumb-item active">Create</li>
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
                            {{ old('title', 'New Assessment') }}
                        </h2>
                    </div>

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-primary-lt">
                            <h3 class="card-title fw-bold mb-0">
                                <i class="fas fa-plus me-2"></i>
                                Create Assessment
                            </h3>
                        </div>

                        <div class="card-body">
                            <x-alert type="success" />
                            <x-alert type="warning" />
                            <x-alert type="danger" />

                            <x-form class="" action="{{ route('assesment.store') }}" method="post">
                                
                                {{-- 1. Assessment Configuration --}}
                                @include('main.assesment.partials.section-configuration')

                                {{-- 2. Access Period & Time Limit --}}
                                @include('main.assesment.partials.section-period-timer')

                                {{-- 3. Add Question Bank --}}
                                @include('main.assesment.partials.section-question-bank-add')

                                {{-- 4. Question Bank List --}}
                                @include('main.assesment.partials.section-question-bank-list')

                                {{-- 5. Assessment Actions --}}
                                @include('main.assesment.partials.section-actions')

                            </x-form>
                        </div>
                    </div>

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
