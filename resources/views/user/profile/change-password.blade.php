@extends('layouts.master')
@section('content')
    <div class="page-body">
        <div class="container-xl">
            <x-alert type='success' />
            <x-alert type='warning' />
            <div class="card">
                <div class="row g-0">
                    <div class="col-3 d-none d-md-block border-end">
                        <div class="card-body">
                            <div class="list-group list-group-transparent">
                                <a href="{{ route('user.profile') }}" class="list-group-item list-group-item-action d-flex align-items-center">My Account</a>
                                <a href="{{ route('user.comment') }}" class="list-group-item list-group-item-action d-flex align-items-center">My Comment</a>
                                <a href="{{ route('user.changepassword') }}" class="list-group-item list-group-item-action d-flex align-items-center active">Change Password</a>
                            </div>
                        </div>
                    </div>
                    <div class="col d-flex flex-column" id="tabmain">
                        <form action="{{ route('user.updatepassword') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <h2 class="mb-4">Change Password</h2>
                            <div class="mb-3">
                                <label for="oldPasswordInput" class="form-label">Old Password</label>
                                <input name="old_password" type="password" class="form-control @error('old_password') is-invalid @enderror" id="oldPasswordInput"
                                    placeholder="Old Password">
                                @error('old_password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="newPasswordInput" class="form-label">New Password</label>
                                <input name="new_password" type="password" class="form-control @error('new_password') is-invalid @enderror" id="newPasswordInput"
                                    placeholder="New Password">
                                @error('new_password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="confirmNewPasswordInput" class="form-label">Confirm New Password</label>
                                <input name="new_password_confirmation" type="password" class="form-control" id="confirmNewPasswordInput"
                                    placeholder="Confirm New Password">
                            </div>

                        </div>
                        <div class="card-footer bg-transparent mt-auto">
                            <div class="btn-list justify-content-end">
                                <button class="btn btn-success">Submit</button>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
