@extends('layouts.sidebar-master')

@push('styles')
    <link href="{{ asset('css/bootstrap-toggle.min.css') }}" rel="stylesheet">
    <style>
        .btn-back {
            color: #0c5b9e !important;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        .btn-back:hover {
            opacity: 0.85;
            transform: translateX(-3px);
        }
        .btn-quick-reset {
            border-color: #0c5b9e !important;
            color: #0c5b9e !important;
            background-color: transparent !important;
            transition: background-color .15s ease-in-out, border-color .15s ease-in-out, color .15s ease-in-out;
        }
        .btn-quick-reset:hover {
            background-color: #e6f0fa !important;
            border-color: #0c5b9e !important;
            color: #0c5b9e !important;
        }
        .btn-quick-reset:hover i {
            color: #0c5b9e !important;
        }
        [data-bs-theme="dark"] .btn-quick-reset:hover {
            background-color: rgba(12, 91, 158, 0.25) !important;
            border-color: #3b82f6 !important;
            color: #60a5fa !important;
        }
        [data-bs-theme="dark"] .btn-quick-reset:hover i {
            color: #60a5fa !important;
        }
        .manage-questionbank-search-container {
            position: relative;
            width: 100%;
        }
        #manage-questionbank-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 2000;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid #e2e8f0 !important;
            background: #ffffff !important;
            overflow: hidden !important;
        }
        #manage-questionbank-autocomplete-list {
            max-height: 280px !important;
            overflow-y: auto !important;
            background-color: #ffffff !important;
            scrollbar-width: thin;
        }
        #manage-questionbank-search-results::-webkit-scrollbar {
            width: 6px;
        }
        #manage-questionbank-search-results::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        #manage-questionbank-search-results .dropdown-item {
            padding: .6rem .85rem;
            transition: background-color .15s ease;
            text-decoration: none;
        }
        #manage-questionbank-search-results .dropdown-item:hover,
        #manage-questionbank-search-results .dropdown-item:focus {
            background-color: rgba(15, 94, 156, .08);
        }
        #manage-questionbank-search-results .dropdown-item:not(:last-child) {
            border-bottom: 1px solid rgba(0, 0, 0, .05);
        }
        [data-bs-theme="dark"] #manage-questionbank-search-results,
        body.dark-mode #manage-questionbank-search-results,
        body.theme-dark #manage-questionbank-search-results {
            background: #1f2937;
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        [data-bs-theme="dark"] #manage-questionbank-search-results .dropdown-item,
        body.dark-mode #manage-questionbank-search-results .dropdown-item,
        body.theme-dark #manage-questionbank-search-results .dropdown-item {
            color: #f8fafc;
        }
        [data-bs-theme="dark"] #manage-questionbank-search-results .dropdown-item:not(:last-child),
        body.dark-mode #manage-questionbank-search-results .dropdown-item:not(:last-child),
        body.theme-dark #manage-questionbank-search-results .dropdown-item:not(:last-child) {
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }
        [data-bs-theme="dark"] #manage-questionbank-search-results .dropdown-item:hover,
        [data-bs-theme="dark"] #manage-questionbank-search-results .dropdown-item:focus,
        body.dark-mode #manage-questionbank-search-results .dropdown-item:hover,
        body.dark-mode #manage-questionbank-search-results .dropdown-item:focus,
        body.theme-dark #manage-questionbank-search-results .dropdown-item:hover,
        body.theme-dark #manage-questionbank-search-results .dropdown-item:focus {
            background: rgba(106, 174, 214, 0.16);
        }
        [data-bs-theme="dark"] #manage-questionbank-search-results .dropdown-item .text-dark,
        body.dark-mode #manage-questionbank-search-results .dropdown-item .text-dark,
        body.theme-dark #manage-questionbank-search-results .dropdown-item .text-dark {
            color: #f8fafc !important;
        }
    </style>
