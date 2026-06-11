@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">
    <div class="container-xl">

```
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card border-0 shadow-sm">

                <div class="card-body text-center p-5">

                    <div class="mb-3">

                        <span class="badge bg-green-lt text-green">
                            <i class="ti ti-circle-check mr-1"></i>
                            Certificate Generated
                        </span>

                    </div>

                    <div class="mb-4">

                        <span class="avatar avatar-xl bg-green-lt text-green">
                            <i class="ti ti-certificate"></i>
                        </span>

                    </div>

                    <h2 class="mb-2">
                        {{ $certificate->title }}
                    </h2>

                    <div class="text-muted mb-4">
                        Successfully completed by
                    </div>

                    <h3 class="mb-4">
                        {{ $certificate->participant_name }}
                    </h3>

                    <div class="row text-left mb-4">

                        <div class="col-md-6 mb-3">

                            <div class="small text-muted">
                                Certificate Number
                            </div>

                            <div class="font-weight-bold">
                                {{ $certificate->certificate_number }}
                            </div>

                        </div>

                        <div class="col-md-6 mb-3">

                            <div class="small text-muted">
                                Credential ID
                            </div>

                            <div class="font-weight-bold">
                                {{ $certificate->credential_id }}
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="small text-muted">
                                Issued Date
                            </div>

                            <div class="font-weight-bold">
                                {{ optional($certificate->issued_at)->format('d M Y') }}
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="small text-muted">
                                Issuer
                            </div>

                            <div class="font-weight-bold">
                                {{ optional($certificate->certificateSetting)->issuer_name }}
                            </div>

                        </div>

                    </div>

                    <hr>

                    <div class="text-center mt-4">

                        {{-- PDF --}}
                        <a href="{{ route('certificate.download.pdf', $certificate->credential_id) }}"
                           class="btn btn-primary mx-1 mb-2">

                            <i class="ti ti-file-type-pdf mr-1"></i>
                            Download PDF

                        </a>

                        {{-- HTML --}}
                        <a href="{{ route('certificate.download', $certificate->credential_id) }}"
                           class="btn btn-success mx-1 mb-2">

                            <i class="ti ti-file-type-html mr-1"></i>
                            Download HTML

                        </a>

                        {{-- Verify --}}
                        <a href="{{ route('certificate.verify', $certificate->credential_id) }}"
                           target="_blank"
                           class="btn btn-warning mx-1 mb-2">

                            <i class="ti ti-shield-check mr-1"></i>
                            Verify

                        </a>

                        {{-- LinkedIn --}}
                        @if(!empty($certificate->linkedin_url))

                            <a href="{{ $certificate->linkedin_url }}"
                               target="_blank"
                               rel="noopener"
                               class="btn btn-info mx-1 mb-2">

                                <i class="ti ti-brand-linkedin mr-1"></i>
                                Add To LinkedIn

                            </a>

                        @endif

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
```

</div>
@endsection
