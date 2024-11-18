@extends('layouts.master')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Manage Assesment</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('assesment.index') }}">Assesment</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Edit</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="page page-center">
            <div class="container">
                <div class="card card-md">
                    <div class="card-header">
                        <h3 class="card-title">Edit Assesment</h3>
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

                            <div class="mb-3 row">
                                <x-label class="col-3 col-form-label required">Type</x-label>
                                <div class="col">
                                    <x-select name="type" onchange="setRequired(this);">
                                        <option value="">-choose-</option>
                                        <option value="content" {{ ($assesment->type == 'content') ? 'selected' : ''  }}>Assesment for content</option>
                                        <option value="trainingplan" {{ ($assesment->type == 'trainingplan') ? 'selected' : ''  }}>Assesment for playlist</option>
                                        <option value="global" {{ ($assesment->type == 'global') ? 'selected' : ''  }}>Global Assesment</option>
                                    </x-select>
                                </div>
                            </div>
                            <div class="mb-3 row {{ ($assesment->type == 'trainingplan') || ($assesment->type == 'global') ? 'd-none' : ''  }}" id="content_id_wrapper">
                                <x-label class="col-3 col-form-label">Learning Content</x-label>
                                <div class="col">
                                    <x-select name="content_id" id="content_id">
                                        <option value="">-choose-</option>
                                        @foreach ($contents as $item)
                                        <option value="{{ $item->id }}" {{ ($assesment->content_id == $item->id) ? 'selected' : ''  }}>{{ $item->title }}</option>
                                        @endforeach
                                    </x-select>
                                </div>
                            </div>
                            <div class="mb-3 row {{ ($assesment->type == 'content') || ($assesment->type == 'global') ? 'd-none' : ''  }}" id="trainingplan_id_wrapper">
                                <x-label class="col-3 col-form-label">Training Plan</x-label>
                                <div class="col">
                                    <x-select name="trainingplan_id" id="trainingplan_id">
                                        <option value="">-choose-</option>
                                        @foreach ($trainingplan as $item)
                                            <option value="{{ $item->id }}" {{ ($assesment->trainingplan_id == $item->id) ? 'selected' : ''  }}>{{ $item->title }}</option>
                                        @endforeach
                                    </x-select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <x-label class="col-3 col-form-label required">Title</x-label>
                                <div class="col">
                                    <x-input type="text" name="title" placeholder="Enter title" value="{{ $assesment->title }}" />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <x-label class="col-3 col-form-label required">Passing Grade</x-label>
                                <div class="col">
                                    <x-input type="number" name="passing_grade" placeholder="Enter passing grade" value="{{ $assesment->passing_grade }}" />
                                </div>
                                <x-label class="col-2 col-form-label required">Time Limit?</x-label>
                                <div class="col">
                                    <x-select name="timer" onchange="setRequiredDate(this);">
                                        <option value="">-choose-</option>
                                        <option value="y" {{ ($assesment->timer == 'y') ? 'selected' : ''  }}>Yes</option>
                                        <option value="n" {{ ($assesment->timer == 'n') ? 'selected' : ''  }}>No</option>
                                    </x-select>
                                </div>
                            </div>
                            <div class="mb-3 row" id="date_wrapper">
                                <x-label class="col-3 col-form-label required">From Date</x-label>
                                <div class="col">
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                                                <path d="M16 3v4" />
                                                <path d="M8 3v4" />
                                                <path d="M4 11h16" />
                                                <path d="M11 15h1" />
                                                <path d="M12 15v3" />
                                            </svg>
                                        </span>
                                        <x-input placeholder="Select a date" id="datepicker-fromdate" name="fromdate" value="{{ $assesment->fromdate }}" required class="input-date" />
                                    </div>
                                </div>

                                <x-label class="col-2 col-form-label required">To Date</x-label>
                                <div class="col">
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                                                <path d="M16 3v4" />
                                                <path d="M8 3v4" />
                                                <path d="M4 11h16" />
                                                <path d="M11 15h1" />
                                                <path d="M12 15v3" />
                                            </svg>
                                        </span>
                                        <x-input placeholder="Select a date" id="datepicker-todate" name="todate" value="{{ $assesment->todate }}" required class="input-date" />
                                    </div>
                                </div>
                            </div>

                            <hr class="my-2">
                            
                            <form action="{{ route('assessment.store.question.bank', $assesment->slug) }}" id="form-store-question-bank" method="post">
                                @csrf
                                <input type="hidden" id="assessment-slug" value="{{ $assesment->slug }}">
                                <div class="mb-3 row align-items-end">
                                    <div class="col-11">
                                        <div class="row align-items-center">
                                            <x-label class="col-2 form-label mb-0 required">Question Bank</x-label>
                                            <div class="col-6">
                                                <x-select name="id_question_bank" id="id-question-bank" class="select2 m-0">
                                                    <option value="" data-total-questions="0">- Choose Question Bank -</option>
                                                    @foreach ($questionBanks as $questionBank)
                                                        <option value="{{ $questionBank->id }}" data-total-questions="{{ count($questionBank->questions) }}">{{ $questionBank->title }} ( {{ count($questionBank->questions) }} )</option>
                                                    @endforeach
                                                </x-select>
                                                <div class="invalid-feedback d-none" id="invalid-id-question-bank"></div>
                                            </div>
                                            <x-label class="col-2 form-label text-end mb-0 required">Question/Bank</x-label>
                                            <div class="col-2">
                                                <input type="number" min="0" name="amount_question" id="amount-question" value="0" class="form-control m-0">
                                                <div class="invalid-feedback d-none" id="invalid-amount-question"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-1 pt-auto">
                                        <button type="button" id="btn-store-qeustion-bank" class="btn btn-success w-100 pb-auto"><i class="fas fa-plus me-2"></i>Add</button>
                                    </div>
                                </div>
                            </form>

                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <table class="table table-bordered table-striped w-100">
                                                <thead>
                                                    <tr>
                                                        <th colspan="5" class="text-center p-2">List of Questions Bank</th>
                                                    </tr>
                                                    <tr class="text-center">
                                                        <th class="p-2">No</th>
                                                        <th class="p-2">Question Bank</th>
                                                        <th class="p-2">Total Questions</th>
                                                        <th class="p-2">Question/Bank</th>
                                                        <th class="p-2">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($assessmentQuestionBanks as $aqb)
                                                        @php
                                                            $assesmentQuestions = App\Models\AssesmentQuestion::where('question_bank_id', $aqb->question_bank_id)->get();
                                                        @endphp
                                                        <tr>
                                                            <td class="p-1 text-center">{{ $loop->iteration }}</td>
                                                            <td class="p-1">{{ $aqb->question_bank->title }}</td>
                                                            <td class="p-1 text-center">{{ count($assesmentQuestions) }}</td>
                                                            <td class="p-1 text-center">{{ $aqb->questions_amount }}</td>
                                                            <td class="p-1 text-center">
                                                                <a href="{{ route('assessment.syncronize.question.bank', $aqb->id) }}" class="btn btn-primary btn-sm" title="Syncronize"><i class="fas fa-sync-alt"></i></a>
                                                                <a href="javascript:void(0)" class="btn btn-warning btn-sm btn-edit-assessment-question-bank" data-id-assessment-question-bank="{{ $aqb->id }}" title="Edit"><i class="fas fa-edit"></i></a>
                                                                @if (count($aqb->assessment->scores) < 1)
                                                                    <a href="javascript:void(0)" class="btn btn-danger btn-sm btn-delete-assessment-question-bank" data-question-bank-title="{{ $aqb->question_bank->title }}" data-id-assessment-question-bank="{{ $aqb->id }}" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center">No Data Available</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                                @isset($assessmentQuestionBanks)
                                                    <tfoot>
                                                        <tr class="fw-bold">
                                                            <td class="p-1 text-end" colspan="3">Total Assessment Questions</td>
                                                            <td class="p-1 text-center">{{ $totalQuestions }}</td>
                                                            <td class="p-1"></td>
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

    <div class="modal fade" id="modal-add-question-bank" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-success"></div>
                <div class="modal-body text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-green icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <circle cx="12" cy="12" r="9" />
                        <path d="M9 12l2 2l4 -4" />
                    </svg>
                    <h3>Questions Added Successfully.</h3>
                    <div class="text-secondary" id="message-add-question-bank"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-added-question-bank" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-warning"></div>
                <div class="modal-body text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-warning icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 9v2m0 4v.01" />
                        <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75" />
                    </svg>
                    <h3>Question Bank already exist!</h3>
                    <div class="text-secondary" id="message-added-question-bank"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-edit-assessment-question-bank" tabindex="-1">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-warning"></div>
                <div class="modal-body py-4">
                    <h3 class="text-center">Edit Question Bank.</h3>
                    <form method="post" id="form-edit-assessment-question-bank">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id_assessment_question_bank" id="id-assessment-question-bank">
                        <div class="row mb-3">
                            <div class="col-3">
                                <div class="d-flex justify-content-between">
                                    <label for="edit-title" >Title</label>
                                    <span>:</span>
                                </div>
                            </div>
                            <div class="col-9">
                                <p class="mb-0" id="edit-title"></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-3">
                                <div class="d-flex justify-content-between">
                                    <label for="edit-total-questions" >Total Questions</label>
                                    <span>:</span>
                                </div>
                            </div>
                            <div class="col-9">
                                <p class="mb-0" id="edit-total-questions"></p>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-3">
                                <div class="d-flex justify-content-between">
                                    <label for="edit-question-per-bank" >Question/Bank</label>
                                    <span>:</span>
                                </div>
                            </div>
                            <div class="col-9">
                                <input type="number" min="1" name="edit_question_per_bank" id="edit-question-per-bank" class="form-control" autocomplete="off">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-warning btn-sm px-5"><i class="fas fa-edit me-2"></i>Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-delete-question-bank" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-danger icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 9v2m0 4v.01" />
                        <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75" />
                    </svg>
                    <h3>Are you sure?</h3>
                    <div class="text-secondary">Want to delete <span id="question-bank-title"></span>.</div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <a href="javascript:void(0)" class="btn w-100" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Cancel</a>
                            </div>
                            <div class="col">
                                <form method="get" id="form-delete-assessment-question-bank">
                                    <button type="submit" class="btn btn-danger w-100"><i class="fas fa-trash-alt me-2"></i>Delete</button>
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
            let amountQuestion   = $('#amount-question').val();
            let totalQuestions   = $('#id-question-bank option:selected').attr('data-total-questions');
            let routeAddQuestion = "{{ route('assessment.store.question.bank', ':slug') }}";
            routeAddQuestion = routeAddQuestion.replace(':slug', assessmentSlug);

            if (idQuestionBank != "" && amountQuestion > 0 && amountQuestion <= totalQuestions) {
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
            } else {
                if (idQuestionBank == "") {
                    alert('Please select question bank and fill amount of question min 1.');
                } else {
                    alert('Please select question bank and fill amount of question min 1, and maks ' + totalQuestions + '.');
                }
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
