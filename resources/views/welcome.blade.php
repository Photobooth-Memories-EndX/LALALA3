@extends('layouts.master')

@section('content')

@php
use App\Models\Content;

$content = Content::select(
'id',
'title',
'type',
'slug',
'duration',
'link'
)
->whereIn(['YouTube','Video'])
->where('active','Active')
->latest()
->take(8)
->get();
@endphp

<div class="page-body modern-home">

<div class="container-xl">

<!-- HERO -->

<div class="modern-hero">

<div class="row align-items-center">

<div class="col-lg-8">

<span class="hero-badge">
Learning Management System
</span>

<h1 class="hero-title">
Upgrade Your Skills
</h1>

<p class="hero-subtitle">
Learn faster, improve performance,
and complete your learning journey.
</p>

<a href="#top-learning"
class="btn btn-light btn-lg">
Start Learning </a>

</div>

<div class="col-lg-4 text-center">

<div class="hero-circle">

<h2>55%</h2>

<span>Completed</span>

</div>

</div>

</div>

</div>

<!-- STATS -->

<div class="row mt-4">

<div class="col-md-3">

<div class="stat-card">

<i class="ti ti-check"></i>

<h3>45</h3>

<p>Completed</p>

</div>

</div>

<div class="col-md-3">

<div class="stat-card">

<i class="ti ti-player-play"></i>

<h3>12</h3>

<p>On Going</p>

</div>

</div>

<div class="col-md-3">

<div class="stat-card">

<i class="ti ti-book"></i>

<h3>57</h3>

<p>Modules</p>

</div>

</div>

<div class="col-md-3">

<div class="stat-card">

<i class="ti ti-certificate"></i>

<h3>8</h3>

<p>Certificates</p>

</div>

</div>

</div>

<div class="row mt-4">

<!-- LEFT -->

<div class="col-lg-8">

<div class="card modern-card">

<div class="card-header">

<h2 class="mb-0">
Top Learning
</h2>

</div>

<div class="card-body">

<div class="row">

@foreach($content as $item)

<div class="col-md-6 mb-4">

<div class="course-card">

@if($item->type == "YouTube")

<img
src="https://img.youtube.com/vi/{{ $item->link }}/mqdefault.jpg"
class="img-fluid rounded-top">

@endif

<div class="course-body">

<h4>

<a href="{{ route('content.user.view',$item->slug) }}">
{{ Str::limit($item->title,60) }}
</a>

</h4>

<span class="badge bg-primary">
{{ \App\Support\DurationFormatter::format($item->duration) }}
</span>

</div>

</div>

</div>

@endforeach

</div>

</div>

</div>

<div class="card modern-card mt-4">

<div class="card-header">

<h2>
Training Module
</h2>

</div>

<div class="card-body">

<img
src="{{ asset('modules.JPG') }}"
class="img-fluid rounded">

</div>

</div>

</div>

<!-- SIDEBAR -->

<div class="col-lg-4">

<div class="card modern-card">

<div class="card-body">

<div class="d-flex align-items-center">

<div class="avatar avatar-lg bg-primary text-white">

EK

</div>

<div class="ms-3">

<h4 class="mb-0">
Edi Kusumaedi
</h4>

<small>
Information System Management Team
</small>

</div>

</div>

</div>

</div>

<div class="card modern-card mt-4">

<div class="card-header">

<h4>
Learning Progress
</h4>

</div>

<div class="card-body">

<div class="progress">

<div
class="progress-bar"
style="width:55%">
</div>

</div>

<p class="mt-3">
55% Completed
</p>

</div>

</div>

<div class="card modern-card mt-4">

<div class="card-header">

<h4>
Notice
</h4>

</div>

<div class="card-body">

<ul class="mb-0">

<li>
Training Schedule
</li>

<li>
Knowledge Update
</li>

<li>
Assessment Reminder
</li>

</ul>

</div>

</div>

</div>

</div>

</div>

</div>

@endsection

@section('style')

<style>

.modern-home{
background:#f5f7fb;
}

.modern-hero{
background:linear-gradient(
135deg,
#2563eb,
#1d4ed8
);
padding:50px;
border-radius:24px;
color:white;
}

.hero-badge{
display:inline-block;
background:white;
color:#2563eb;
padding:8px 16px;
border-radius:999px;
margin-bottom:20px;
font-weight:600;
}

.hero-title{
font-size:48px;
font-weight:700;
color:white;
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
display:flex;
flex-direction:column;
justify-content:center;
align-items:center;
margin:auto;
}

.hero-circle h2{
font-size:48px;
color:white;
margin:0;
}

.stat-card{
background:white;
padding:25px;
text-align:center;
border-radius:20px;
box-shadow:0 10px 25px rgba(0,0,0,.06);
}

.stat-card i{
font-size:32px;
color:#2563eb;
}

.modern-card{
border:none;
border-radius:20px;
box-shadow:0 10px 25px rgba(0,0,0,.06);
}

.course-card{
border-radius:16px;
overflow:hidden;
background:white;
box-shadow:0 8px 20px rgba(0,0,0,.06);
transition:.3s;
}

.course-card:hover{
transform:translateY(-5px);
}

.course-body{
padding:15px;
}

</style>

@endsection
