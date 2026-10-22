@extends('layouts.sidebar-master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
    <style>
        .btn-back {
            color: #0c5b9e !important;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        .btn-back:hover {
            opacity: 0.85;
            transform: translateX(-3px);
        }
    </style>
@endpush

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <!-- TOMBOL BACK RATA KIRI ATAS -->
            <div class="mb-3">
                <a href="{{ route('question.bank.index') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none">
                    <i class="ti ti-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>

            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Manage Question Bank</h2>
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
    
                        <x-form id="questionForm" action="{{ route('question.bank.store.question', $questionBank->id) }}" method="post" enctype="multipart/form-data">
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
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <a href="{{ route('question.bank.edit.question', ['question_bank_slug' => $questionBank->slug, 'question_id' => $question->id]) }}" class="btn btn-info btn-sm p-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                                            <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                                            <path d="M16 5l3 3"></path>
                                                        </svg>
                                                    </a>
                                                    <form action="{{ route('question.bank.delete.question', ['question_bank_slug' => $questionBank->slug, 'question_id' => $question->id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm p-2" onclick="return confirm('Are you sure want to delete this data?')"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M4 7l16 0"></path>
                                                            <path d="M10 11l0 6"></path>
                                                            <path d="M14 11l0 6"></path>
                                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                        </svg></button>
                                                    </form>
                                                </div>
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

    {{-- Unsaved Changes Confirmation Modal --}}
    <div class="modal modal-blur fade" id="unsavedChangesModal" tabindex="-1" role="dialog" aria-labelledby="unsavedChangesModalLabel" aria-hidden="true" data-bs-backdrop="static" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow rounded-3 border-0">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center bg-warning-lt text-warning rounded" style="width: 20px; height: 20px; font-size: 14px;">
                            <i class="ti ti-alert-triangle" style="font-size: 14px;"></i>
                        </span>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="unsavedChangesModalLabel">Unsaved Changes</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-dark mb-0" style="color: #212529 !important; font-size: 0.95rem; line-height: 1.5;">
                        Are you sure you want to leave? Any unsaved changes will be lost, or you can stay to finish editing.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-danger d-inline-flex align-items-center gap-1" id="btnDiscardAndLeave">
                        <i class="ti ti-trash"></i> Discard & Leave
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-info text-white" id="btnKeepEditing" data-bs-dismiss="modal" data-dismiss="modal">
                            Keep Editing
                        </button>
                        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" id="btnSaveDraftAndExit">
                            <i class="ti ti-device-floppy"></i> <span class="btn-save-text">Save as Draft & Exit</span>
                        </button>
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

        function openUnsavedChangesModal() {
            var modalEl = document.getElementById('unsavedChangesModal');
            if (!modalEl) return;

            if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                $(modalEl).modal('show');
            } else if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function') {
                var modalInstance = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
                modalInstance.show();
            } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                var modalInstance = window.bootstrap.Modal.getOrCreateInstance ? window.bootstrap.Modal.getOrCreateInstance(modalEl) : new window.bootstrap.Modal(modalEl);
                modalInstance.show();
            } else {
                modalEl.style.display = 'block';
                modalEl.classList.add('show');
                document.body.classList.add('modal-open');
                if (!document.querySelector('.modal-backdrop')) {
                    var bd = document.createElement('div');
                    bd.className = 'modal-backdrop fade show';
                    document.body.appendChild(bd);
                }
            }
        }

        function closeUnsavedChangesModal() {
            var modalEl = document.getElementById('unsavedChangesModal');
            if (!modalEl) return;

            if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                $(modalEl).modal('hide');
            } else if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function') {
                var modalInstance = bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(modalEl) : null;
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    (new bootstrap.Modal(modalEl)).hide();
                }
            } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                var modalInstance = window.bootstrap.Modal.getInstance ? window.bootstrap.Modal.getInstance(modalEl) : null;
                if (modalInstance) {
                    modalInstance.hide();
                }
            } else {
                modalEl.style.display = 'none';
                modalEl.classList.remove('show');
                document.body.classList.remove('modal-open');
                var bd = document.querySelector('.modal-backdrop');
                if (bd) bd.remove();
            }
        }

        // 1. Back button click handler (both jQuery and native)
        $(document).on('click', '.btn-back', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openUnsavedChangesModal();
        });

        // 2. Modal dismissal handlers ("X" and "Keep Editing")
        $(document).on('click', '#unsavedChangesModal .btn-close, #unsavedChangesModal [data-bs-dismiss="modal"], #unsavedChangesModal [data-dismiss="modal"], #btnKeepEditing', function (e) {
            e.preventDefault();
            closeUnsavedChangesModal();
        });

        // 3. "Discard & Leave" handler
        $(document).on('click', '#btnDiscardAndLeave', function (e) {
            e.preventDefault();
            window.location.href = "{{ route('question.bank.index') }}";
        });

        // 4. "Save as Draft & Exit" handler
        $(document).on('click', '#btnSaveDraftAndExit', function (e) {
            e.preventDefault();
            var saveBtn = this;
            var btnText = saveBtn.querySelector('.btn-save-text');
            saveBtn.disabled = true;
            if (btnText) btnText.textContent = 'Saving Draft...';

            if (window.tinyMCE) {
                window.tinyMCE.triggerSave();
            }

            var form = document.getElementById('questionForm') || document.querySelector('form.card-body, form') || document.forms[0];
            if (form) {
                var formData = new FormData(form);
                formData.set('action', 'draft');

                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                    || form.querySelector('input[name="_token"]')?.value;

                var targetUrl = form.getAttribute('action') || form.action;

                fetch(targetUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin'
                })
                .then(function (response) {
                    if (!response.ok) {
                        return response.json().then(function (err) { throw err; });
                    }
                    return response.json();
                })
                .then(function (data) {
                    window.location.href = (data && data.redirect) ? data.redirect : "{{ route('question.bank.index') }}";
                })
                .catch(function (err) {
                    console.error('AJAX draft save failed:', err);
                    if (err && err.redirect) {
                        window.location.href = err.redirect;
                        return;
                    }
                    // Safely exit to question bank index without blind form.submit() which causes 419 Page Expired
                    window.location.href = "{{ route('question.bank.index') }}";
                });
            } else {
                window.location.href = "{{ route('question.bank.index') }}";
            }
        });

        // Vanilla fallback listener
        document.addEventListener('click', function (e) {
            var backBtn = e.target.closest('.btn-back');
            if (backBtn) {
                e.preventDefault();
                e.stopPropagation();
                openUnsavedChangesModal();
                return;
            }

            var dismissBtn = e.target.closest('#unsavedChangesModal .btn-close, #unsavedChangesModal [data-bs-dismiss="modal"], #unsavedChangesModal [data-dismiss="modal"], #btnKeepEditing');
            if (dismissBtn) {
                e.preventDefault();
                closeUnsavedChangesModal();
                return;
            }

            var discardBtn = e.target.closest('#btnDiscardAndLeave');
            if (discardBtn) {
                e.preventDefault();
                window.location.href = "{{ route('question.bank.index') }}";
                return;
            }
        });
    </script>
@endpush