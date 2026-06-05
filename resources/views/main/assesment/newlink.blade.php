@extends('layouts.sidebar-master')



@section('content')
    <div class="page-header d-print-none mb-4">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle text-muted">
                        Learning Management System
                    </div>

                    <h2 class="page-title fw-bold">
                        <i class="fas fa-link me-2 text-primary"></i>
                        Manage Assessment
                    </h2>
                </div>

                <div class="col-auto">
                    <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item">
                            <a href="#">Manage</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('assesment.index') }}">Assessment</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            New Link
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            <x-form
                class="card border-0 shadow-sm"
                action="{{ route('assesment.updatelink', $assesment->slug) }}"
                method="put">

                <div class="card-header bg-primary-lt">
                    <div class="row align-items-center w-100">
                        <div class="col">
                            <h3 class="card-title fw-bold mb-1">
                                <i class="fas fa-edit me-2 text-primary"></i>
                                New Link Assessment
                            </h3>

                            <div class="text-muted small">
                                Update assessment link, batch number, and assessment access time.
                            </div>
                        </div>

                        <div class="col-auto">
                            <span class="badge bg-primary-lt text-primary">
                                {{ strtoupper($assesment->slug ?? 'ASSESSMENT') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body">

                    <div class="card bg-dark-lt border-0 mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-link me-2"></i>
                                Link Configuration
                            </h3>
                        </div>

                        <div class="card-body">

                            <div class="mb-4">
                                <label class="form-label required">
                                    Link
                                </label>

                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="fas fa-link"></i>
                                    </span>

                                    <x-input
                                        type="text"
                                        name="slug"
                                        placeholder="Enter new link"
                                        value="{{ $assesment->slug }}" />
                                </div>

                                <small class="form-hint">
                                    No space and symbol allowed
                                </small>
                            </div>

                            <div class="row">

                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label required">
                                            Batch No
                                        </label>

                                        <x-input
                                            type="number"
                                            name="batch"
                                            placeholder="Enter batch no"
                                            required
                                            value="{{ $assesment->batch }}" />
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label required">
                                            Time Limit?
                                        </label>

                                        <x-select
                                            name="timer"
                                            onchange="setRequiredDate(this);">

                                            <option value="">-choose-</option>

                                            <option value="y" {{ ($assesment->timer == 'y') ? 'selected' : '' }}>
                                                Yes
                                            </option>

                                            <option value="n" {{ ($assesment->timer == 'n') ? 'selected' : '' }}>
                                                No
                                            </option>

                                        </x-select>
                                    </div>
                                </div>

                            </div>

                            <div id="date_wrapper">
                                <div class="row">

                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label required">
                                                From Date
                                            </label>

                                            <div class="input-icon">
                                                <span class="input-icon-addon">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </span>

                                                <x-input
                                                    placeholder="Select a date"
                                                    id="datepicker-fromdate"
                                                    name="fromdate"
                                                    value="{{ $assesment->fromdate }}"
                                                    required
                                                    class="input-date" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label class="form-label required">
                                                To Date
                                            </label>

                                            <div class="input-icon">
                                                <span class="input-icon-addon">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </span>

                                                <x-input
                                                    placeholder="Select a date"
                                                    id="datepicker-todate"
                                                    name="todate"
                                                    value="{{ $assesment->todate }}"
                                                    required
                                                    class="input-date" />
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="card-footer bg-transparent d-flex justify-content-end">
                    <x-button class="btn btn-primary px-5">
                        <i class="fas fa-save me-2"></i>
                        Submit
                    </x-button>
                </div>

            </x-form>

        </div>
    </div>
@endsection

@section('style')
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/dark.css') }}">
@endsection

@section('script')
    <script src="{{ asset('js/flatpickr.js') }}"></script>

    <script>
        $(".input-date").flatpickr({
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });

        function setRequiredDate(that) {
            if (that.value == "y") {
                document.getElementById("datepicker-fromdate").setAttribute('required', 'required');
                document.getElementById("datepicker-todate").setAttribute('required', 'required');
                $("#date_wrapper").removeClass('d-none');
            } else {
                document.getElementById("datepicker-fromdate").removeAttribute('required');
                document.getElementById("datepicker-todate").removeAttribute('required');
                $("#date_wrapper").addClass('d-none');
                $("#datepicker-fromdate").val('').change();
                $("#datepicker-todate").val('').change();
            }
        }
    </script>
@endsection