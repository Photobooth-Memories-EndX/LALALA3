{{-- Quick Filter Card --}}
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <!-- Label Header Quick Filter -->
        <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
            Quick Filter
        </div>

        <form action="{{ route('content.index') }}" method="GET" id="manageContentFilterForm">
            {{-- Preserve active More Filter parameters upon Quick Filter submission --}}
            @if(request()->filled('min_learning_hour'))
                <input type="hidden" name="min_learning_hour" value="{{ request('min_learning_hour') }}">
            @endif
            @if(request()->filled('max_learning_hour'))
                <input type="hidden" name="max_learning_hour" value="{{ request('max_learning_hour') }}">
            @endif
            @if(request()->filled('learning_hour'))
                <input type="hidden" name="learning_hour" value="{{ request('learning_hour') }}">
            @endif
            @if(request()->filled('duration'))
                <input type="hidden" name="duration" value="{{ request('duration') }}">
            @endif
            @if(request()->filled('author'))
                <input type="hidden" name="author" value="{{ request('author') }}">
            @endif
            @if(request()->filled('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            @if(request()->filled('view_order') || request()->filled('sort_views'))
                <input type="hidden" name="view_order" value="{{ request('view_order') ?? request('sort_views') }}">
            @endif
            @if(request()->filled('created_from_date') || request()->filled('created_from'))
                <input type="hidden" name="created_from_date" value="{{ request('created_from_date') ?? request('created_from') }}">
            @endif
            @if(request()->filled('created_to_date') || request()->filled('created_to'))
                <input type="hidden" name="created_to_date" value="{{ request('created_to_date') ?? request('created_to') }}">
            @endif
            @if(request()->filled('deadline_from_date') || request()->filled('period_from') || request()->filled('date_from'))
                <input type="hidden" name="deadline_from_date" value="{{ request('deadline_from_date') ?? (request('period_from') ?? request('date_from')) }}">
            @endif
            @if(request()->filled('deadline_to_date') || request()->filled('period_to') || request()->filled('date_to'))
                <input type="hidden" name="deadline_to_date" value="{{ request('deadline_to_date') ?? (request('period_to') ?? request('date_to')) }}">
            @endif
            @if(request()->filled('deadline_status') || request()->filled('deadline'))
                <input type="hidden" name="deadline_status" value="{{ request('deadline_status') ?? request('deadline') }}">
            @endif
            @if(request()->filled('exam'))
                <input type="hidden" name="exam" value="{{ request('exam') }}">
            @endif
            @if(request()->filled('playlistonly') || request()->filled('playlist'))
                <input type="hidden" name="playlistonly" value="{{ request('playlistonly') ?? request('playlist') }}">
            @endif

            <div class="row g-2 align-items-center">
                <!-- Filter Status -->
                <div class="col-6 col-md-auto">
                    <select name="active" class="form-select rounded-3">
                        <option value="">All Status</option>
                        <option value="Active" {{ (request('active') == 'Active' || request('status') == 'Active') ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ (request('active') == 'Inactive' || request('status') == 'Inactive') ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Filter Type -->
                <div class="col-6 col-md-auto">
                    <select name="type" class="form-select rounded-3">
                        <option value="">All Type</option>
                        @foreach ($types as $t)
                            <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ \App\Support\ContentTypeHelper::label($t) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Classification -->
                <div class="col-6 col-md-auto">
                    <select name="classification_id" class="form-select rounded-3">
                        <option value="">All Classification</option>
                        @foreach ($classifications as $c)
                            <option value="{{ $c->id }}" {{ (request('classification_id') == $c->id || request('classification') == $c->id) ? 'selected' : '' }}>{{ $c->classification_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Year & Month -->
                <div class="col-6 col-md-auto">
                    <select name="year" class="form-select rounded-3">
                        <option value="">All Year</option>
                        @foreach(range(date('Y'), 2020) as $y)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-auto">
                    <select name="month" class="form-select rounded-3">
                        <option value="">All Month</option>
                        @foreach(range(1, 12) as $m)
                            @php $mVal = str_pad((string)$m, 2, '0', STR_PAD_LEFT); @endphp
                            <option value="{{ $mVal }}" {{ (request('month') == $m || request('month') == $mVal) ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Input Search Bar -->
                <div class="col-12 col-md">
                    <div class="position-relative manage-search-container flex-grow-1">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="text" id="manage-content-search-input" name="q" value="{{ request('q') ?? (request('s') ?? request('search')) }}"
                                class="form-control" placeholder="Search content..." autocomplete="off">
                        </div>
                        
                        <div id="manage-content-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 p-0 overflow-hidden" 
                             style="display: none; z-index: 2000;">
                            <!-- Container 1: Scrollable List Area (Max 280px height with vertical scrollbar) -->
                            <div id="manage-content-autocomplete-list" class="list-group list-group-flush" 
                                 style="max-height: 280px; overflow-y: auto;"></div>
                            
                            <!-- Container 2: Fixed/Sticky Bottom Footer -->
                            <div id="manage-content-search-footer" class="border-top p-2 text-center" 
                                 style="display: none; position: sticky; bottom: 0; z-index: 2005;"></div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons (Search, More Filters & Reset) -->
                <div class="col-auto d-flex align-items-center gap-1 flex-shrink-0">
                    <!-- 1. Search Submit Button -->
                    <button type="submit" class="btn btn-primary btn-icon px-3" title="Search">
                        <i class="ti ti-search fs-2"></i>
                    </button>

                    <!-- 2. More Filters Modal Trigger Button (Icon Only - No Text) -->
                    <button type="button" class="btn btn-outline-primary btn-icon px-3 position-relative" data-bs-toggle="modal" data-bs-target="#moreContentFiltersModal" title="More Filters">
                        <i class="ti ti-adjustments-horizontal fs-2"></i>
                        @if(request('duration') || request('learning_hour') || request('min_learning_hour') || request('max_learning_hour') || request('has_exam') || request('date_from') || request('date_to') || request('author') || request('category'))
                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle">
                                <span class="visually-hidden">Active filter</span>
                            </span>
                        @endif
                    </button>

                    <!-- 3. Reset Button -->
                    <a href="{{ route('content.index') }}" class="btn btn-outline-secondary btn-icon px-3" title="Reset Filters">
                        <i class="ti ti-refresh fs-2"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
