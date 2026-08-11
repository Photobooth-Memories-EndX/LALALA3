@php
    $content = $content ?? null;
    $mode = $mode ?? ($content ? 'edit' : 'create');
    $user = $user ?? [];
    $stepNumber = $stepNumber ?? ($mode === 'edit' ? 6 : 5);
    $hasUserList = count($user) > 0;
    $cardTitle = ($mode === 'edit' || $hasUserList) ? 'Assignment & Action' : 'Action';
@endphp

<div class="card owl-main-card mb-4">
    <div class="card-header d-flex align-items-center">
        <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">{{ $stepNumber }}</span>
        <h3 class="card-title mb-0">{{ $cardTitle }}</h3>
    </div>
    <div class="card-body">
        @if ($mode === 'edit' && $content)
            {{-- Top Part: Assignment Section for Edit Mode --}}
            <div class="mb-4">
                <h4 class="form-label font-weight-bold mb-1">Assign Content ke Karyawan</h4>
                <p class="text-muted small mb-3">Pilih karyawan yang akan mendapatkan konten ini sebagai mandatory.</p>

                <form id="contentAssignForm" action="{{ route('content.assign', $content->slug) }}" method="post" autocomplete="off">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-10">
                            <label class="form-label" for="select-user">Employee</label>
                            <select class="form-select" id="select-user" name="employee[]" multiple>
                                @unless (count($user) == 0)
                                    @foreach ($user as $list)
                                        <option value="{{ $list->id }}">{{ $list->id_emp }} - {{ $list->name }}</option>
                                    @endforeach
                                @else
                                    <option value="">No employee available</option>
                                @endunless
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="hidden" name="content_id" value="{{ $content->id }}">
                            <input type="hidden" name="slug" value="{{ $content->slug }}">
                            <button type="submit" form="contentAssignForm" class="btn btn-primary w-100" name="action" value="assign">
                                Assign
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <hr class="my-4">
        @elseif ($mode === 'create' && $hasUserList)
            {{-- Top Part: Assignment Section for Create Mode --}}
            <div class="mb-4">
                <h4 class="form-label font-weight-bold mb-1">Assign Content ke Karyawan</h4>
                <p class="text-muted small mb-3">Pilih karyawan yang akan mendapatkan konten ini sebagai mandatory.</p>
                <div class="row g-3 align-items-end">
                    <div class="col-12">
                        <label class="form-label" for="select-user">Employee</label>
                        <select class="form-select" id="select-user" name="employee[]" form="contentCreateForm" multiple>
                            @unless (count($user) == 0)
                                @foreach ($user as $list)
                                    <option value="{{ $list->id }}">{{ $list->id_emp }} - {{ $list->name }}</option>
                                @endforeach
                            @else
                                <option value="">No employee available</option>
                            @endunless
                        </select>
                        <small class="form-hint">Optional. Selected employees will receive this content upon saving/publishing.</small>
                    </div>
                </div>
            </div>

            <hr class="my-4">
        @endif

        {{-- Bottom Part: Action Buttons --}}
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            @if ($mode === 'edit' && $content)
                <a href="{{ route('content.index') }}" class="btn btn-light me-auto">Back to List</a>

                <button type="submit" form="contentEditForm" class="btn btn-primary" name="action" value="update">
                    <i class="ti ti-device-floppy me-1"></i>
                    Save Content
                </button>

                @if ($content->active != 'Active')
                    {{-- <button type="submit" form="contentEditForm" class="btn btn-success" name="action" value="save" formnovalidate>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2z"></path>
                            <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                            <path d="M14 4l0 4l-6 0l0 -4"></path>
                        </svg>
                        Unpublish Content
                    </button> --}}
                    <button type="submit" form="contentEditForm" class="btn btn-primary" name="action" value="publish">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M7 11v8a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1v-7a1 1 0 0 1 1 -1h3a4 4 0 0 0 4 -4v-1a2 2 0 0 1 4 0v5h3a2 2 0 0 1 2 2l-1 5a2 3 0 0 1 -3 -3"></path>
                        </svg>
                        Save & Publish
                    </button>
                @endif
            @else
                <a href="{{ route('content.index') }}" class="btn btn-light me-auto">Cancel</a>
                <button type="submit" form="contentCreateForm" class="btn btn-success" name="action" value="save" formnovalidate>
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2z"></path>
                        <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                        <path d="M14 4l0 4l-6 0l0 -4"></path>
                    </svg>
                    Save Content (Draft)
                </button>
                <button type="submit" form="contentCreateForm" class="btn btn-primary" name="action" value="publish">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M7 11v8a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1v-7a1 1 0 0 1 1 -1h3a4 4 0 0 0 4 -4v-1a2 2 0 0 1 4 0v5h3a2 2 0 0 1 2 2l-1 5a2 3 0 0 1 -3 -3"></path>
                    </svg>
                    Save & Publish
                </button>
            @endif
        </div>
    </div>
</div>

