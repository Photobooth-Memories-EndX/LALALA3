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
                    <i class="fas fa-file-alt me-2 text-primary"></i>
                    Manage Assessment
                </h2>
            </div>

            <div class="col-auto">
                <ol class="breadcrumb breadcrumb-arrows mb-0">
                    <li class="breadcrumb-item">
                        <a href="#">Manage</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('assesment.index') }}">
                            Assessment
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        Edit
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>

    <div class="page-body">
        <div class="page page-center">
            <div class="container">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary-lt">
                       <h3 class="card-title fw-bold">
                            <i class="fas fa-edit me-2"></i>
                            Edit Assessment
                        </h3>
                    </div>
                    <div class="card-body">

                        @if (session()->has('success-syncronized'))
                            <div class="alert alert-success py-2 alert-dismissible" role="alert">
                                {{ session('success-syncronized') }}
                                <a class="btn-close pt-1" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                        @endif

                        @if (session()->has('warning'))
                            <div class="alert alert-warning py-2 alert-dismissible" role="alert">
                                {{ session('warning') }}
                                <a class="btn-close pt-1" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                        @endif

                        @if (session()->has('danger'))
                            <div class="alert alert-danger py-2 alert-dismissible" role="alert">
                                {{ session('danger') }}
                                <a class="btn-close pt-1" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                        @endif

                        <x-form class="" action="{{ route('assesment.update', $assesment->slug) }}" id="form-update-assessment" method="put">

                            <x-alert type='success' />

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-cogs me-2"></i>
                                        Assessment Configuration
                                    </h3>
                                </div>

                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label required">Assessment Type</label>

                                                <x-select name="type" onchange="setRequired(this);">
                                                    <option value="">-choose-</option>
                                                    <option value="content" {{ ($assesment->type == 'content') ? 'selected' : '' }}>Assesment for content</option>
                                                    <option value="trainingplan" {{ ($assesment->type == 'trainingplan') ? 'selected' : '' }}>Assesment for playlist</option>
                                                    <option value="global" {{ ($assesment->type == 'global') ? 'selected' : '' }}>Global Assesment</option>
                                                </x-select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label required">Title</label>
                                                <x-input type="text" name="title" placeholder="Enter title" value="{{ $assesment->title }}" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4 {{ ($assesment->type == 'trainingplan') || ($assesment->type == 'global') ? 'd-none' : '' }}" id="content_id_wrapper">
                                        <label class="form-label">Learning Content</label>

                                        <x-select name="content_id" id="content_id">
                                            <option value="">-choose-</option>
                                            @foreach ($contents as $item)
                                                <option value="{{ $item->id }}" {{ ($assesment->content_id == $item->id) ? 'selected' : '' }}>{{ $item->title }}</option>
                                            @endforeach
                                        </x-select>
                                    </div>

                                    <div class="mb-4 {{ ($assesment->type == 'content') || ($assesment->type == 'global') ? 'd-none' : '' }}" id="trainingplan_id_wrapper">
                                        <label class="form-label">Training Plan</label>

                                        <x-select name="trainingplan_id" id="trainingplan_id">
                                            <option value="">-choose-</option>
                                            @foreach ($trainingplan as $item)
                                                <option value="{{ $item->id }}" {{ ($assesment->trainingplan_id == $item->id) ? 'selected' : '' }}>{{ $item->title }}</option>
                                            @endforeach
                                        </x-select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label">Unique Code (Optional)</label>
                                                <x-input type="text" name="unique_code" placeholder="Enter unique code" value="{{ $assesment->unique_code }}" />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-4">
                                                <label class="form-label required">Passing Grade</label>
                                                <x-input type="number" name="passing_grade" placeholder="Enter passing grade" value="{{ $assesment->passing_grade }}" />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-4">
                                                <label class="form-label required">Time Limit?</label>

                                                <x-select name="timer" onchange="setRequiredDate(this);">
                                                    <option value="">-choose-</option>
                                                    <option value="y" {{ ($assesment->timer == 'y') ? 'selected' : '' }}>Yes</option>
                                                    <option value="n" {{ ($assesment->timer == 'n') ? 'selected' : '' }}>No</option>
                                                </x-select>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="date_wrapper">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label class="form-label required">From Date</label>

                                                    <div class="input-icon">
                                                        <span class="input-icon-addon">
                                                            <i class="fas fa-calendar-alt"></i>
                                                        </span>

                                                        <x-input placeholder="Select a date" id="datepicker-fromdate" name="fromdate" value="{{ $assesment->fromdate }}" required class="input-date" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label class="form-label required">To Date</label>

                                                    <div class="input-icon">
                                                        <span class="input-icon-addon">
                                                            <i class="fas fa-calendar-alt"></i>
                                                        </span>

                                                        <x-input placeholder="Select a date" id="datepicker-todate" name="todate" value="{{ $assesment->todate }}" required class="input-date" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-0">
                                                <label class="form-label required">Total Remedials</label>

                                                <input type="number" name="total_remedials" id="total-remedials" class="form-control @error('total_remedials') is-invalid @enderror" placeholder="0" value="{{ old('total_remedials', $assesment->total_remedials) }}">

                                                @error('total_remedials')
                                                    <div class="invalid-feedback d-block">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-2">
                            
                          <form action="{{ route('assessment.store.question.bank', $assesment->slug) }}"
                                id="form-store-question-bank"
                                method="post">
                                @csrf
                                <input type="hidden"
                                    id="assessment-slug"
                                    value="{{ $assesment->slug }}">

                                <div class="card bg-dark-lt border-0 shadow-sm mb-4">

                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-database me-2"></i>
                                            Add Question Bank
                                        </h3>
                                    </div>

                                    <div class="card-body">

                                        <div class="row align-items-end">

                                            <div class="col-lg-7">
                                                <label class="form-label required">
                                                    Question Bank
                                                </label>

                                                <x-select
                                                    name="id_question_bank"
                                                    id="id-question-bank"
                                                    class="select2">

                                                    <option value=""
                                                            data-total-questions="0">
                                                        - Choose Question Bank -
                                                    </option>

                                                    @foreach ($questionBanks as $questionBank)
                                                        <option
                                                            value="{{ $questionBank->id }}"
                                                            data-total-questions="{{ count($questionBank->questions) }}">
                                                            {{ $questionBank->title }}
                                                            ({{ count($questionBank->questions) }})
                                                        </option>
                                                    @endforeach

                                                </x-select>

                                                <div class="invalid-feedback d-none"
                                                    id="invalid-id-question-bank">
                                                </div>
                                            </div>

                                            <div class="col-lg-3">
                                                <label class="form-label required">
                                                    Question Used
                                                </label>

                                                <input
                                                    type="number"
                                                    min="0"
                                                    name="amount_question"
                                                    id="amount-question"
                                                    value=""
                                                    class="form-control">

                                                <div class="invalid-feedback d-none"
                                                    id="invalid-amount-question">
                                                </div>
                                            </div>

                                            <div class="col-lg-2">
                                                <button
                                                    type="button"
                                                    id="btn-store-qeustion-bank"
                                                    class="btn btn-success w-100">
                                                    <i class="fas fa-plus me-2"></i>
                                                    Add
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="row">
                                <div class="col-12">

                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header">
                                            <div class="row align-items-center w-100">
                                                <div class="col">
                                                    <h3 class="card-title mb-0">
                                                        <i class="fas fa-list-ul me-2"></i>
                                                        List of Questions Bank
                                                    </h3>
                                                </div>

                                                <div class="col-auto">
                                                    <span class="badge bg-primary-lt text-primary">
                                                        Total: {{ $assessmentQuestionBanks->count() ?? 0 }} Bank
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-vcenter card-table table-hover">
                                                <thead>
                                                    <tr class="text-center">
                                                        <th style="width: 70px;">No</th>
                                                        <th class="text-start">Question Bank</th>
                                                        <th>Total Questions in the Bank</th>
                                                        <th>Total Question Used/Bank</th>
                                                        <th style="width: 160px;">Action</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    @forelse ($assessmentQuestionBanks as $aqb)
                                                        <tr>
                                                            <td class="text-center text-muted">
                                                                {{ $loop->iteration }}
                                                            </td>

                                                            <td>
                                                                <div class="fw-semibold">
                                                                    {{ $aqb->question_bank->title }}
                                                                </div>
                                                                <div class="text-muted small">
                                                                    Question bank source
                                                                </div>
                                                            </td>

                                                            <td class="text-center">
                                                                <span class="badge bg-blue-lt text-blue">
                                                                    {{ count($aqb->question_bank->questions) }} Questions
                                                                </span>
                                                            </td>

                                                            <td class="text-center">
                                                                <span class="badge bg-green-lt text-green">
                                                                    {{ $aqb->questions_amount }} Used
                                                                </span>
                                                            </td>

                                                            <td class="text-center">
                                                                <div class="btn-list justify-content-center flex-nowrap">
                                                                    <a
                                                                        href="{{ route('assessment.syncronize.question.bank', $aqb->id) }}"
                                                                        class="btn btn-primary btn-sm"
                                                                        title="Syncronize">

                                                                        <i class="fas fa-sync-alt"></i>
                                                                    </a>

                                                                    <a
                                                                        href="javascript:void(0)"
                                                                        class="btn btn-warning btn-sm btn-edit-assessment-question-bank"
                                                                        data-id-assessment-question-bank="{{ $aqb->id }}"
                                                                        title="Edit">

                                                                        <i class="fas fa-edit"></i>
                                                                    </a>

                                                                    @if (count($aqb->assessment->scores) < 1)
                                                                        <a
                                                                            href="javascript:void(0)"
                                                                            class="btn btn-danger btn-sm btn-delete-assessment-question-bank"
                                                                            data-question-bank-title="{{ $aqb->question_bank->title }}"
                                                                            data-id-assessment-question-bank="{{ $aqb->id }}"
                                                                            title="Delete">

                                                                            <i class="fas fa-trash-alt"></i>
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center py-5">
                                                                <div class="empty">
                                                                    <div class="empty-icon">
                                                                        <i class="fas fa-database fa-2x text-muted"></i>
                                                                    </div>

                                                                    <p class="empty-title">
                                                                        No Data Available
                                                                    </p>

                                                                    <p class="empty-subtitle text-muted">
                                                                        Question bank has not been added to this assessment.
                                                                    </p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>

                                                @isset($assessmentQuestionBanks)
                                                    <tfoot>
                                                        <tr class="fw-bold">
                                                            <td colspan="3" class="text-end">
                                                                Total Assessment Questions
                                                            </td>

                                                            <td class="text-center">
                                                                <span class="badge bg-primary-lt text-primary">
                                                                    {{ $totalQuestions }}
                                                                </span>
                                                            </td>

                                                            <td></td>
                                                        </tr>
                                                    </tfoot>
                                                @endisset
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <hr class="my-2">
                            
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-primary pb-auto px-5" id="btn-submit-form-update-assessment">Submit</button>
                            </div>
                        </x-form>
                    </div>
                </div>
            </div>
        </div>
    </div>

