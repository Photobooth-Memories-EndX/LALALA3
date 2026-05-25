@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">

    <div class="container-xl">

        {{-- HERO --}}
        @include('user.news.partials.hero')

        <div class="row mt-4">

            {{-- LEFT CONTENT --}}
            <div class="col-lg-9">

                <div class="d-flex justify-content-between align-items-center mb-4">

                    <h2 class="page-title fw-bold">
                        Hub Highlights
                    </h2>

                </div>

                <div class="row">

                    @if($news->count() > 1)

                        @foreach($news->slice(1) as $item)

                            @include('user.news.partials.card')

                        @endforeach

                    @else

                        <div class="col-12">

                            <div class="card border-0 shadow-sm">

                                <div class="card-body text-center py-5">

                                    <h3 class="mb-3">
                                        No News Available
                                    </h3>

                                    <p class="text-secondary">
                                        Please create news first.
                                    </p>

                                </div>

                            </div>

                        </div>

                    @endif

                </div>

            </div>

            {{-- RIGHT SIDEBAR --}}
            <div class="col-lg-3">

                @include('user.news.partials.sidebar')

            </div>

        </div>

    </div>

</div>

@endsection

@section('style')

<style>

body{
    background:#f5f7fb;
}

/* HERO */

.owl-hero-card{
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    border-radius:28px;
    overflow:hidden;
}

.hero-image{
    min-height:450px;
    object-fit:cover;
}

/* CARD */

.owl-news-card{
    border-radius:24px;
    overflow:hidden;
    transition:0.3s;
}

.owl-news-card:hover{
    transform:translateY(-6px);
    box-shadow:0 20px 40px rgba(0,0,0,0.08);
}

.owl-sidebar-card{
    border-radius:24px;
}

/* IMAGE */

.owl-news-image{
    width:100%;
    height:240px;
    object-fit:cover;
}

/* TRENDING */

.trending-number{
    font-size:32px;
    color:#cbd5e1;
    font-weight:700;
}

</style>

@endsection
