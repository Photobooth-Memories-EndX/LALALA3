@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">

        @include('user.playlist.components.playlist-hero')

        @include('user.playlist.components.playlist-continue-learning')

        @include('user.playlist.components.playlist-filters')

        @if(count($trainingplan) > 0)

            @include('user.playlist.components.playlist-grid')

            <div class="playlist-pagination mt-4 d-flex justify-content-center">
                {{ $trainingplan->links() }}
            </div>

        @else

            @include('user.playlist.components.playlist-empty')

        @endif

    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/content.css') }}">
<link rel="stylesheet" href="{{ asset('css/playlist.css') }}">
@endsection

@section('script')
@include('user.playlist.components.playlist-script')
@endsection
