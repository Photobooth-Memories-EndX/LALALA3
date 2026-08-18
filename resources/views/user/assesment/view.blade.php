@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">
    <div class="container-xl">

        @include('user.assesment.partials.view-header')

        @include('user.assesment.partials.view-timing')

        @include('user.assesment.partials.view-instruction')

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <x-form action="{{ route('assesment.user.store', $assesment->slug) }}" method="post" id="formAssesment">
                    <input type="hidden" name="submission_token" value="{{ $assessmentSubmissionToken }}">
                    @unless(count($assesment_question) == 0)

                        @include('user.assesment.partials.view-question-navigation')

                        @include('user.assesment.partials.view-submit-footer')

                    @else

                        @include('user.assesment.partials.view-empty-state')

                    @endunless
                </x-form>
            </div>
        </div>

    </div>
</div>

@include('user.assesment.partials.view-leave-modal')

@endsection

@section('style')
    @include('user.assesment.partials.view-style')
@endsection

@section('script')
    @include('user.assesment.partials.view-script')
@endsection