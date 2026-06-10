@extends('layouts.sidebar-master')
@section('content')
<div class="page-body"><div class="container">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="card-body text-center">
        <h2>{{ $certificate->title }}</h2>
        <p class="text-muted">{{ $certificate->credential_id }}</p>
        <a class="btn btn-primary" href="{{ route('certificate.download', $certificate->credential_id) }}">Download Certificate</a>
        <a class="btn btn-outline-secondary" href="{{ route('certificate.verify', $certificate->credential_id) }}">Verify</a>
        <a class="btn btn-outline-info" target="_blank" rel="noopener" href="{{ $certificate->linkedin_url }}">Add to LinkedIn</a>
    </div></div>
</div></div>
@endsection