{{-- Modal: Add Question Bank Success --}}
<div class="modal fade" id="modal-add-question-bank" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">

            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>

            <div class="modal-status bg-success"></div>

            <div class="modal-body text-center py-5 px-4">

                <div class="avatar avatar-xl bg-success-lt text-success mb-3">
                    <i class="fas fa-check fa-lg"></i>
                </div>

                <h3 class="mb-2">
                    Questions Added Successfully
                </h3>

                <div class="text-secondary" id="message-add-question-bank"></div>

            </div>

        </div>
    </div>
</div>


{{-- Modal: Question Bank Already Exist --}}
<div class="modal fade" id="modal-added-question-bank" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">

            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>

            <div class="modal-status bg-warning"></div>

            <div class="modal-body text-center py-5 px-4">

                <div class="avatar avatar-xl bg-warning-lt text-warning mb-3">
                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                </div>

                <h3 class="mb-2">
                    Question Bank Already Exists
                </h3>

                <div class="text-secondary" id="message-added-question-bank"></div>

            </div>

        </div>
    </div>
</div>


{{-- Modal: Edit Question Bank --}}
<div class="modal fade" id="modal-edit-assessment-question-bank" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">

            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>

            <div class="modal-status bg-warning"></div>

            <div class="modal-header border-0 pb-0">
                <div>
                    <h3 class="modal-title mb-1">
                        <i class="fas fa-edit me-2 text-warning"></i>
                        Edit Question Bank
                    </h3>

                    <div class="text-secondary small">
                        Update total question used from this question bank.
                    </div>
                </div>
            </div>

            <div class="modal-body pt-3">

                <form method="post" id="form-edit-assessment-question-bank">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="id_assessment_question_bank" id="id-assessment-question-bank">

                    <div class="card bg-dark-lt border-0 mb-3">
                        <div class="card-body">

                            <div class="mb-3">
                                <label for="edit-title" class="form-label">
                                    Title
                                </label>

                                <div class="form-control-plaintext fw-semibold" id="edit-title"></div>
                            </div>

                            <div class="mb-0">
                                <label for="edit-total-questions" class="form-label">
                                    Total Questions
                                </label>

                                <div class="form-control-plaintext" id="edit-total-questions"></div>
                            </div>

                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="edit-question-per-bank" class="form-label required">
                            Question/Bank
                        </label>

                        <input
                            type="number"
                            min="1"
                            name="edit_question_per_bank"
                            id="edit-question-per-bank"
                            class="form-control"
                            autocomplete="off">
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning px-5">
                            <i class="fas fa-edit me-2"></i>
                            Update
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</div>


