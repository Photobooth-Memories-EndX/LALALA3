@extends('layouts.sidebar-master')

@push('styles')
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
                    <h2 class="page-title">
                        Manage Question Bank
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Question Bank</h3>
                </div>
                <div class="card-body">
                    <form id="questionBankForm" action="{{ route('question.bank.store') }}" method="post">
                        @csrf
                        <div class="row align-items-center mb-3">
                            <div class="col-2 text-end">
                                <label for="title" class="form-label mb-0 required">Title</label>
                            </div>
                            <div class="col-10">
                                <input type="text" name="title" id="title" class="form-control" placeholder="Title..." autocomplete="off">
                                @error('title')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-2 text-end">
                                <label for="created-date" class="form-label mb-0 required">Created Date</label>
                            </div>
                            <div class="col-10">
                                <input type="date" name="created_date" id="created-date" class="form-control" value="{{ date('Y-m-d') }}">
                                @error('created_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary px-5 float-end"><i class="fas fa-plus me-2"></i>Submit</button>
                    </form>
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
    <script>
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

            var form = document.getElementById('questionBankForm') || document.querySelector('form');
            if (form) {
                var formData = new FormData(form);
                formData.set('action', 'draft');

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function (response) {
                    if (!response.ok) {
                        return response.json().then(function (err) { throw err; });
                    }
                    return response.json();
                })
                .then(function (data) {
                    window.location.href = "{{ route('question.bank.index') }}";
                })
                .catch(function (err) {
                    console.error('AJAX draft save failed, submitting fallback:', err);
                    var actionInput = form.querySelector('input[name="action"]');
                    if (!actionInput) {
                        actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        form.appendChild(actionInput);
                    }
                    actionInput.value = 'draft';
                    form.submit();
                });
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