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
                    <div class="card-body p-3 p-md-4">
                        <div class="border-bottom pb-3 mb-4">
                            <h2 class="h2 mb-1 text-body">My Account</h2>
                            <p class="text-muted mb-0 small">Manage your profile details, avatar, and personal information.</p>
                        </div>

                        {{-- Avatar Section --}}
                        <div class="my-profile-avatar-box mb-4">
                            <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-3">
                                <div class="my-profile-avatar-ring">
                                    @php
                                        $avatarPath = !empty(auth()->user()?->avatar) ? asset('storage/profile/' . auth()->user()->avatar) : asset('storage/profile/default.jpg');
                                    @endphp
                                    <div class="my-profile-avatar-img" id="avatarPreview" style="background-image: url('{{ $avatarPath }}');">
                                        @if(empty(auth()->user()?->avatar))
                                            <i class="ti ti-user fs-1 text-muted"></i>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-center text-sm-start flex-grow-1">
                                    <h4 class="mb-1 text-body">Profile Photo</h4>
                                    <p class="text-muted small mb-3">Upload a new photo to personalize your account. Max file size: 2MB.</p>

                                    <form action="{{ route('user.updateprofile') }}" id="formavatar" name="formavatar" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="file" name="avatar" id="avatar" accept="image/png, image/gif, image/jpeg, image/webp" class="d-none" />
                                    </form>

                                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-sm-start align-items-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="document.getElementById('avatar').click();">
                                            <i class="ti ti-upload me-1"></i> Change Photo
                                        </button>

                                        @if(!empty(auth()->user()?->avatar))
                                            <a href="{{ route('user.deleteavatar') }}" class="btn btn-ghost-danger btn-sm text-danger px-3 text-decoration-none" onclick="return confirm('Are you sure you want to delete avatar?')">
                                                <i class="ti ti-trash me-1"></i> Delete avatar
                                            </a>
                                        @endif
                                    </div>

                                    <div id="fileSelectedBadge" class="mt-2 d-none small text-success">
                                        <i class="ti ti-check me-1"></i> Selected: <strong id="fileNameDisplay">file.jpg</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Profile Information Details --}}
                        <div class="profile-info-section">
                            <h3 class="card-title mb-3">Profile Details</h3>
                            @php
                                $u = auth()->user();
                            @endphp
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted small fw-bold mb-1">FULL NAME</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-user text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control" value="{{ $u->name ?? '-' }}" readonly disabled>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted small fw-bold mb-1">EMAIL ADDRESS</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-mail text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control" value="{{ $u->email ?? '-' }}" readonly disabled>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted small fw-bold mb-1">EMPLOYEE ID / SAP CODE</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-id text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control" value="{{ $u->id_emp ?? $u->sap_code ?? $u->id_user ?? '-' }}" readonly disabled>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted small fw-bold mb-1">ROLE</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-shield text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control" value="{{ $u->role_name ?? 'User' }}" readonly disabled>
                                    </div>
                                </div>

                                @if(!empty($u->department) || !empty($u->division) || !empty($u->team))
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted small fw-bold mb-1">ORGANIZATION / DEPARTMENT</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-building text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control" value="{{ implode(' - ', array_filter([$u->department, $u->division, $u->team])) }}" readonly disabled>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-transparent border-top mt-auto p-3 p-md-4">
                        <div class="d-flex justify-content-end align-items-center my-profile-actions">
                            <button type="button" class="btn btn-outline-secondary px-4 me-2" onclick="handleCancel(event)">
                                Cancel
                            </button>
                            <button type="button" class="btn btn-primary px-4" onclick="document.getElementById('formavatar').submit();">
                                <i class="ti ti-check me-1"></i> Submit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('avatarPreview');
        const fileBadge = document.getElementById('fileSelectedBadge');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const initialBg = avatarPreview ? avatarPreview.style.backgroundImage : '';

        if (avatarInput) {
            avatarInput.addEventListener('change', function(e) {
                const file = e.target.files && e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        if (avatarPreview) {
                            avatarPreview.style.backgroundImage = 'url("' + evt.target.result + '")';
                        }
                        if (fileBadge) {
                            fileBadge.classList.remove('d-none');
                        }
                        if (fileNameDisplay) {
                            fileNameDisplay.textContent = file.name;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        window.resetAvatarPreview = function() {
            if (avatarInput) avatarInput.value = '';
            if (avatarPreview) avatarPreview.style.backgroundImage = initialBg;
            if (fileBadge) fileBadge.classList.add('d-none');
        };
    });

    function handleCancel(e) {
        if (e) e.preventDefault();
        const avatarInput = document.getElementById('avatar');
        if (avatarInput && avatarInput.files && avatarInput.files.length > 0) {
            if (typeof resetAvatarPreview === 'function') {
                resetAvatarPreview();
            }
        }
        if (document.referrer && document.referrer !== window.location.href && document.referrer.includes(window.location.host)) {
            window.history.back();
        } else {
            window.location.href = "{{ route('user.profile') }}";
        }
    }
</script>
@endpush
