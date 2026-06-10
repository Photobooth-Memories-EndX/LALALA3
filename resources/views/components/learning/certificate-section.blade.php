@props([
    'userCertificates' => collect()
])

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">

    <div class="card-body p-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h3 class="mb-1">
                    Sertifikat Saya
                </h3>

                <div class="text-muted small">
                    Daftar sertifikat yang telah berhasil Anda generate.
                </div>
            </div>

            <span class="badge bg-blue-lt text-blue">
                {{ $userCertificates->count() }} Sertifikat
            </span>
        </div>
    </div>

    <div class="card-body p-4">

        @if($userCertificates->count())

            <div class="row">

                @foreach($userCertificates as $certificate)

                    <div class="col-md-6 col-lg-4 mb-3">

                        <a href="{{ route('certificate.show', $certificate->credential_id) }}"
                           class="text-reset text-decoration-none">

                            <div class="card h-100 border-0 shadow-sm certificate-card">

                                <div class="card-body p-4">

                                    <div class="d-flex align-items-center justify-content-between mb-3">

                                        <span class="avatar avatar-md bg-green-lt text-green">
                                            <i class="ti ti-certificate"></i>
                                        </span>

                                        <span class="badge bg-green-lt text-green">
                                            Generated
                                        </span>

                                    </div>

                                    <h4 class="mb-2 line-clamp-2">
                                        {{ $certificate->title }}
                                    </h4>

                                    <div class="text-muted small mb-3">
                                        {{ $certificate->certificate_number }}
                                    </div>

                                    <div class="mb-3">
                                        <div class="small text-muted">
                                            Credential ID
                                        </div>

                                        <div class="font-weight-bold text-primary">
                                            {{ $certificate->credential_id }}
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between">

                                        <div>
                                            <div class="small text-muted">
                                                Issued Date
                                            </div>

                                            <div class="small font-weight-bold">
                                                {{ optional($certificate->issued_at)->format('d M Y') }}
                                            </div>
                                        </div>

                                        <span class="btn btn-sm btn-primary">
                                            Detail
                                            <i class="ti ti-arrow-right ml-1"></i>
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </a>

                    </div>

                @endforeach

            </div>

        @else

            <div class="empty text-center py-5">

                <div class="empty-icon mb-3">
                    <span class="avatar avatar-xl bg-blue-lt text-blue">
                        <i class="ti ti-certificate"></i>
                    </span>
                </div>

                <h3>
                    Belum Ada Sertifikat
                </h3>

                <p class="text-muted mb-0">
                    Sertifikat yang berhasil digenerate akan muncul di sini.
                </p>

            </div>

        @endif

    </div>

</div>

<style>
    .certificate-card {
        border-radius: 16px;
        transition: all .2s ease;
    }

    .certificate-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 32px rgba(15, 23, 42, .14) !important;
    }

    .certificate-card .avatar {
        width: 46px;
        height: 46px;
        font-size: 24px;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>