@endpush

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <!-- TOMBOL BACK RATA KIRI ATAS KE DASHBOARD MANAGE -->
            <div class="mb-3">
                <a href="{{ route('manage.index') }}" class="btn-back mb-3 d-inline-flex align-items-center gap-1 text-decoration-none">
                    <i class="ti ti-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>

            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-lg bg-primary-lt">
                            <i class="ti ti-help fs-2"></i>
                        </div>
                        <div>
                            <h2 class="page-title mb-1">
                                Manage Question Bank
                            </h2>
                            <div class="text-secondary">Manage assessment question repositories, questions, and templates.</div>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('question.bank.create') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M12 5l0 14"></path>
                            <path d="M5 12l14 0"></path>
                        </svg>
                        Add Question Bank
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            <x-alert type='success' />
            <x-alert type='warning' />
            <x-alert type='danger' />

            {{-- Quick Filter Component --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3">
                    <!-- Label Header Quick Filter -->
                    <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                        Quick Filter
                    </div>

                    <form action="{{ route('question.bank.index') }}" method="GET" id="manageQuestionBankFilterForm">
                        <div class="row g-2 align-items-center">
                            <!-- Total Questions Dropdown -->
                            <div class="col-6 col-md-auto">
                                <select name="total_questions" class="form-select rounded-3" onchange="document.getElementById('manageQuestionBankFilterForm').submit()">
                                    <option value="">All Questions</option>
                                    <option value="1_20" {{ request('total_questions') == '1_20' ? 'selected' : '' }}>1 - 20 Questions</option>
                                    <option value="20_40" {{ request('total_questions') == '20_40' ? 'selected' : '' }}>20 - 40 Questions</option>
                                    <option value="40_50" {{ request('total_questions') == '40_50' ? 'selected' : '' }}>40 - 50 Questions</option>
                                    <option value="50_100" {{ request('total_questions') == '50_100' ? 'selected' : '' }}>50 - 100 Questions</option>
                                    <option value="gt_100" {{ request('total_questions') == 'gt_100' ? 'selected' : '' }}>&gt; 100 Questions</option>
                                </select>
                            </div>

                            <!-- Created Date Sort Dropdown -->
                            <div class="col-6 col-md-auto">
                                <select name="sort_created" id="sort-created-select" class="form-select rounded-3" onchange="document.getElementById('manageQuestionBankFilterForm').submit()">
                                    <option value="all" {{ request('sort_created', 'all') == 'all' ? 'selected' : '' }}>Sort Date: All</option>
                                    <option value="oldest" {{ request('sort_created') == 'oldest' ? 'selected' : '' }}>Sort Date: Oldest</option>
                                    <option value="newest" {{ request('sort_created') == 'newest' ? 'selected' : '' }}>Sort Date: Newest</option>
                                </select>
                            </div>

                            <!-- Live Autocomplete Search Input -->
                            <div class="col-12 col-md">
                                <div class="position-relative manage-search-container flex-grow-1">
                                    <div class="input-icon">
                                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                        <input type="text" name="q" id="manage-questionbank-search-input" class="form-control" placeholder="Search question bank..." value="{{ request('q') ?? (request('search') ?? request('s')) }}" autocomplete="off">
                                    </div>
                                    <div id="manage-questionbank-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 p-0 overflow-hidden" 
                                         style="display: none; z-index: 2000; background-color: #ffffff;">
                                        <!-- Container 1: Scrollable List Area (Max 280px height with vertical scrollbar) -->
                                        <div id="manage-questionbank-autocomplete-list" class="list-group list-group-flush" 
                                             style="max-height: 280px; overflow-y: auto; background-color: #ffffff;"></div>
                                        
                                        <!-- Container 2: Fixed/Sticky Bottom Footer -->
                                        <div id="manage-questionbank-search-footer" class="border-top p-2 text-center" 
                                             style="display: none; position: sticky; bottom: 0; z-index: 2005; background-color: #f8f9fa;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-auto d-flex gap-1">
                                <button type="submit" class="btn btn-primary rounded-3 px-3" style="background-color: #0c5b9e; border-color: #0c5b9e;" title="Search">
                                    <i class="ti ti-search"></i>
                                </button>
                                <a href="{{ route('question.bank.index') }}" class="btn btn-outline-primary rounded-3 px-3 btn-quick-reset" style="border-color: #0c5b9e; color: #0c5b9e; background-color: transparent;" title="Reset Filter">
                                    <i class="ti ti-refresh" style="font-size: 1.1rem; color: #0c5b9e !important;"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="card-title mb-1">List Question Bank</h3>
                        <div class="card-subtitle text-muted">
                            Showing {{ $questionBanks->firstItem() ?? 0 }}–{{ $questionBanks->lastItem() ?? 0 }} of {{ $questionBanks->total() }} question bank(s)
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover mb-0" id="table-question-bank">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px;">No</th>
                                    <th>Title</th>
                                    <th class="text-center" style="width: 150px;">Total Questions</th>
                                    <th class="text-center" style="width: 150px;">Created Date</th>
                                    <th class="text-center" style="width: 180px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($questionBanks as $questionBank)
                                    <tr>
                                        <td class="align-middle text-center text-secondary small">
                                            {{ $loop->iteration + (($questionBanks->currentPage() - 1) * $questionBanks->perPage()) }}
                                        </td>
                                        <td class="align-middle">
                                            <span class="fw-semibold text-dark">{{ $questionBank->title }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge bg-blue-lt text-blue fw-bold">
                                                {{ $questionBank->questions_count ?? count($questionBank->questions) }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-center text-secondary small">
                                            {{ $questionBank->created_date ? \Carbon\Carbon::parse($questionBank->created_date)->format('d M Y') : ($questionBank->created_at ? $questionBank->created_at->format('d M Y') : '—') }}
                                        </td>
                                        <td class="align-middle text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="{{ route('question.bank.edit', $questionBank->slug) }}" class="btn btn-warning btn-icon btn-sm" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                                        <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                                        <path d="M16 5l3 3"></path>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('question.bank.create.question', ['question_bank_slug' => $questionBank->slug]) }}" class="btn btn-success btn-icon btn-sm" title="Add Question">
                                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-clipboard-plus">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                                                        <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                                        <path d="M10 14h4" />
                                                        <path d="M12 12v4" />
                                                    </svg>
                                                </a>
                                                <a href="javascript:void(0)" class="btn btn-danger btn-icon btn-sm btn-delete-question-bank" title="Delete" data-title="{{ $questionBank->title }}" data-id="{{$questionBank->id}}">
                                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M4 7l16 0" />
                                                        <path d="M10 11l0 6" />
                                                        <path d="M14 11l0 6" />
                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                    </svg>
                                                </a>
                                                <a href="{{ route('question.bank.clone', $questionBank->slug) }}" data-title="{{$questionBank->title}}" onclick="return confirm('Are you sure want to clone/duplicate ' + $(this).data('title') + '?')" class="btn btn-secondary btn-icon btn-sm" title="Clone">
                                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-library-plus">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M7 3m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" />
                                                        <path d="M4.012 7.26a2.005 2.005 0 0 0 -1.012 1.737v10c0 1.1 .9 2 2 2h10c.75 0 1.158 -.385 1.5 -1" />
                                                        <path d="M11 10h6" />
                                                        <path d="M14 7v6" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty py-5">
                                                <div class="empty-icon">
                                                    <i class="ti ti-help-off" style="font-size:3rem;opacity:.4;"></i>
                                                </div>
                                                <p class="empty-title">No question banks found</p>
                                                <p class="empty-subtitle text-secondary">Try adjusting your filters or add a new question bank.</p>
                                                <div class="empty-action">
                                                    <a href="{{ route('question.bank.create') }}" class="btn btn-primary btn-sm">
                                                        <i class="ti ti-plus me-1"></i>Add Question Bank
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($questionBanks->hasPages())
                    <div class="card-footer d-flex align-items-center justify-content-center py-3">
                        {{ $questionBanks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Delete Confirmation --}}
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
                    <div class="text-secondary">Do you really want to delete <strong id="delete-title-question-bank"></strong> ?</div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <a href="javascript:void(0)" class="btn w-100" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Cancel</a>
                            </div>
                            <div class="col">
                                <form method="post" id="form-delete-question-bank">
                                    @csrf
                                    @method('DELETE')
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

@push('scripts')
    <script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Delete confirmation modal handler
            $('.btn-delete-question-bank').on('click', function () {
                let id         = $(this).data('id');
                let title      = $(this).data('title');
                let route      = "{{ route('question.bank.delete', ':id') }}";
                let routeFixed = route.replace(':id', id);

                $('#delete-title-question-bank').html(title);
                $('#form-delete-question-bank').prop('action', routeFixed);

                $('#modal-delete-question-bank').modal('show');
            });

            // Autocomplete is handled by standardized initManageAutocomplete
        });
    </script>
@endpush