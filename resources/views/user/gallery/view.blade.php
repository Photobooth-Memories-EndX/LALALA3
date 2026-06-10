@extends('layouts.sidebar-master')

@section('content')

@php
$url_lightbox_js = asset('Tabler/dist/libs/fslightbox/index.js?1684106062');

$search = ['[', ']', '"'];
$arraypic = explode(',', $eventphoto->photos);


$url_lightbox_js = asset('Tabler/dist/libs/fslightbox/index.js?1684106062');

$search = ['[', ']', '"'];

$arraypic = explode(',', $eventphoto->photos);

@endphp

<div class="container-xl mt-4">

{{-- HERO SECTION --}}
<div
    class="event-hero"
    style="
        background-image:url('https://picsum.photos/1600/800?random={{ $eventphoto->id }}');
        background-size:cover;
        background-position:center;
    "
>

    <div class="event-overlay">

        <span class="event-badge">
            WORKSHOP
        </span>

        <h1 class="event-title">
            {{ $eventphoto->title }}
        </h1>

        <p class="event-description">
            Documentation and learning reflections from this event.
        </p>

        <div class="event-meta">

            <span>
                📅 {{ date('d M Y', strtotime($eventphoto->eventdate)) }}
            </span>

            <span>
                📷 {{ $photoCount ?? count(json_decode($eventphoto->photos, true) ?? []) }} Photos
            </span>

            <span>
                👁 {{ $eventphoto->views }}
            </span>

        </div>

    </div>

</div>

<div class="row">

    {{-- GALLERY --}}
    <div class="col-lg-8">

        <div class="section-header">

            <h3>
                Event Gallery
            </h3>

            <span class="text-primary">
                {{ $photoCount }} Photos
            </span>

        </div>

        <div class="gallery-grid">

            @foreach ($arraypic as $listphoto)

                <a
                    data-fslightbox="gallery"
                    href="{{ asset('storage/gallery/photo/' . $eventphoto->id . '/' . str_replace($search, '', $listphoto)) }}"
                    class="gallery-item"
                >

                    <img
                        src="{{ asset('storage/gallery/photo/' . $eventphoto->id . '/' . str_replace($search, '', $listphoto)) }}"
                        alt=""
                    >

                </a>

            @endforeach

        </div>

    </div>

    {{-- SIDEBAR --}}
    <div class="col-lg-4">

        <div class="info-card">

            <h5>
                Event Information
            </h5>

            <hr>

            <p>
                <strong>Uploader</strong>
                <br>
                {{ $eventphoto->uploader }}
            </p>

            <p>
                <strong>Date</strong>
                <br>
                {{ date('d M Y', strtotime($eventphoto->eventdate)) }}
            </p>

            <p>
                <strong>Views</strong>
                <br>
                {{ $eventphoto->views }}
            </p>

            <p>
                <strong>Total Photos</strong>
                <br>
                {{ $photoCount }}
            </p>

        </div>

    </div>

</div>

{{-- DISCUSSION --}}
<div class="discussion-card mt-5">

    <h3 class="discussion-title">
        Discussion & Reflection
    </h3>

    <div class="comment-wrapper">

        <x-comment
            :content="$eventphoto"
            :comment="$comment"
            type="eventphoto"
        />

    </div>

</div>

@endsection

@section('style')

<style>

.event-hero{
    position:relative;
    height:420px;
    border-radius:24px;
    overflow:hidden;
    margin-bottom:30px;

    background:url('https://picsum.photos/1600/800')
    center center / cover;
}

.event-overlay{
    position:absolute;
    inset:0;

    padding:50px;

    background:
    linear-gradient(
        rgba(0,0,0,.4),
        rgba(0,0,0,.8)
    );

    display:flex;
    flex-direction:column;
    justify-content:flex-end;
}

.event-badge{
    display:inline-block;
    width:max-content;

    padding:8px 16px;

    border-radius:999px;

    background:#2563eb;

    color:white;

    font-size:12px;
}

.event-title{

    color:white;

    font-size:48px;

    font-weight:700;

    line-height:1.15;

    max-width:900px;

    margin-top:15px;

    overflow:hidden;

    display:-webkit-box;

    -webkit-line-clamp:3;

    -webkit-box-orient:vertical;
}

.event-description{
    color:#e5e7eb;
    max-width:700px;
}

.event-meta{

    display:flex;

    flex-wrap:wrap;

    gap:20px;

    margin-top:20px;

    color:#f8fafc;

    font-size:15px;
}

.section-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.gallery-grid{

    display:grid;

    grid-template-columns:
    repeat(auto-fill,minmax(280px,1fr));

    gap:20px;
}

.gallery-item{

    display:block;

    overflow:hidden;

    border-radius:18px;
}

.gallery-image img{
    width:100%;
    height:280px;
    object-fit:cover;
    display:block;
}

.gallery-card{
    overflow:hidden;
}

.gallery-item:hover img{
    transform:scale(1.03);
}

.info-card{
    background:var(--tblr-bg-surface,#fff);

    border-radius:20px;

    padding:25px;

    border:1px solid rgba(0,0,0,.08);

    position:sticky;
    top:20px;
}

.discussion-card{

    background:white;

    border-radius:24px;

    padding:35px;

    margin-top:40px;

    box-shadow:0 4px 20px rgba(0,0,0,.05);
}

[data-bs-theme="dark"] .discussion-card{

    background:#1f2937;
}

.comment-wrapper{

    margin-top:25px;

    padding-top:20px;

    border-top:1px solid rgba(0,0,0,.08);
}

@media(max-width:768px){

    .event-hero{
        height:350px;
    }

    .event-title{
        font-size:30px;
        line-height:1.2;
    }

    .event-meta{
        flex-direction:column;
        gap:8px;
    }

    .event-overlay{
        padding:25px;
    }

}

</style>

@endsection

@section('script')

<script src="{{ $url_lightbox_js }}"></script>

@endsection
