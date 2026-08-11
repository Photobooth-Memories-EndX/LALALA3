@php
    $content = $content ?? null;
    $mode = $mode ?? ($content ? 'edit' : 'create');
    $user = $user ?? [];
    $stepNumber = $stepNumber ?? 5;
@endphp

@if ($mode === 'edit' && $content)
    <div class="card owl-main-card mb-3">
        <div class="card-header d-flex align-items-center">
            <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">{{ $stepNumber }}</span>
            <div>
                <h3 class="card-title mb-0">Assign Content ke Karyawan</h3>
                <div class="card-subtitle text-muted mt-1">Pilih karyawan yang akan mendapatkan konten ini sebagai mandatory.</div>
            </div>
        </div>
        <form id="contentAssignForm" action="{{ route('content.assign', $content->slug) }}" method="post" autocomplete="off">
            @csrf
            <div class="card-body">
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
            </div>
        </form>
    </div>
@endif
