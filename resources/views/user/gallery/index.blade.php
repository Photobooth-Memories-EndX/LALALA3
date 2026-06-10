@extends('layouts.sidebar-master')

@section('content')

<div class="container-xl mt-4">

<!-- HERO -->
<div class="gallery-hero">

    <div class="row align-items-center">

        <div class="col-lg-8">

            <span class="hero-badge">
                INTERNAL MEMORIES
            </span>

            <h1 class="hero-title">
                Learning Event
                <br>
                Gallery Hub
            </h1>

            <p class="hero-sub mt-4">
                Capturing the journey of growth and innovation across
                workshops, technical training and professional development.
            </p>

            <a href="#gallery-list" class="btn btn-light btn-lg mt-3">
                Explore Gallery →
            </a>

        </div>

        <div class="col-lg-4">

            <div class="row g-3">

                <div class="col-6">
                    <div class="stat-box">
                        <h2>{{ $totalEvents ?? count($eventphoto) }}</h2>
                        <small>Total Events</small>
                    </div>
                </div>

                <div class="col-6">
                    <div class="stat-box">
                        <h2>{{ $totalPhotos ?? '--' }}</h2>
                        <small>Total Photos</small>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

<!-- SEARCH -->
<div class="mb-4">

    <input
        type="text"
        id="gallerySearch"
        class="form-control form-control-lg"
        placeholder="Search events, workshops, moments..."
    >

</div>

<!-- FILTER -->
<div class="filter-bar mb-4">

    <div class="d-flex gap-2 flex-wrap">

        <button class="filter-btn active">
            All Events
        </button>

        <button class="filter-btn">
            Leadership
        </button>

        <button class="filter-btn">
            Safety Training
        </button>

        <button class="filter-btn">
            Workshop
        </button>

    </div>

</div>

<!-- CARD LIST -->
<div class="row" id="gallery-list">

    @forelse($eventphoto as $list)

@php

    $photos = json_decode($list->photos, true);

    $cover = $list->thumbnail;

    if (!empty($photos)) {
        $cover = $photos[0];
    }

@endphp


<div
    class="col-lg-6 mb-4 gallery-item"
    data-category="{{ strtolower($list->category ?? 'workshop') }}"
>

    <div class="card gallery-card">
<div class="gallery-image">

   <img
    src="https://picsum.photos/1600/800?random={{ $list->id }}"
    alt="{{ $list->title }}"
>
            <span class="badge-category">
                {{ ucfirst($list->category ?? 'Workshop') }}
            </span>

    

        </div>

        <div class="card-body">

            <h3 class="gallery-title">
                {{ $list->title }}
            </h3>

            <p class="text-muted">
                Documentation and learning memories from training sessions,
                workshops and internal development programs.
            </p>

            <hr>

            <div class="author-section">

                <div>

                    <strong>
                        {{ $list->uploader }}
                    </strong>

                    <br>

                    <small class="text-muted">
                        {{ $list->created_at->diffForHumans() }}
                    </small>

                </div>

                <div>

                    👁 {{ $list->views }}

                    &nbsp;

                    💬 {{ $list->comments_count }}

                </div>

            </div>

            <a
                href="{{ route('gallery.user.view',$list->slug) }}"
                class="btn btn-primary w-100 mt-3"
            >
                View Gallery
            </a>

        </div>

    </div>

</div>

@empty

<div class="col-12">

    <div class="alert alert-warning">
        No Gallery Data Found
    </div>

</div>

@endforelse

