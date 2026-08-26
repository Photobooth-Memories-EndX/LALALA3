@extends('layouts.sidebar-master')

@section('content')
    @include('main.assesment.partials.subpage-header', [
        'breadcrumb'     => ['Create'],
        'title'          => old('title', 'Create Assessment'),
        'titleId'        => 'dynamic_page_title',
        'containerClass' => 'col-lg-10 col-xl-9',
    ])

    <div class="page-body">
        <div class="container-xl">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">

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
