@extends('layouts.sidebar-master')

@section('content')

@php
$content_video = $content->filter(function ($list){
    return in_array($list->type, ['YouTube', 'Video']);
});

$content_pdf = $content->filter(function ($list){
    return $list->type == 'Document';
});
@endphp

<div class="page-body">
    <div class="container-fluid px-4">
        <div class="row g-4 align-items-start">

            <div class="col-xl-9 col-lg-8">

                <div class="modern-hero">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="hero-badge">
                                Learning Management System
                            </span>
                            <h1 class="hero-title">
                                Welcome Back, {{ explode(' ', auth()->user()->name)[0] }}
                            </h1>
                            <p class="hero-subtitle">
                                Continue your learning journey and improve your professional skills.
                            </p>
                            <a href="#continue-learning" class="btn btn-light btn-lg">
                                Continue Learning
                            </a>
                        </div>

                        <div class="col-lg-4 text-center">
                            <div class="hero-circle">
                                <h2>
                                    {{ $content_user->where('finish',1)->count() }}
                                </h2>
                                <span>
                                    Completed Course
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="ti ti-player-play"></i>
                            <h3>{{ $content_user->where('finish',0)->count() }}</h3>
                            <p>In Progress</p>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="ti ti-check"></i>
                            <h3>{{ $content_user->where('finish',1)->count() }}</h3>
                            <p>Completed</p>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="ti ti-photo"></i>
                            <h3>{{ $eventphoto->count() }}</h3>
                            <p>Gallery</p>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="ti ti-news"></i>
                            <h3>{{ $news->count() }}</h3>
                            <p>News</p>
                        </div>
                    </div>
                </div>

                <div class="modern-card mt-4">
                    <div class="card-header border-0">
                        <h2 class="mb-0">
                            Recommended Learning
                        </h2>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            @foreach($top_video->take(4) as $item)
                            <div class="col-md-6 mb-4">
                                <div class="course-card">
                                    @if($item->type == 'YouTube')
                                        <img src="https://img.youtube.com/vi/{{ $item->link }}/mqdefault.jpg" class="img-fluid w-100">
                                    @endif

                                    <div class="course-body">
                                        <h5>
                                            <a href="{{ route('content.user.view',$item->slug) }}">
                                                {{ Str::limit($item->title,60) }}
                                            </a>
                                        </h5>
                                        <span class="badge bg-primary">
                                            {{ $item->duration }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div> <div class="col-xl-3 col-lg-4">

                <div class="modern-profile-card">
                    <div class="d-flex align-items-center">
                        <div class="modern-avatar">
                            {{ substr(auth()->user()->initials, 0, 2) }}
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-0">
                                {{ ucwords(strtolower(auth()->user()->name ?? '')) }}
                            </h5>
                            <small class="text-muted">
                                {{ auth()->user()->team ?? '' }}
                            </small>
                        </div>
                    </div>
                </div>

                <div class="modern-widget mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            Learning Progress
                        </h5>
                        <span class="badge bg-primary">
                            {{ $content_user->where('finish',1)->count() }}
                        </span>
                    </div>

                    <div class="progress progress-modern">
                        <div class="progress-bar"
                             style="width: {{ ($content_user->count() > 0) ? round(($content_user->where('finish',1)->count() / $content_user->count()) * 100) : 0 }}%">
                        </div>
                    </div>

                    <p class="mt-3 text-muted">
                        {{ $content_user->where('finish',1)->count() }} Completed Courses
                    </p>
                </div>

                <div class="row mt-4">

                    <div class="col-md-6">
                        <div class="modern-widget h-100">
                            <div class="d-flex justify-content-between mb-3">
                                <h5 class="mb-0">Latest Gallery</h5>
                            </div>

                            @if($eventphoto->count())
                                @php
                                    $gallery = $eventphoto->first();
                                @endphp

                                <a href="{{ route('gallery.user.view',$gallery->slug) }}">
                                    @if(!empty($gallery->thumbnail))
                                        <img src="{{ asset('/storage/gallery/photo/'.$gallery->id.'/'.$gallery->thumbnail) }}"class="gallery-cover">
                                    @else
                                        <img src="https://via.placeholder.com/600x350?text=Gallery" class="gallery-cover">
                                    @endif

                                    <h4 class="gallery-title">
                                        {{ Str::limit($gallery->title,50) }}
                                    </h4>
                                </a>
                            @endif
                        </div>
                    </div> <div class="col-md-6">
                        <div class="modern-widget h-100">
                            <div class="d-flex justify-content-between mb-3">
                                <h5 class="mb-0">Latest News</h5>
                                <a href="{{ route('news.user.index') ?? '#' }}">
                                    View All
                                </a>
                            </div>

                            @foreach($news as $list)
                                <div class="news-item">
                                    <a href="{{ route('news.user.view',$list->slug) }}" class="news-link">
                                        {{ Str::limit($list->title,70) }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div> </div> </div> </div> </div> </div> @endsection


@section('style')

<link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>

<style>

/* =======================================================
   GLOBAL
======================================================= */

.page-body{
    background:#f5f7fb;
    min-height:100vh;
}

.card{
    border:none;
}

a{
    text-decoration:none;
}

/* =======================================================
   HERO SECTION
======================================================= */

.modern-hero{
    background:linear-gradient(
        135deg,
        #2563eb 0%,
        #1d4ed8 100%
    );
    border-radius:28px;
    padding:50px;
    color:white;
    margin-bottom:30px;
    position:relative;
    overflow:hidden;
}

.modern-hero::after{
    content:'';
    position:absolute;
    width:300px;
    height:300px;
    right:-80px;
    top:-80px;
    border-radius:50%;
    background:rgba(255,255,255,.08);
}

.hero-badge{
    display:inline-block;
    background:white;
    color:#2563eb;
    padding:8px 18px;
    border-radius:999px;
    font-weight:600;
    margin-bottom:20px;
}

.hero-title{
    color:white;
    font-size:clamp(32px,4vw,56px);
    font-weight:700;
    line-height:1.1;
    margin-bottom:15px;
    word-break:break-word;
}

.hero-subtitle{
    font-size:18px;
    opacity:.9;
    margin-bottom:25px;
}

.hero-circle{
    width:180px;
    height:180px;
    border-radius:50%;
    background:rgba(255,255,255,.15);
    backdrop-filter:blur(10px);
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    margin:auto;
}

.hero-circle h2{
    font-size:48px;
    color:white;
    margin:0;
}

.hero-circle span{
    color:white;
}

/* =======================================================
   STATS
======================================================= */

.stat-card{
    background:white;
    border-radius:22px;
    padding:25px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,.06);
    transition:.3s;
    height:100%;
}

.stat-card:hover{
    transform:translateY(-5px);
}

.stat-card i{
    font-size:34px;
    color:#2563eb;
}

.stat-card h3{
    margin-top:10px;
    margin-bottom:5px;
    font-weight:700;
}

.stat-card p{
    color:#6b7280;
    margin:0;
}

/* =======================================================
   COURSE CARD
======================================================= */

.modern-card{
    border:none;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.06);
}

