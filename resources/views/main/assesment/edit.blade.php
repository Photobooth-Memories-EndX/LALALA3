@extends('layouts.sidebar-master')

@section('content')
    <div class="assessment-page-header page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">
                    <div class="row align-items-center g-2">
                        <div class="col">
                            <div class="page-pretitle text-muted">
                                Learning Management System
                            </div>
                            <h2 class="page-title fw-bold mb-0">
                                <i class="fas fa-file-alt me-2 text-primary"></i>
                                Manage Assessment
                            </h2>
                        </div>
                        <div class="col-auto">
                            <ol class="breadcrumb breadcrumb-arrows mb-0">
                                <li class="breadcrumb-item"><a href="#">Manage</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('assesment.index') }}">Assessment</a></li>
                                <li class="breadcrumb-item active">Edit</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">

                    {{-- Dynamic Assessment Title Area displayed above Card --}}
                    <div class="mb-3">
                        <h2 class="page-title fw-bold text-primary" id="dynamic_page_title">
                            {{ $assesment->title }}
                        </h2>
                    </div>

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-primary-lt">
                            <h3 class="card-title fw-bold mb-0">
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

                            <x-alert type="success" />

                            {{-- Form Update Assessment (Section 1 & Section 2) --}}
                            <x-form class="" action="{{ route('assesment.update', $assesment->slug) }}" id="form-update-assessment" method="put">

                                {{-- 1. Assessment Configuration --}}
                                @include('main.assesment.partials.section-configuration', ['assesment' => $assesment])

                                {{-- 2. Access Period & Time Limit --}}
                                @include('main.assesment.partials.section-period-timer', ['assesment' => $assesment])

                            </x-form>

                            {{-- 3. Add Question Bank --}}
                            <form action="{{ route('assessment.store.question.bank', $assesment->slug) }}" id="form-store-question-bank" method="post">
                                @csrf
                                <input type="hidden" id="assessment-slug" value="{{ $assesment->slug }}">

                                @include('main.assesment.partials.section-question-bank-add', ['assesment' => $assesment])
                            </form>

                            {{-- 4. Question Bank List --}}
                            @include('main.assesment.partials.section-question-bank-list', ['assesment' => $assesment])

                            {{-- 5. Assessment Actions --}}
                            @include('main.assesment.partials.section-actions', ['assesment' => $assesment])

                        </div>
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
                    <h3 class="mb-2">Questions Added Successfully</h3>
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
                    <h3 class="mb-2">Question Bank Already Exists</h3>
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
                        <input type="hidden" name="id_assessment_question_bank" id="edit-id-assessment-question-bank">

                        <div class="card bg-dark-lt border-0 mb-3">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="edit-title" class="form-label">Title</label>
                                    <div class="form-control-plaintext fw-semibold" id="edit-title"></div>
                                </div>
                                <div class="mb-0">
                                    <label for="edit-total-questions" class="form-label">Total Questions</label>
                                    <div class="form-control-plaintext" id="edit-total-questions"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="edit-question-per-bank" class="form-label required">Question/Bank</label>
                            <input type="number" min="1" name="edit_question_per_bank" id="edit-question-per-bank" class="form-control" autocomplete="off">
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
                    <h3 class="mb-2">Are you sure?</h3>
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
    @include('main.assesment.partials.form-styles')
@endsection

@section('script')
    @include('main.assesment.partials.form-scripts')

    <script>
        $('.select2').select2({
            theme: 'bootstrap-5',
        });

        $('#btn-submit-form-update-assessment').on('click', function() {
            $('#form-update-assessment').submit();
        });

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

        $('.btn-delete-assessment-question-bank').on('click', function () { 
            let idAssessmentQuestionBank = $(this).data('id-assessment-question-bank');
            let routeDelete              = "{{ route('assessment.delete.question.bank', ':id') }}";

            $('#question-bank-title').html($(this).data('question-bank-title'));
            $('#form-delete-assessment-question-bank').attr('action', routeDelete.replace(':id', idAssessmentQuestionBank));

            $('#modal-delete-question-bank').modal('show');
        });
    </script>
@endsection
