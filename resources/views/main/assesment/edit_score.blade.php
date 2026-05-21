@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Assessment Edit Score</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('assesment.index') }}">Assessment</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Edit Score</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container">
            <div class="card">
                <div class="card-body">

                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div>
                                    <h4 class="alert-title">{{ session('success') }}</h4>
                                </div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col">
                            <div class="card">
                                <div class="card-header py-2">
                                    <h5 class="card-title">Form Edit Score Assessment</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('assesment.edit.score.update') }}" method="post" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="assessment">Assessment</label>
                                            <select class="form-select select2" id="assessment" name="assessment">
                                                <option value="">-- Choose Assessment --</option>
                                                @foreach ($assessments as $assessment)
                                                    <option value="{{ $assessment->id }}" @selected(old('assessment') == $assessment->id)>{{ $assessment->title }}</option>
                                                @endforeach
                                            </select>
                                            @error('assessment')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="employee">Employee</label>
                                            <select class="form-select select2" id="employee" name="employee">
                                                <option value="">-- Choose Employee --</option>
                                                @foreach ($employees as $employee)
                                                    <option value="{{ $employee->id_emp }}" @selected(old('employee') == $employee->id_emp)>{{ $employee->id_emp . ' | ' . $employee->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('employee')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-2">
                                                <label for="is-paper-based">Paper Based</label>
                                                <select name="is_paper_based" id="is-paper-based" class="form-select">
                                                    <option value="0" @selected(old('is_paper_based') == 0)>No</option>
                                                    <option value="1" @selected(old('is_paper_based') == 1)>Yes</option>
                                                </select>
                                            </div>
                                            <div class="col">
                                                <label for="evidence-paper-based">Evidances Paper Based</label>
                                                <input type="file" class="form-control" id="evidence-paper-based" name="evidence_paper_based" class="form-control" accept=".pdf" />
                                                @error('evidence_paper_based')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-2">
                                                <label for="score">Score</label>
                                                <input type="number" class="form-control" id="score" name="score" placeholder="0" value="{{ old('score') }}" autocomplete="off" />
                                                @error('score')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Update</button>
                                        </div>
                                    </form>
                                </div>
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
        $('.select2').select2({
            theme: 'bootstrap-5',
        });
    </script>
@endpush