.modern-card .card-header h2{
    font-size:28px;
    font-weight:700;
    color:#111827;
}

.course-card{
    background:white;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 8px 20px rgba(0,0,0,.06);
    transition:.3s;
    height:100%;
}

.course-card:hover{
    transform:translateY(-5px);
}

.course-card img{
    width:100%;
    height:220px;
    object-fit:cover;
}

.course-body{
    padding:16px;
}

.course-body h5{
    min-height:80px;
    font-size:18px;
    font-weight:700;
    line-height:1.6;
    margin-bottom:12px;
}

.course-body h5 a{
    color:#111827;
}

.course-body .badge{
    font-size:13px;
    padding:8px 12px;
}

.course-body a{
    color:#111827;
}

.course-body a:hover{
    color:#2563eb;
}

/* =======================================================
   PROFILE SIDEBAR
======================================================= */

.modern-profile-card{
    background:white;
    border-radius:24px;
    padding:30px;
    box-shadow:0 10px 30px rgba(0,0,0,.06);
}

.modern-avatar{
    width:72px;
    height:72px;
    font-size:24px;
}

.modern-profile-card h5{
    font-size:20px;
    font-weight:700;
    margin-bottom:4px;
}

.modern-profile-card small{
    font-size:14px;
}

/* =======================================================
   WIDGET
======================================================= */

