@extends('layouts.sidebar-master')

@section('content')
    @include('main.assesment.partials.subpage-header', [
        'backUrl'    => route('assesment.question', $assesment->slug),
        'breadcrumb' => ['Question', 'Edit'],
        'title'      => 'Edit Question',
    ])

    <div class="page-body">
        <div class="container-xl">

            <x-alert type='success' />

            <x-form
                class="card border-0 shadow-sm mb-4"
                action="{{ route('assesment.question.update', [$assesment->slug, $data->id]) }}"
                method="post"
                enctype="multipart/form-data">

                @csrf
                @method('put')

                <div class="card-header bg-primary-lt">
                    <div class="row align-items-center w-100">
                        <div class="col">
                            <h3 class="card-title fw-bold mb-1">
                                <i class="fas fa-edit me-2 text-primary"></i>
                                Assessment Question and Answer
                            </h3>

                            <div class="text-muted small">
                                Update question, image, options, and correct answer.
                            </div>
                        </div>

                        <div class="col-auto">
                            <span class="badge bg-primary-lt text-primary">
                                Edit Question
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body">

                    <div class="card bg-dark-lt border-0 mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-align-left me-2"></i>
                                Question Detail
                            </h3>
                        </div>

                        <div class="card-body">
                            <div class="mb-4">
                                <x-label class="form-label">
                                    Question
                                </x-label>

                                <x-textarea
                                    name="question"
                                    rows="6"
                                    placeholder="Input question.."
                                    data-bs-toggle="autosize"
                                    id="tinymce-mytextarea">{{ $data->question }}</x-textarea>
                            </div>

                            <div class="mb-0">
                                <x-label class="form-label">
                                    Image
                                </x-label>

                                @if (($data->image != '') or ($data->image != NULL))
                                    <div class="card bg-dark-lt border-0 mb-3" id="container-img">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span
                                                        class="avatar avatar-2xl rounded"
                                                        style="background-image: url({{ asset('storage/assesment/') .'/'. $data->image }})">
                                                    </span>
                                                </div>

                                                <div class="col">
                                                    <div class="fw-semibold">
                                                        Current Question Image
                                                    </div>
                                                    <div class="text-muted small">
                                                        Existing image attached to this question.
                                                    </div>
                                                </div>

                                                <div class="col-auto">
                                                    <a
                                                        href="#"
                                                        data-url="{{ route('assesment.deleteImage', $data->id) }}"
                                                        class="btn btn-danger btn-remove-img">

                                                        <i class="fas fa-trash-alt me-2"></i>
                                                        Delete Image
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <img
                                    id="image-preview"
                                    alt="image preview"
                                    style="display:none;height:200px;"
                                    class="img-fluid rounded border mb-3" />

                                <x-input
                                    type="file"
                                    id="image"
                                    name="image"
                                    placeholder="Enter image"
                                    onchange="previewImage();" />
                            </div>
                        </div>
                    </div>

                    <div class="card bg-dark-lt border-0">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list-check me-2"></i>
                                Options and Answer
                            </h3>
                        </div>

                        <div class="card-body">
                            <div class="row">

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-label">
                                            Options
                                        </div>

                                        <x-select class="small" name="option[]" id="options" multiple="multiple">
                                            @foreach ($data->option as $answer)
                                                <option value="{{ $answer }}" selected>{{ $answer }}</option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-label">
                                            Answer
                                        </div>

                                        <x-select name="answer" id="answer">
                                            <option value="">-choose-</option>

                                            @foreach ($data->option as $answer)
                                                <option value="{{ $answer }}" {{ ($data->answer == $answer) ? 'selected' : '' }}>
                                                    {{ $answer }}
                                                </option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-footer bg-transparent d-flex justify-content-end">
                    <x-button class="btn btn-primary px-5">
                        <i class="fas fa-save me-2"></i>
                        Update
                    </x-button>
                </div>
            </x-form>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary-lt">
                    <div class="row align-items-center w-100">
                        <div class="col">
                            <h3 class="card-title fw-bold mb-1">
                                <i class="fas fa-clipboard-list me-2 text-primary"></i>
                                {{ $assesment->title }}
                            </h3>

                            <div class="text-muted small">
                                List of questions and answers for this assessment.
                            </div>
                        </div>

                        <div class="col-auto">
                            <div class="btn-list">
                                <a href="{{ route('assesment.question', $assesment->slug) }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>
                                    Add Question
                                </a>

                                <form name="form1" id="form1" method="POST" action="{{ route('assesment.question.download') }}" class="d-inline">
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
                                        <th style="width: 170px;">Action</th>
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
                                    Add a new question to start building this assessment.
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
        $(function() {
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var el;
            window.TomSelect && (new TomSelect(el = document.getElementById('options'), {
                create: true,
                delimiter: ';',
                copyClassesToDropdown: false,
                dropdownParent: 'body',
                controlInput: '<input>',
                plugins: ['remove_button'],
                render: {
                    item: function(data, escape) {
                        if (data.customProperties) {
                            return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                        }

                        return '<div>' + escape(data.text) + '</div>';
                    },
                    option: function(data, escape) {
                        if (data.customProperties) {
                            return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                        }

                        return '<div>' + escape(data.text) + '</div>';
                    },
                },
                onItemAdd: function(value) {
                    $('#answer').append($('<option>', {
                        value: value,
                        text: value
                    }));
                },
                onItemRemove: function(value) {
                    $("#answer option[value='" + value + "']").remove();
                }
            }));
        });

        function previewImage() {
            document.getElementById("image-preview").style.display = "block";

            var oFReader = new FileReader();
            oFReader.readAsDataURL(document.getElementById("image").files[0]);

            oFReader.onload = function(oFREvent) {
                document.getElementById("image-preview").src = oFREvent.target.result;
            };
        };

        $(document).on('click', '.btn-remove-img', function(e) {
            e.preventDefault();

            if (confirm("Are you sure want to delete this image?")) {
                var column = $(this).data('column');

                $.ajax({
                    type: "PUT",
                    url: $(this).data('url'),
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        if (data.status == 200) {
                            $("#container-img").load(" #container-img > *");
                        }
                    }
                });
            }
        });
    </script>

    <script src="{{ asset('Tabler/dist/libs/tinymce/tinymce.min.js') }}" defer></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let options = {
                selector: '#tinymce-mytextarea',
                height: 300,
                menubar: false,
                statusbar: false,
                plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
                toolbar: 'undo redo | formatselect | ' +
                    'bold italic underline forecolor backcolor | bullist numlist outdent indent | ' +
                    'removeformat | table',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }'
            }

            if (localStorage.getItem("tablerTheme") === 'dark') {
                options.skin = 'oxide-dark';
                options.content_css = 'dark';
            }

            tinyMCE.init(options);
        })
    </script>
@endsection