@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container">

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-5">

                <div class="mb-3">
                    <span class="badge bg-green-lt text-green">
                        Certificate Generated
                    </span>
                </div>

                <h2 class="mb-2">
                    {{ $certificate->title }}
                </h2>

                <p class="text-muted mb-4">
                    Credential ID :
                    <strong>{{ $certificate->credential_id }}</strong>
                </p>
                    <div class="text-center mt-4">

                        <a href="{{ route('certificate.download', $certificate->credential_id) }}"
                        class="btn btn-primary mx-1 mb-2">
                            <i class="ti ti-file-type-pdf mr-1"></i>
                            Download PDF Certificate
                        </a>

                        <a href="{{ route('certificate.download', $certificate->credential_id) }}"
                        class="btn btn-success mx-1 mb-2">
                            <i class="ti ti-file-type-html mr-1"></i>
                            Download HTML Certificate
                        </a>

                        <a href="{{ route('certificate.verify', $certificate->credential_id) }}"
                        target="_blank"
                        class="btn btn-warning mx-1 mb-2">
                            <i class="ti ti-shield-check mr-1"></i>
                            Verify
                        </a>

                        @if(!empty($certificate->linkedin_url))
                            <a href="{{ $certificate->linkedin_url }}"
                            target="_blank"
                            rel="noopener"
                            class="btn btn-info mx-1 mb-2">
                                <i class="ti ti-brand-linkedin mr-1"></i>
                                Add to LinkedIn
                            </a>
                        @endif

                    </div>

                <hr>

                <div class="row text-left mt-4">
                    <div class="col-md-6">
                        <strong>Certificate Number</strong>
                        <div class="text-muted">
                            {{ $certificate->certificate_number }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <strong>Issued Date</strong>
                        <div class="text-muted">
                            {{ optional($certificate->issued_at)->format('d M Y') }}
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection