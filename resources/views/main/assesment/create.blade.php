@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none owl-modern-header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="owl-eyebrow">Learning Management System</div>
                    <h2 class="page-title mb-1">Manage Assesment</h2>
                    <div class="text-muted">Create a new assessment for content, playlists, or global assessments.</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('assesment.index') }}">Assesment</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Create</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="page page-center">
            <div class="container-xl">

                <div class="row justify-content-center">
                    <div class="col-lg-10 col-xl-9">

                        <div class="card owl-form-card border-0 shadow-sm">
                            <div class="card-header owl-card-header">
                                <div>
                                    <div class="owl-card-icon">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="card-title mb-1">Create Assesment</h3>
                                    <div class="text-muted small">
                                        Complete the assessment details, schedule, passing score, and remedial settings.
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <x-form class="owl-create-form" action="{{ route('assesment.store') }}" method="post">

    <div class="owl-section-title">
        <span>1</span>
        <div>
            <strong>Assessment Type</strong>
            <small>Select whether this assessment is for content, a playlist, or global use.</small>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-12">
            <x-label class="form-label required">Type</x-label>
            <x-select name="type" onchange="setRequired(this);">
                <option value="">-choose-</option>
                <option value="content" {{ (old('type') == 'content') ? 'selected' : ''  }}>Assesment for content</option>
                <option value="trainingplan" {{ (old('type') == 'trainingplan') ? 'selected' : ''  }}>Assesment for playlist</option>
                <option value="global" {{ (old('type') == 'global') ? 'selected' : ''  }}>Global Assesment</option>
            </x-select>
        </div>

        <div class="form-group col-md-12 d-none" id="content_id_wrapper">
            <x-label class="form-label">Learning Content</x-label>
            <x-select name="content_id" id="content_id">
                <option value="">-choose-</option>
                @foreach ($contents as $item)
                    <option value="{{ $item->id }}" {{ (old('title') == $item->id) ? 'selected' : ''  }}>{{ $item->title }}</option>
                @endforeach
            </x-select>
        </div>

        <div class="form-group col-md-12 d-none" id="trainingplan_id_wrapper">
            <x-label class="form-label">Training Plan</x-label>
            <x-select name="trainingplan_id" id="trainingplan_id">
                <option value="">-choose-</option>
                @foreach ($trainingplan as $item)
                    <option value="{{ $item->id }}" {{ (old('title') == $item->id) ? 'selected' : ''  }}>{{ $item->title }}</option>
                @endforeach
            </x-select>
        </div>
    </div>

    <div class="owl-section-title mt-3">
        <span>2</span>
        <div>
            <strong>Basic Information</strong>
            <small>Enter the assessment title, unique code, and passing score.</small>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-12">
            <x-label class="form-label required">Title</x-label>
            <x-input type="text" name="title" placeholder="Enter title" value="{{ old('title') }}" />
        </div>

        <div class="form-group col-md-12">
            <x-label class="form-label">Unique Code (Optional)</x-label>
            <x-input type="text" name="unique_code" placeholder="Enter unique code" value="{{ old('unique_code') }}" />
        </div>

        <div class="form-group col-md-12">
            <x-label class="form-label required">Passing Grade</x-label>
            <x-input type="number" name="passing_grade" placeholder="Enter passing grade" value="{{ old('passing_grade') }}" />
        </div>

        <div class="form-group col-md-12">
            <x-label class="form-label required">Time Limit?</x-label>
            <x-select name="timer" onchange="setRequiredDate(this);">
                <option value="">-choose-</option>
                <option value="y" {{ (old('timer') == 'y') ? 'selected' : ''  }}>Yes</option>
                <option value="n" {{ (old('timer') == 'n') ? 'selected' : ''  }}>No</option>
            </x-select>
        </div>
    </div>

    <div class="owl-section-title mt-3">
        <span>3</span>
        <div>
            <strong>Schedule</strong>
            <small>Set the assessment start and end times.</small>
        </div>
    </div>

    <div class="form-row" id="date_wrapper">
        <div class="form-group col-md-12">
            <x-label class="form-label required">From Date</x-label>
            <div class="input-icon owl-input-icon">
                <span class="input-icon-addon">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                <x-input placeholder="Select a date" id="datepicker-fromdate" name="fromdate" value="" required class="input-date" />
            </div>
        </div>

        <div class="form-group col-md-12">
            <x-label class="form-label required">To Date</x-label>
            <div class="input-icon owl-input-icon">
                <span class="input-icon-addon">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                <x-input placeholder="Select a date" id="datepicker-todate" name="todate" value="" required class="input-date" />
            </div>
        </div>
    </div>

    <div class="owl-section-title mt-3">
        <span>4</span>
        <div>
            <strong>Remedial Setting</strong>
            <small>Set the maximum number of remedial attempts.</small>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-12">
            <x-label class="form-label required">Total Remedials</x-label>
            <input type="number" name="total_remedials" id="total-remedials" class="form-control @error('total_remedials') is-invalid @enderror" placeholder="0" value="{{ old('total_remedials') }}">
            @error('total_remedials')
                <div class="invalid-feedback d-block">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <div class="owl-form-footer">
        <a href="{{ route('assesment.index') }}" class="btn btn-light">
            Cancel
        </a>
        <x-button class="px-5">
            <i class="fas fa-save mr-1"></i>
            Submit
        </x-button>
    </div>