{{-- Modal: Delete Question Bank --}}
<div class="modal fade" id="modal-delete-question-bank" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">

            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>

            <div class="modal-status bg-danger"></div>

            <div class="modal-body text-center py-5 px-4">

                <div class="avatar avatar-xl bg-danger-lt text-danger mb-3">
                    <i class="fas fa-trash-alt fa-lg"></i>
                </div>

                <h3 class="mb-2">
                    Are you sure?
                </h3>

                <div class="text-secondary">
                    Want to delete <span class="fw-semibold" id="question-bank-title"></span>?
                </div>

            </div>

            <div class="modal-footer border-0 pt-0">
                <div class="w-100">
                    <div class="row g-2">

                        <div class="col">
                            <a href="javascript:void(0)" class="btn w-100" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>
                                Cancel
                            </a>
                        </div>

                        <div class="col">
                            <form method="get" id="form-delete-assessment-question-bank">
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-trash-alt me-2"></i>
                                    Delete
                                </button>
                            </form>
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
    <script>
        $('.select2').select2({
            theme: 'bootstrap-5',
        });
    </script>
    <script>
        $('#btn-submit-form-update-assessment').on('click', function() {
            $('#form-update-assessment').submit();
        });
    </script>
    <script>
        $('#btn-store-qeustion-bank').on('click', function() {
            let assessmentSlug   = $('#assessment-slug').val();
            let idQuestionBank   = $('#id-question-bank').val();
            let amountQuestion   = parseInt($('#amount-question').val() == '' ? 0 : $('#amount-question').val());
            let totalQuestions   = parseInt($('#id-question-bank option:selected').attr('data-total-questions'));            
            let routeAddQuestion = "{{ route('assessment.store.question.bank', ':slug') }}";
            routeAddQuestion = routeAddQuestion.replace(':slug', assessmentSlug);

            if (idQuestionBank != "" && amountQuestion > 0 ) {
                if (amountQuestion > totalQuestions) {
                    alert('Please fill amount of questions max ' + totalQuestions + '.');
                } else {
                    $.ajax({
                        url: routeAddQuestion,
                        type: "POST",
                        cache: false,
                        data: {
                            "_token" : "{{ csrf_token() }}",
                            "assessment_slug" : assessmentSlug,
                            "id_question_bank" : idQuestionBank,
                            "amount_question" : amountQuestion,
                        },
                        success: function(data) {
                            if (data.status == 422) {
                                $('#modal-added-question-bank').modal('show');
                                $('#message-added-question-bank').html(data.message);
                            } else {
                                $('#modal-add-question-bank').modal('show');
                                $('#message-add-question-bank').html(data.message);
            
                                setInterval(() => {
                                    $('#modal-add-question-bank').modal('hide');
                                    location.reload();
                                }, 2000);
                            }
                        }
                    });
                }
            } else {
                alert('Please select question bank and fill amount of question min 1.');
            }
        });
    </script>
    
    {{-- Edit and Update Script Assessment Question Bank --}}
    <script>
        $('.btn-edit-assessment-question-bank').on('click', function() {
            let idAssessmentQuestionBank = $(this).data('id-assessment-question-bank');
            let routeEdit                = "{{ route('assessment.edit.question.bank', ':id') }}";
            
            $.ajax({
                url: routeEdit.replace(':id', idAssessmentQuestionBank),
                type: "GET",
                cache: false,
                success: function(response) {
                    $('#edit-title').html(response.data.question_bank_title);
                    $('#edit-id-assessment-question-bank').val(idAssessmentQuestionBank);
                    $('#edit-total-questions').html(response.data.total_questions);
                    $('#edit-question-per-bank').val(response.data.question_per_bank);
                    $('#edit-question-per-bank').attr('max', response.data.total_questions);

                    let routeUpdate = "{{ route('assessment.update.question.bank', ':id') }}";
                    $('#form-edit-assessment-question-bank').attr('action', routeUpdate.replace(':id', idAssessmentQuestionBank) );

                    $('#modal-edit-assessment-question-bank').modal('show');
                }
            })
        });
    </script>

    {{-- Delete Assessment Question Bank --}}
    <script>
        $('.btn-delete-assessment-question-bank').on('click', function () { 
            let idAssessmentQuestionBank = $(this).data('id-assessment-question-bank');
            let routeDelete              = "{{ route('assessment.delete.question.bank', ':id') }}";

            $('#question-bank-title').html($(this).data('question-bank-title'));
            $('#form-delete-assessment-question-bank').attr('action', routeDelete.replace(':id', idAssessmentQuestionBank));

            $('#modal-delete-question-bank').modal('show');
        });
    </script>
@endsection