.modern-widget h5{
    font-size:24px;
    font-weight:700;
}

.progress-modern{
    height:14px;
    border-radius:999px;
}

.modern-widget p{
    font-size:16px;
}

.modern-widget .badge{
    font-size:14px;
    padding:8px 12px;
}

.modern-widget h5,
.modern-profile-card h5{
    letter-spacing:-0.3px;
    line-height:1.4;
}

.gallery-cover{
    width:100%;
    height:240px;
    object-fit:cover;
    border-radius:16px;
    margin-bottom:15px;
}

.gallery-title{
    font-size:18px;
    font-weight:700;
    line-height:1.6;
    color:#111827;
}

/* =======================================================
   NEWS (REVISI MASALAH 4: Font Diperbesar & Dipertebal)
======================================================= */

.news-item{
    padding:12px 0;
    border-bottom:1px solid #edf2f7;
}

.news-item:last-child{
    border-bottom:none;
}

.news-link{
    font-size:17px;
    font-weight:600;
    line-height:1.8;
    color:#111827;
}

.news-link:hover{
    color:#2563eb;
}

/* =======================================================
   OLD CAROUSEL SUPPORT
======================================================= */

@media (max-width: 767px){

    .carousel-inner .carousel-item > div{
        display:none;
    }

    .carousel-inner .carousel-item > div:first-child{
        display:block;
    }

    .modern-hero{
        padding:30px;
    }

    .hero-title{
        font-size:32px;
    }

    .hero-circle{
        width:120px;
        height:120px;
        margin-top:20px;
    }

    .hero-circle h2{
        font-size:30px;
    }
}

/* =======================================================
   DARK MODE
======================================================= */

[data-bs-theme="dark"] .page-body{
    background:#0f172a;
}

