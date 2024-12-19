@extends('layouts.master')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Manage User Outsourcing
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('user.index') }}">User</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Create New User Non-PTKP</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="page page-center">
            <div class="container container-tight py-4">
                <x-form class="card card-md" action="{{ route('manage.store.user.outsourcing') }}" method="post">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Create new user non-PTKP</h2>
                        <div class="mb-3">
                            <label class="form-label">ID Employee</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">ex</span>
                                <input type="text" class="form-control @error('id_employee') is-invalid @enderror" placeholder="ID Employee" name="id_employee" id="id-employee" />
                            </div>
                            @error('id_employee')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <x-label class="form-label">Name</x-label>
                            <x-input type="text" class="form-control" placeholder="Enter name" name="name" id="name" />
                        </div>
                        <div class="mb-3">
                            <x-label class="form-label">Email address</x-label>
                            <x-input type="email" class="form-control" placeholder="Enter email" name="email" id="email" />
                        </div>
                        <div class="mb-3">
                            <x-label class="form-label">Company Name</x-label>
                            <x-input type="text" class="form-control" placeholder="Company Name" name="company_name" id="company-name" />
                        </div>
                        <div class="form-footer">
                            <x-button type="submit" class="btn btn-primary w-100">Create new account</x-button>
                        </div>
                    </div>
                </x-form>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        #overlay {
            position: absolute;
            top: 0rem;
            right: 0rem;
            left: 0rem;
            bottom: 0rem;
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-align-items: center;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-pack: center;
            -ms-flex-pack: center;
            -webkit-justify-content: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 0.25rem;
            z-index: 1000;
        }
    </style>
@endsection