</x-form>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('style')
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/dark.css') }}">

    <style>
        .owl-form-card {
            border-radius: 18px;
            overflow: hidden;
            background: #ffffff;
        }

        .owl-form-card .card-body {
            padding: 24px;
        }

        .owl-card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 22px 24px;
            background: linear-gradient(135deg, rgba(32, 107, 196, .08), rgba(66, 153, 225, .04));
            border-bottom: 1px solid rgba(98, 105, 118, .16);
        }

        .owl-create-form .form-row {
            margin-left: -8px;
            margin-right: -8px;
        }

        .owl-create-form .form-row > [class*="col-"] {
            padding-left: 8px;
            padding-right: 8px;
        }

        .owl-create-form .form-group {
            margin-bottom: 16px;
        }

        .owl-create-form .form-label {
            display: block;
            font-weight: 600;
            color: #334155;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .owl-create-form .form-control,
        .owl-create-form select,
        .owl-create-form .custom-select {
            width: 100%;
            border-radius: 10px;
            border-color: #d8dee9;
            min-height: 42px;
            font-size: 13px;
        }

        .owl-create-form .form-control:focus,
        .owl-create-form select:focus,
        .owl-create-form .custom-select:focus {
            border-color: #206bc4;
            box-shadow: 0 0 0 3px rgba(32, 107, 196, .12);
        }

        .owl-section-title {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 13px 15px;
            margin-bottom: 16px;
            border: 1px solid rgba(98, 105, 118, .14);
            border-radius: 14px;
            background: #f8fafc;
        }

        .owl-section-title span {
            width: 28px;
            height: 28px;
            min-width: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: rgba(32, 107, 196, .12);
            color: #206bc4;
            font-weight: 700;
            font-size: 13px;
        }

        .owl-section-title strong {
            display: block;
            font-size: 14px;
            color: #1f2937;
            margin-bottom: 2px;
        }

        .owl-section-title small {
            display: block;
            color: #6b7280;
            font-size: 12px;
        }

        .owl-form-footer {
            margin-top: 8px;
            padding-top: 20px;
            border-top: 1px solid rgba(98, 105, 118, .16);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .owl-form-footer .btn {
            border-radius: 10px;
            min-height: 40px;
        }

        /* Dark Mode Tabler */
        body.theme-dark .owl-form-card,
        body[data-theme="dark"] .owl-form-card,
        [data-bs-theme="dark"] .owl-form-card {
            background: #182433;
            color: #e5e7eb;
        }

        body.theme-dark .owl-card-header,
        body[data-theme="dark"] .owl-card-header,
        [data-bs-theme="dark"] .owl-card-header {
            background: linear-gradient(135deg, rgba(32, 107, 196, .18), rgba(15, 23, 42, .35));
            border-bottom-color: rgba(255, 255, 255, .08);
        }

        body.theme-dark .owl-section-title,
        body[data-theme="dark"] .owl-section-title,
        [data-bs-theme="dark"] .owl-section-title {
            background: #111827;
            border-color: rgba(255, 255, 255, .08);
        }

        body.theme-dark .owl-create-form .form-label,
        body[data-theme="dark"] .owl-create-form .form-label,
        [data-bs-theme="dark"] .owl-create-form .form-label {
            color: #d1d5db;
        }

        body.theme-dark .owl-section-title strong,
        body[data-theme="dark"] .owl-section-title strong,
        [data-bs-theme="dark"] .owl-section-title strong {
            color: #f3f4f6;
        }

        body.theme-dark .owl-section-title small,
        body[data-theme="dark"] .owl-section-title small,
        [data-bs-theme="dark"] .owl-section-title small,
        body.theme-dark .text-muted,
        body[data-theme="dark"] .text-muted,
        [data-bs-theme="dark"] .text-muted {
            color: #9ca3af !important;
        }

        body.theme-dark .owl-create-form .form-control,
        body.theme-dark .owl-create-form select,
        body.theme-dark .owl-create-form .custom-select,
        body[data-theme="dark"] .owl-create-form .form-control,
        body[data-theme="dark"] .owl-create-form select,
        body[data-theme="dark"] .owl-create-form .custom-select,
        [data-bs-theme="dark"] .owl-create-form .form-control,
        [data-bs-theme="dark"] .owl-create-form select,
        [data-bs-theme="dark"] .owl-create-form .custom-select {
            background-color: #111827;
            border-color: #374151;
            color: #e5e7eb;
        }

        body.theme-dark .owl-form-footer,
        body[data-theme="dark"] .owl-form-footer,
        [data-bs-theme="dark"] .owl-form-footer {
            border-top-color: rgba(255, 255, 255, .08);
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/flatpickr.js') }}"></script>
    <script>
        $(".input-date").flatpickr({
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });

        function setRequired(that) {
            if (that.value == "content") {
                document.getElementById("content_id").setAttribute('required', 'required');
                document.getElementById("trainingplan_id").removeAttribute('required');
                $("#trainingplan_id_wrapper").addClass('d-none');
                $("#content_id_wrapper").removeClass('d-none');
                $("#trainingplan_id").val('').change();
            } else if (that.value == "trainingplan") {
                document.getElementById("trainingplan_id").setAttribute('required', 'required');
                document.getElementById("content_id").removeAttribute('required');
                $("#content_id_wrapper").addClass('d-none');
                $("#trainingplan_id_wrapper").removeClass('d-none');
                $("#content_id").val('').change();
            } else {
                document.getElementById("content_id").removeAttribute('required');
                document.getElementById("trainingplan_id").removeAttribute('required');
                $("#content_id_wrapper").addClass('d-none');
                $("#trainingplan_id_wrapper").addClass('d-none');
                $("#content_id").val('').change();
                $("#trainingplan_id").val('').change();
            }
        }

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