[data-bs-theme="dark"] .modern-card,
[data-bs-theme="dark"] .modern-widget,
[data-bs-theme="dark"] .modern-profile-card,
[data-bs-theme="dark"] .stat-card,
[data-bs-theme="dark"] .course-card{
    background:#1e293b;
    color:#f8fafc;
    box-shadow:none;
    border:1px solid rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .course-body a,
[data-bs-theme="dark"] .news-link,
[data-bs-theme="dark"] h1,
[data-bs-theme="dark"] h2,
[data-bs-theme="dark"] h3,
[data-bs-theme="dark"] h4,
[data-bs-theme="dark"] h5,
[data-bs-theme="dark"] h6{
    color:#f8fafc;
}

[data-bs-theme="dark"] .text-muted,
[data-bs-theme="dark"] .stat-card p{
    color:#94a3b8 !important;
}

[data-bs-theme="dark"] .progress{
    background:#334155;
}

[data-bs-theme="dark"] .progress-bar{
    background:#3b82f6;
}

[data-bs-theme="dark"] .news-item{
    border-bottom:1px solid rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .hero-badge{
    background:#1e293b;
    color:#60a5fa;
}

[data-bs-theme="dark"] .modern-avatar{
    background:linear-gradient(
        135deg,
        #3b82f6,
        #2563eb
    );
}

[data-bs-theme="dark"] .gallery-cover{
    border:1px solid rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .course-card:hover,
[data-bs-theme="dark"] .stat-card:hover{
    transform:translateY(-4px);
    border-color:#3b82f6;
}

[data-bs-theme="dark"] .modern-hero{
    background:linear-gradient(
        135deg,
        #2563eb,
        #1e40af
    );
}

.navbar .form-control,
header .form-control,
.search-input{
    height:48px !important;
    font-size:15px !important;
    border-radius:12px !important;
}

.navbar .form-control::placeholder{
    font-size:15px;
}

.carousel-inner .carousel-item.active,
.carousel-inner .carousel-item-next,
.carousel-inner .carousel-item-prev{
    display:flex;
}

@media (min-width:768px){

    .carousel-inner .carousel-item-end.active,
    .carousel-inner .carousel-item-next{
        transform:translateX(25%);
    }

    .carousel-inner .carousel-item-start.active,
    .carousel-inner .carousel-item-prev{
        transform:translateX(-25%);
    }

    .c-home .carousel-control-prev,
    .c-home .carousel-control-next{
        width:5% !important;
    }
}

.carousel-inner .carousel-item-end,
.carousel-inner .carousel-item-start{
    transform:translateX(0);
}

</style>

@endsection

@section('script')

<script>

/* =======================================================
   MULTI CAROUSEL
======================================================= */

document.addEventListener("DOMContentLoaded", function () {

    let itemsTopVideo = document.querySelectorAll('#carousel-topvideo .carousel-item');
    let itemsVideo = document.querySelectorAll('#carousel-videos .carousel-item');
    let itemsDocument = document.querySelectorAll('#carousel-documents .carousel-item');
    let itemsPlaylist = document.querySelectorAll('#carousel-playlist .carousel-item');
    let itemsGallery = document.querySelectorAll('#carousel-event-photo .carousel-item');

    itemsTopVideo.forEach((el) => {
        const minPerSlide = 4;
        let next = el.nextElementSibling;

        for (let i = 1; i < minPerSlide; i++) {

            if (!next) {
                next = itemsTopVideo[0];
            }

            let cloneChild = next.cloneNode(true);

            el.appendChild(cloneChild.children[0]);

            next = next.nextElementSibling;
        }
    });

    itemsVideo.forEach((el) => {

        const minPerSlide = 4;

        let next = el.nextElementSibling;

        for (let i = 1; i < minPerSlide; i++) {

            if (!next) {
                next = itemsVideo[0];
            }

            let cloneChild = next.cloneNode(true);

            el.appendChild(cloneChild.children[0]);

            next = next.nextElementSibling;
        }

    });

    itemsDocument.forEach((el) => {

        const minPerSlide = 4;

        let next = el.nextElementSibling;

        for (let i = 1; i < minPerSlide; i++) {

            if (!next) {
                next = itemsDocument[0];
            }

            let cloneChild = next.cloneNode(true);

            el.appendChild(cloneChild.children[0]);

            next = next.nextElementSibling;
        }

    });

    itemsPlaylist.forEach((el) => {

        const minPerSlide = 3;

        let next = el.nextElementSibling;

        for (let i = 1; i < minPerSlide; i++) {

            if (!next) {
                next = itemsPlaylist[0];
            }

            let cloneChild = next.cloneNode(true);

            el.appendChild(cloneChild.children[0]);

            next = next.nextElementSibling;
        }

    });

    if(itemsGallery.length){

        itemsGallery.forEach((el) => {

            const minPerSlide = 1;

            let next = el.nextElementSibling;

            for (let i = 1; i < minPerSlide; i++) {

                if (!next) {
                    next = itemsGallery[0];
                }

                let cloneChild = next.cloneNode(true);

                el.appendChild(cloneChild.children[0]);

                next = next.nextElementSibling;
            }

        });

    }

});


/* =======================================================
   ASSESSMENT ACCESS
======================================================= */

$('#btn-assessment-access').on('click', function () {

    $('#unique-code').val('');

    $('#invalid-text-unique-code').html('');

    $('#invalid-text-unique-code')
        .removeClass('d-block')
        .addClass('d-none');

    $('#modal-assessment-access').modal('show');

});


function submitAssessmentAccess(){

    let unique_code = $('#unique-code').val();

    $.ajax({

        url: "{{ route('home.user.access.assessment.by.unique.code') }}",

        type: "POST",

        data: {

            _token: "{{ csrf_token() }}",

            unique_code: unique_code,

        },

        success: function(response){

            let routeAssessment =
                "{{ route('assesment.user.view', ':slug') }}";

            let routeAssessmentFixed =
                routeAssessment.replace(
                    ':slug',
                    response.assessment_slug
                );

            window.location.replace(
                routeAssessmentFixed
            );

        },

        error: function(error){

            if(error.responseJSON.unique_code){

                $('#invalid-text-unique-code')
                    .html(error.responseJSON.unique_code[0])
                    .removeClass('d-none')
                    .addClass('d-block');
            }

            if(error.responseJSON.error_message){

                $('#invalid-text-unique-code')
                    .html(error.responseJSON.error_message)
                    .removeClass('d-none')
                    .addClass('d-block');
            }

        }

    });

}

</script>

@endsection
