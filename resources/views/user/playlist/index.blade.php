@extends('layouts.sidebar-master')

@php
    $playlistAdvancedFilterCount = $playlistAdvancedFilterCount ?? collect([
        filled(request('author')) && request('author') !== 'all',
        filled(request('date_from')),
        filled(request('date_to')),
        filled(request('period_from')),
        filled(request('period_to')),
        request('sort') && !in_array(request('sort'), ['default', 'latest', 'all']),
        request('sort_views') && !in_array(request('sort_views'), ['default', 'all']),
        request('duration') && request('duration') !== 'all',
    ])->filter()->count();
@endphp

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
@include('user.playlist.components.playlist-style')
@endsection

@section('script')
@include('user.playlist.components.playlist-script')
@endsection
