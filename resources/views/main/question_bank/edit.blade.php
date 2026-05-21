@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Manage Question Bank
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('question.bank.index') }}">Question Bank</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Create</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Question Bank</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('question.bank.update', $questionBank->id) }}" method="post">
                        @csrf
                        @method('PUT')
                        <div class="row align-items-center mb-3">
                            <div class="col-2 text-end">
                                <label for="title" class="form-label mb-0 required">Title</label>
                            </div>
                            <div class="col-10">
                                <input type="text" name="title" id="title" class="form-control" placeholder="Title..." value="{{ old('title', $questionBank->title) }}" autocomplete="off">
                                @error('title')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-2 text-end">
                                <label for="created-date" class="form-label mb-0 required">Created Date</label>
                            </div>
                            <div class="col-10">
                                <input type="date" name="created_date" id="created-date" value="{{ old('created_date', $questionBank->created_date) }}" class="form-control">
                                @error('created_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning px-5 float-end"><i class="fas fa-edit me-2"></i>Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection