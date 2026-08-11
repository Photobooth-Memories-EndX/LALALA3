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
                        <i class="fas fa-question-circle me-2 text-primary"></i>
                        Manage Assessment
                    </h2>
                </div>

                <div class="col-auto">
                    <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item"><a href="#">Manage</a></li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('assesment.index') }}">Assessment</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Questions
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            {{-- <x-alert type='success' /> --}}

            {{-- <x-form class="card card-md" action="{{ route('assesment.question.store', $assesment->slug) }}" method="post" enctype="multipart/form-data">
                @csrf
                @method('POST')
                <div class="card-header">
                    <h3 class="card-title">Assesment Question and Answer</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <x-label class="form-label">Question</x-label>
                        <x-textarea name="question" rows="6" placeholder="Input question.." data-bs-toggle="autosize" id="tinymce-mytextarea">{{ old('question') }}</x-textarea>
                    </div>
                    <div class="mb-3">
                        <x-label class="form-label">Image</x-label>
                        <img id="image-preview" alt="image preview" style="display:none;height:200px;" class="img-fluid mb-2" />
                        <x-input type="file" id="image" name="image" placeholder="Enter image" onchange="previewImage();" />
                    </div>
                    <div class="mb-3 row">
                        <div class="col-md-6">
                            <div class="form-label">Options</div>
                            <x-select name="option[]" id="options" multiple="multiple"></x-select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-label">Answer</div>
                            <x-select name="answer" id="answer">
                                <option value="">-choose-</option>
                            </x-select>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <x-button>Submit</x-button>
                </div>
            </x-form> --}}

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary-lt">
                    <div class="row align-items-center w-100">
                        <div class="col">
                            <h3 class="card-title fw-bold mb-1">
                                <i class="fas fa-clipboard-list me-2 text-primary"></i>
                                {{ $assesment->title }}
                            </h3>

                            <div class="text-muted small">
                                List of question and answer for this assessment.
                            </div>
                        </div>

                        <div class="col-auto">
                            <form name="form1" id="form1" method="POST" action="{{ route('assesment.question.download') }}">
                                @csrf

                                <input type="hidden" name="assessment_id" id="assessment_id" value="{{ $assesment->id }}">
                                <input type="hidden" name="slug" id="slug" value="{{ $assesment->slug }}">

                                <x-button class="btn btn-success">
                                    <i class="fas fa-download me-2"></i>
                                    Download
                                </x-button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div id="table-default" class="table-responsive">

                        @unless(count($assesment_question) == 0)
                            <table class="table table-vcenter card-table table-hover mb-0" id="MyTable">
                                <thead>
                                    <tr class="text-center">
                                        <th style="width: 70px;">No</th>
                                        <th class="text-start" style="min-width: 300px;">Question</th>
                                        <th class="text-start" style="min-width: 260px;">Option</th>
                                        <th class="text-start" style="min-width: 180px;">Answer</th>
                                        <th style="width: 130px;">Status</th>
                                        <th style="width: 100px;">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($assesment_question as $list)
                                        <tr>
                                            <td class="text-center text-muted">
                                                <span class="badge bg-secondary-lt text-secondary">
                                                    {{ $loop->index + 1 }}
                                                </span>
                                            </td>

                                            <td>
                                                <div class="fw-semibold text-wrap mb-2">
                                                    {!! $list->question !!}
                                                </div>

                                                @if ($list->image != '')
                                                    <div class="mt-2">
                                                        <img
                                                            src="{{ asset('storage/assesment').'/'.$list->image }}"
                                                            class="img-fluid rounded border"
                                                            style="height:90px;">
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="list-group list-group-flush">
                                                    @foreach ($list->option as $option)
                                                        <div class="list-group-item px-0 py-2 bg-transparent border-0 border-bottom">
                                                            <span class="text-muted">
                                                                {{ $option }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>

                                            <td>
                                                <span class="badge bg-blue-lt text-blue text-wrap">
                                                    {{ $list->answer }}
                                                </span>
                                            </td>

                                            <td class="text-center">
                                                @if ($list->status)
                                                    <span class="badge bg-green-lt text-green">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Aktif
                                                    </span>
                                                @else
                                                    <span class="badge bg-red-lt text-red">
                                                        <i class="fas fa-times-circle me-1"></i>
                                                        Non Aktif
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-list justify-content-center flex-nowrap">

                                                    <a href="{{ route('assesment.question.edit', [$assesment->slug, $list->id]) }}"
                                                    class="btn btn-sm btn-warning d-inline-flex align-items-center"
                                                    title="Edit Question">

                                                        <i class="fas fa-pen mr-1"></i>
                                                        Edit
                                                    </a>

                                                    <form action="{{ route('assesment.question.destroy', [$assesment->slug, $list->id]) }}"
                                                        method="POST"
                                                        class="d-inline">

                                                        @csrf
                                                        @method('DELETE')

                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-danger d-inline-flex align-items-center"
                                                            onclick="return confirm('Delete this question ?')"
                                                            title="Delete Question">

                                                            <i class="fas fa-trash-alt mr-1"></i>
                                                            Delete
                                                        </button>

                                                    </form>

                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="empty py-5">
                                <div class="empty-icon">
                                    <i class="fas fa-question-circle fa-3x text-muted"></i>
                                </div>

                                <p class="empty-title">
                                    No question-answer found for this assessment
                                </p>

                                <p class="empty-subtitle text-muted">
                                    This assessment does not have question data yet.
                                </p>
                            </div>
                        @endunless

                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('style')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet" />
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('Tabler/dist/libs/tom-select/dist/js/tom-select.complete.min.js?1684106062') }}"></script>

    <script>
        $(function () {
            $("#MyTable").DataTable({
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'All'],
                ],
                "pageLength": 10,
                "autoWidth": false,
            });
        });
    </script>
@endsection