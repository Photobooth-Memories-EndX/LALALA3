@extends('layouts.sidebar-master')

@section('content')
@include('user.profile.partials.styles')

<div class="page-body">
    <div class="container-xl">
        <x-alert type='success' />
        <x-alert type='warning' />

        <div class="card my-profile-card">
            <div class="row g-0">
                @include('user.profile.partials.nav')

                <div class="col-12 col-md-9 d-flex flex-column" id="tabmain">
                    <form action="{{ route('user.updatepassword') }}" method="POST">
                        @csrf
                        <div class="card-body p-3 p-md-4">
                            <div class="border-bottom pb-3 mb-4">
                                <h2 class="h2 mb-1 text-body">Change Password</h2>
                                <p class="text-muted mb-0 small">Ensure your account is using a long, random password to stay secure.</p>
                            </div>

                            <div class="row g-3 max-w-lg">
                                <div class="col-12">
                                    <label for="oldPasswordInput" class="form-label text-muted small fw-bold mb-1">OLD PASSWORD</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-lock-open text-muted"></i>
                                        </span>
                                        <input name="old_password" type="password" class="form-control @error('old_password') is-invalid @enderror" id="oldPasswordInput" placeholder="Enter current password">
                                    </div>
                                    @error('old_password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="newPasswordInput" class="form-label text-muted small fw-bold mb-1">NEW PASSWORD</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-lock text-muted"></i>
                                        </span>
                                        <input name="new_password" type="password" class="form-control @error('new_password') is-invalid @enderror" id="newPasswordInput" placeholder="Enter new password">
                                    </div>
                                    @error('new_password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="confirmNewPasswordInput" class="form-label text-muted small fw-bold mb-1">CONFIRM NEW PASSWORD</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-circle-check text-muted"></i>
                                        </span>
                                        <input name="new_password_confirmation" type="password" class="form-control" id="confirmNewPasswordInput" placeholder="Confirm new password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-transparent border-top mt-auto p-3 p-md-4">
                            <div class="d-flex justify-content-end align-items-center my-profile-actions">
                                <button type="button" class="btn btn-outline-secondary px-4 me-2" onclick="handleCancel(event)">
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="ti ti-check me-1"></i> Submit
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleCancel(e) {
        if (e) e.preventDefault();
        if (document.referrer && document.referrer !== window.location.href && document.referrer.includes(window.location.host)) {
            window.history.back();
        } else {
            window.location.href = "{{ route('user.profile') }}";
        }
    }
</script>
@endpush