</div> {{-- END #gallery-list --}}

</div> {{-- END container-xl --}}

@endsection

@section('style')

<style>

.gallery-hero{
    background: linear-gradient(135deg,#2563eb,#4f8cff);
    border-radius:24px;
    padding:50px;
    color:white;
    margin-bottom:30px;
}

.hero-badge{
    background:rgba(255,255,255,.15);
    padding:8px 16px;
    border-radius:999px;
    font-size:12px;
}

.hero-title{
    font-size:56px;
    font-weight:700;
    line-height:1.1;
    margin-top:20px;
}

.hero-sub{
    font-size:18px;
    color:#dbeafe;
    max-width:650px;
}

.stat-box{
    background:white;
    color:#111827;
    border-radius:20px;
    padding:30px;
    text-align:center;
    height:100%;
}

.stat-box h2{
    font-size:38px;
    font-weight:700;
    margin:0;
}

.filter-bar{
    background:white;
    border-radius:18px;
    padding:15px;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
}

.filter-btn{
    border:none;
    background:#eef2ff;
    border-radius:999px;
    padding:10px 18px;
}

.filter-btn.active{
    background:#2563eb;
    color:white;
}

.gallery-card{

    height:100%;

    display:flex;

    flex-direction:column;

    border:none;

    border-radius:22px;

    overflow:hidden;

    box-shadow:0 5px 20px rgba(0,0,0,.08);

    transition:.3s;
}

.gallery-card:hover{
    transform:translateY(-6px);
}

.gallery-card .card-body{

    flex:1;

    display:flex;

    flex-direction:column;
}

.author-section{
    margin-top:auto;
}

.gallery-image{
    position:relative;
}

.gallery-image img{
    width:100%;
    height:320px;
    object-fit:cover;
}

.badge-category{
    position:absolute;
    top:15px;
    left:15px;
    background:#2563eb;
    color:white;
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
}

.photo-count{
    position:absolute;
    bottom:15px;
    right:15px;
    background:white;
    padding:6px 12px;
    border-radius:12px;
    font-size:12px;
}

.gallery-title{

    font-size:24px;

    font-weight:700;

    line-height:1.35;

    min-height:65px;

    overflow:hidden;

    display:-webkit-box;

    -webkit-line-clamp:2;

    -webkit-box-orient:vertical;
}

.author-section{

    margin-top:auto;

    display:flex;

    justify-content:space-between;

    align-items:center;
}

html[data-bs-theme="dark"] .filter-btn,
body[data-bs-theme="dark"] .filter-btn{
    background:#1f2937 !important;
    color:#ffffff !important;
    border:1px solid #374151;
}

html[data-bs-theme="dark"] .filter-btn.active,
body[data-bs-theme="dark"] .filter-btn.active{
    background:#2563eb !important;
    color:white !important;
}

html[data-bs-theme="dark"] .gallery-card,
body[data-bs-theme="dark"] .gallery-card{
    background:#111827 !important;
}

html[data-bs-theme="dark"] .gallery-title,
body[data-bs-theme="dark"] .gallery-title{
    color:white !important;
}

html[data-bs-theme="dark"] .text-muted,
body[data-bs-theme="dark"] .text-muted{
    color:#cbd5e1 !important;
}

html[data-bs-theme="dark"] .filter-bar,
body[data-bs-theme="dark"] .filter-bar{
    background:#111827 !important;
}

html[data-bs-theme="dark"] #gallerySearch,
body[data-bs-theme="dark"] #gallerySearch{
    background:#1f2937 !important;
    color:white !important;
    border-color:#374151 !important;
}

html[data-bs-theme="dark"] #gallerySearch::placeholder,
body[data-bs-theme="dark"] #gallerySearch::placeholder{
    color:#94a3b8 !important;
}

</style>


@endsection

@section('script')

<script>

// SEARCH
document.getElementById('gallerySearch')
.addEventListener('keyup', function(){

    let value = this.value.toLowerCase();

    document.querySelectorAll('.gallery-item')
    .forEach(function(item){

        item.style.display =
        item.innerText.toLowerCase().includes(value)
        ? ''
        : 'none';

    });

});


// FILTER
const buttons =
document.querySelectorAll('.filter-btn');

buttons.forEach(btn => {

    btn.addEventListener('click', function(){

        buttons.forEach(b =>
            b.classList.remove('active')
        );

        this.classList.add('active');

        const category =
        this.innerText.toLowerCase();

        document
        .querySelectorAll('.gallery-item')
        .forEach(item => {

            if(category === 'all events'){

                item.style.display='';
                return;
            }

            const itemCategory =
            item.dataset.category;

            item.style.display =
            itemCategory === category
            ? ''
            : 'none';

        });

    });

});

</script>

@endsection