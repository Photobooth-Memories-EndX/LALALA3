@extends('layouts.master')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Manage User Non-PTKP
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('user.index') }}">User</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Edit User Non-PTKP</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="page page-center">
            <div class="container container-tight py-4">
                <x-form class="card card-md" action="{{ route('manage.update.user.outsourcing', $user->id) }}" method="post">
                    @method('PUT')
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Create new user non-PTKP</h2>
                        <div class="mb-3">
                            <label class="form-label">ID Employee</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">ex</span>
                                <input type="text" class="form-control @error('id_employee') is-invalid @enderror" placeholder="ID Employee" name="id_employee" id="id-employee" value="{{ substr(old('id_employee', $user->id_emp), 2, 12) }}" />
                            </div>
                            @error('id_employee')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <x-label class="form-label">Name</x-label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" placeholder="Enter name" name="name" id="name" value="{{ old('name', $user->name) }}" >
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <x-label class="form-label">Email address</x-label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" placeholder="Enter email" name="email" id="email" value="{{ old('email', $user->email) }}" >
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <x-label class="form-label">Company Name</x-label>
                            <input type="text" class="form-control @error('company_name') is-invalid @enderror" placeholder="Company Name" name="company_name" id="company-name" value="{{ old('company_name', $user->company_name) }}" >
                            @error('company_name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-footer">
                            <x-button type="submit" class="btn btn-warning w-100">Update account</x-button>
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