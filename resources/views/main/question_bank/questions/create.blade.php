@extends('layouts.sidebar-master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
@endpush

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Manage Question Bank</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('question.bank.index') }}">Question Bank</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Questions</a></li>
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
                        <h3 class="card-title">Management Question and Answers</h3>
                    </div>
                    <div class="card-body p-3">
    
                        <x-alert type='success' />
                        <x-alert type='warning' />
                        <x-alert type='danger' />
    
                        <x-form action="{{ route('question.bank.store.question', $questionBank->id) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <x-label class="form-label">Question</x-label>
                                <x-textarea name="question" rows="3" placeholder="Input question.." data-bs-toggle="autosize" id="tinymce-mytextarea">{{ old('question') }}</x-textarea>
                            </div>
                            <div class="mb-3">
                                <x-label class="form-label">Image</x-label>
                                <img id="image-preview" alt="image preview" style="display:none;height:200px;" class="img-fluid mb-2" />
                                <x-input type="file" id="image" name="image" placeholder="Enter image" onchange="previewImage();" />
                            </div>
                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <div class="form-label">Options</div>
                                    <x-select name="option[]" id="options" multiple="multiple">
                                    </x-select>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-label">Answer</div>
                                    <x-select name="answer" id="answer">
                                        <option value="">-choose-</option>
                                    </x-select>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <x-button class="px-5">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-checklist">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M9.615 20h-2.615a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8" /><path d="M14 19l2 2l4 -4" /><path d="M9 8h4" />
                                        <path d="M9 12h2" />
                                    </svg>
                                    Submit
                                </x-button>
                            </div>
                        </x-form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ $questionBank->title }}</h3>
                        <div class="card-actions row">
                            <div class="col-auto ms-auto d-print-none">
                                <div class="d-flex">
                                    <a href="{{ route('question.bank.download.question', ['question_bank_id' => $questionBank->id]) }}" class="btn btn-success btn-icon" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                                            <path d="M7 11l5 5l5 -5"></path>
                                            <path d="M12 4l0 12"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="table-questions">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th class="text-center">Question</th>
                                        <th class="text-center">Option</th>
                                        <th class="text-center">Answer</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($questions as $question)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td>
                                                {!! $question->question !!}
                                                @if ($question->question_image != '')
                                                    <img src="{{ asset('storage/question_bank/image').'/'.$question->question_image }}" class="img-fluid" style="height:90px;">
                                                @endif
                                            </td>
                                            <td>{!! implode('<hr class="my-1">', $question->option) !!}</td>
                                            <td>{{ $question->answer }}</td>
                                            <td class="text-center">{{ $question->status == true ? 'Aktif' : 'Non Aktif' }}</td>
                                            <td class="text-center">
                                                <div class="d-inline">
                                                    <a href="{{ route('question.bank.edit.question', ['question_bank_slug' => $questionBank->slug, 'question_id' => $question->id]) }}" class="btn btn-info btn-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                                            <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                                            <path d="M16 5l3 3"></path>
                                                        </svg>
                                                    </a>
                                                </div>

                                                <form action="{{ route('question.bank.delete.question', ['question_bank_slug' => $questionBank->slug, 'question_id' => $question->id]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-icon" onclick="return confirm('Are you sure want to delete this data?')"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M4 7l16 0"></path>
                                                        <path d="M10 11l0 6"></path>
                                                        <path d="M14 11l0 6"></path>
                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                    </svg></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $('#table-questions').dataTable({
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, 'All'],
            ],
            "pageLength" : 10,
            "autoWidth": false,
        });
    </script>

    <script src="{{ asset('Tabler/dist/libs/tom-select/dist/js/tom-select.complete.min.js?1684106062') }}"></script>
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
                onItemAdd : function(value) {
                    $('#answer').append($('<option>', {
                        value: value,
                        text: value
                    }));
                },
                onItemRemove : function(value) {
                    $("#answer option[value='"+value+"']").remove();
                }
            }));
        });
    </script>

    <script>
        function previewImage() {
            document.getElementById("image-preview").style.display = "block";
            var oFReader = new FileReader();
            oFReader.readAsDataURL(document.getElementById("image").files[0]);
            oFReader.onload = function(oFREvent) {
                document.getElementById("image-preview").src = oFREvent.target.result;
            };
        };
    </script>

    <script src="{{ asset('Tabler/dist/libs/tinymce/tinymce.min.js') }}" defer></script>
    <script>
        // @formatter:off
        document.addEventListener("DOMContentLoaded", function () {
            let options = {
                selector: '#tinymce-mytextarea',
                height: 150,
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
        });
        // @formatter:on
    </script>
@endpush