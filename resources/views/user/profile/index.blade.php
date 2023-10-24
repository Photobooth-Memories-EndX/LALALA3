@extends('layouts.master')
@section('content')
    <div class="page-body">
        <div class="container-xl">
            <x-alert type='success' />
            <x-alert type='warning' />
            <div class="card">
                <div class="row g-0">
                    <div class="col-3 d-md-block border-end">
                        <div class="card-body">
                            <div class="list-group list-group-transparent">
                                <a href="{{ route('user.profile') }}" class="list-group-item list-group-item-action d-flex align-items-center active">My Account</a>
                                <a href="{{ route('user.comment') }}" class="list-group-item list-group-item-action d-flex align-items-center">My Comment</a>
                                <a href="{{ route('user.changepassword') }}" class="list-group-item list-group-item-action d-flex align-items-center">Change Password</a>
                            </div>
                        </div>
                    </div>
                    <div class="col d-flex flex-column" id="tabmain">
                        <div class="card-body">
                            <h2 class="mb-4">My Account</h2>
                            <h3 class="card-title">Profile Details</h3>
                            <div class="row align-items-center">
                                <div class="col-auto"><span class="avatar avatar-xl" style="background-image: url({{ asset('storage/profile') }}/{{ auth()->user()->avatar ?? 'default.jpg' }})"></span>
                                </div>
                                <div class="col-auto">
                                    <form action="{{ route('user.updateprofile') }}" id="formavatar" name="formavatar" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="file" class="form-control" name="avatar" id="avatar" accept="image/png, image/gif, image/jpeg" />
                                    </form>
                                </div>
                                <div class="col-auto"><a href="{{ route('user.deleteavatar') }}" class="btn btn-ghost-danger" onclick="confirm('Are you sure you want to delete avatar?')">
                                        Delete avatar
                                    </a></div>
                            </div>

                        </div>
                        <div class="card-footer bg-transparent mt-auto">
                            <div class="btn-list justify-content-end">
                                {{-- <a href="#" class="btn">
                                    Cancel
                                </a> --}}
                                <a href="#" class="btn btn-primary" onclick="document.getElementById('formavatar').submit();">
                                    Submit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
