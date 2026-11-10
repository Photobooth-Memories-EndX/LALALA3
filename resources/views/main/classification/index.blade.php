@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none mb-0 pb-0">
    <div class="container-xl">
        <!-- Back Navigation Link -->
        <div class="mb-3">
            <a href="{{ url('/manage') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 btn-back">
                <i class="ti ti-arrow-left fs-3"></i><span>Back to Manage</span>
            </a>
        </div>

        <!-- Classification History Hero Header Box -->
        <div class="card border-0 shadow-sm p-4 mb-3 rounded-4 bg-white option-hero-card">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <!-- Left Group: Icon Box + Title & Subtitle -->
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 d-flex align-items-center justify-content-center flex-shrink-0 hero-icon-box" 
                         style="width: 72px; height: 72px; background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); color: #003b6d; border: 1px solid #c7d2fe;">
                        <i class="ti ti-category-2" style="font-size: 2.2rem !important;"></i>
                    </div>

                    <!-- Title & Subtitle -->
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h2 class="h3 fw-bold mb-0 text-dark">Classification History</h2>
                            <span class="badge bg-primary-lt text-primary px-3 py-1 rounded-pill small fw-semibold">
                                <i class="ti ti-database me-1"></i>Master Training Data
                            </span>
                        </div>
                        <p class="text-muted small mb-0">
                            Training Option Management: Manage training classifications, categories, types, delivery methods, and training providers for Offline Training and OWL LMS.
                        </p>
                    </div>
                </div>

                <!-- Right Group: Primary Action Buttons -->
                <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                    @if ($activeType === 'classification')
                        <button type="button" 
                                class="btn btn-outline-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1 rounded-3"
                                data-bs-toggle="modal" 
                                data-bs-target="#addMainClassificationModal">
                            <i class="ti ti-plus fs-3"></i><span>Main Classification</span>
                        </button>
                        <button type="button" 
                                class="btn btn-outline-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1 rounded-3"
                                data-bs-toggle="modal" 
                                data-bs-target="#addSubClassificationModal">
                            <i class="ti ti-subtask fs-3"></i><span>Sub Classification</span>
                        </button>
                        <button type="button" 
                                class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm rounded-3" 
                                style="background-color: #003b6d; border-color: #003b6d;"
                                data-bs-toggle="modal" 
                                data-bs-target="#addCourseExampleModal">
                            <i class="ti ti-certificate fs-3"></i><span>Course Example</span>
                        </button>
                    @elseif ($activeType === 'delivery_method')
                        <button type="button" 
                                class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm rounded-3" 
                                style="background-color: #003b6d; border-color: #003b6d;"
                                data-bs-toggle="modal" 
                                data-bs-target="#addOptionModal">
                            <i class="ti ti-plus fs-2"></i><span>Add Delivery Method</span>
                        </button>
                    @elseif ($activeType === 'provider')
                        <button type="button" 
                                class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm rounded-3" 
                                style="background-color: #003b6d; border-color: #003b6d;"
                                data-bs-toggle="modal" 
                                data-bs-target="#addOptionModal">
                            <i class="ti ti-plus fs-2"></i><span>Add Provider</span>
                        </button>
                    @else
                        <button type="button" 
                                class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm rounded-3" 
                                style="background-color: #003b6d; border-color: #003b6d;"
                                data-bs-toggle="modal" 
                                data-bs-target="#addOptionModal">
                            <i class="ti ti-plus fs-2"></i><span>Add {{ $typeConfig['label'] }}</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Master Data Metrics Strip -->
            <div class="row g-2 mt-3 pt-3 border-top">
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-2 rounded-3 bg-light-subtle d-flex align-items-center gap-2 metric-box">
                        <span class="avatar avatar-sm bg-blue-lt text-primary rounded-circle"><i class="ti ti-category"></i></span>
                        <div>
                            <div class="fw-bold fs-4 text-dark mb-0 lh-1">{{ $summaryStats['total_classifications'] ?? 7 }}</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">Main Class</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-2 rounded-3 bg-light-subtle d-flex align-items-center gap-2 metric-box">
                        <span class="avatar avatar-sm bg-indigo-lt text-indigo rounded-circle"><i class="ti ti-subtask"></i></span>
                        <div>
                            <div class="fw-bold fs-4 text-dark mb-0 lh-1">{{ $summaryStats['total_sub_classifications'] ?? 22 }}</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">Sub Class</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-2 rounded-3 bg-light-subtle d-flex align-items-center gap-2 metric-box">
                        <span class="avatar avatar-sm bg-purple-lt text-purple rounded-circle"><i class="ti ti-certificate"></i></span>
                        <div>
                            <div class="fw-bold fs-4 text-dark mb-0 lh-1">{{ $summaryStats['total_course_examples'] ?? 68 }}</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">Courses</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-2 rounded-3 bg-light-subtle d-flex align-items-center gap-2 metric-box">
                        <span class="avatar avatar-sm bg-green-lt text-green rounded-circle"><i class="ti ti-presentation"></i></span>
                        <div>
                            <div class="fw-bold fs-4 text-dark mb-0 lh-1">{{ $summaryStats['total_delivery'] ?? 8 }}</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">Delivery Methods</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-2 rounded-3 bg-light-subtle d-flex align-items-center gap-2 metric-box">
                        <span class="avatar avatar-sm bg-orange-lt text-orange rounded-circle"><i class="ti ti-building"></i></span>
                        <div>
                            <div class="fw-bold fs-4 text-dark mb-0 lh-1">{{ $summaryStats['total_providers'] ?? 6 }}</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">Providers</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-2 rounded-3 bg-light-subtle d-flex align-items-center gap-2 metric-box">
                        <span class="avatar avatar-sm bg-teal-lt text-teal rounded-circle"><i class="ti ti-circle-check"></i></span>
                        <div>
                            <div class="fw-bold fs-4 text-success mb-0 lh-1">{{ $summaryStats['active_options'] ?? 0 }}</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">Active Options</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NAVIGATION / 5 MASTER TABS SELECTOR TOOLBAR -->
        <div class="card border-0 shadow-sm rounded-4 mb-3 type-selector-card">
            <div class="card-body p-2 p-md-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <!-- Left: 5 Main Master Tabs -->
                    <div class="d-flex flex-wrap align-items-center gap-1 gap-md-2">
                        @foreach ($primaryTypes as $pKey)
                            @php
                                $pConfig = $allTypes[$pKey];
                                $isActive = ($activeType === $pKey);
                            @endphp
                            <a href="{{ route('manage.classification.index', ['type' => $pKey]) }}" 
                               class="btn {{ $isActive ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary' }} btn-sm rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1 type-nav-pill {{ $isActive ? 'active-pill' : '' }}"
                               style="{{ $isActive ? 'background-color: #003b6d; border-color: #003b6d;' : '' }}">
                                <i class="ti {{ $pConfig['icon'] ?? 'ti-circle' }} fs-3"></i>
                                <span>{{ $pConfig['label'] }}</span>
                            </a>
                        @endforeach

                        <!-- Secondary Dropdown for Other Master Options -->
                        <div class="dropdown">
                            @php
                                $isSecondaryActive = array_key_exists($activeType, $secondaryTypes);
                            @endphp
                            <button class="btn {{ $isSecondaryActive ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary' }} btn-sm rounded-pill px-3 py-1 fw-semibold dropdown-toggle d-inline-flex align-items-center gap-1"
                                    type="button" 
                                    id="secondaryTypesDropdown" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false"
                                    style="{{ $isSecondaryActive ? 'background-color: #003b6d; border-color: #003b6d;' : '' }}">
                                <i class="ti ti-dots fs-3"></i>
                                <span>{{ $isSecondaryActive ? $typeConfig['label'] : 'More Master Options' }}</span>
                            </button>
                            <ul class="dropdown-menu shadow-lg py-1 border-0 rounded-3 mt-1" aria-labelledby="secondaryTypesDropdown" style="min-width: 240px; max-height: 360px; overflow-y: auto;">
                                <li class="dropdown-header text-uppercase small fw-bold text-muted px-3 py-2">
                                    Additional Master Options
                                </li>
                                @foreach ($secondaryTypes as $sKey => $sConfig)
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center justify-content-between px-3 py-2 {{ $activeType === $sKey ? 'active fw-bold' : '' }}" 
                                           href="{{ route('manage.classification.index', ['type' => $sKey]) }}">
                                            <span class="d-inline-flex align-items-center gap-2">
                                                <i class="ti {{ $sConfig['icon'] ?? 'ti-circle' }}"></i>
                                                {{ $sConfig['label'] }}
                                            </span>
                                            @if ($activeType === $sKey)
                                                <i class="ti ti-check text-primary"></i>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <!-- Right: Tab summary counter -->
                    <div class="text-muted small d-none d-lg-flex align-items-center gap-3">
                        <span>
                            Currently viewing: <strong class="text-dark">{{ $typeConfig['label'] }}</strong>
                        </span>
                        @if ($activeType === 'classification')
                            <span class="badge bg-light text-muted border px-2 py-1">
                                {{ count($items) }} Main Categories
                            </span>
                        @else
                            <span class="badge bg-light text-muted border px-2 py-1">
                                {{ count($items) }} Records
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body mt-0 pt-0">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />
        <x-alert type="error" />

        {{-- SEARCH & FILTER TOOLBAR --}}
        <div class="card border-0 shadow-sm rounded-4 mb-3 quick-filter-card">
            <div class="card-body p-3">
                <form action="{{ route('manage.classification.index') }}" method="GET" id="manageOptionFilterForm">
                    <input type="hidden" name="type" value="{{ $activeType }}">
                    
                    <div class="row g-2 align-items-center">
                        <!-- Entries Select -->
                        <div class="col-6 col-sm-4 col-md-2 col-lg-auto" style="min-width: 120px;">
                            <select id="option-entries-select" name="entries" class="form-select rounded-3">
                                <option value="10" {{ request('entries') == 10 ? 'selected' : '' }}>10 Entries</option>
                                <option value="25" {{ (request('entries') == 25 || !request()->has('entries')) ? 'selected' : '' }}>25 Entries</option>
                                <option value="50" {{ request('entries') == 50 ? 'selected' : '' }}>50 Entries</option>
                                <option value="100" {{ request('entries') == 100 ? 'selected' : '' }}>100 Entries</option>
                            </select>
                        </div>

                        <!-- Main Classification Filter (when on classification) -->
                        @if ($activeType === 'classification')
                            <div class="col-6 col-sm-4 col-md-3 col-lg-auto" style="min-width: 200px;">
                                <select name="main_id" class="form-select rounded-3" id="filter-main-select" onchange="this.form.submit()">
                                    <option value="">All Main Classifications</option>
                                    @foreach ($classificationsList ?? [] as $mCls)
                                        <option value="{{ $mCls->id }}" {{ request('main_id') == $mCls->id ? 'selected' : '' }}>
                                            {{ $mCls->classification_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6 col-sm-4 col-md-3 col-lg-auto" style="min-width: 190px;">
                                <select name="sub_id" class="form-select rounded-3" id="filter-sub-select" onchange="this.form.submit()">
                                    <option value="">All Sub Classifications</option>
                                    @foreach ($subClassificationsList ?? [] as $sCls)
                                        <option value="{{ $sCls->id }}" {{ request('sub_id') == $sCls->id ? 'selected' : '' }}>
                                            {{ $sCls->value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Source Filter (All / OWL / Non KP-OWL) -->
                        <div class="col-6 col-sm-4 col-md-2 col-lg-auto" style="min-width: 150px;">
                            <select name="source" class="form-select rounded-3" id="filter-source-select" onchange="this.form.submit()">
                                <option value="" {{ request('source') === null || request('source') === '' ? 'selected' : '' }}>All Sources</option>
                                <option value="owl" {{ request('source') === 'owl' ? 'selected' : '' }}>OWL Only</option>
                                <option value="non_kp_owl" {{ request('source') === 'non_kp_owl' ? 'selected' : '' }}>Non KP-OWL Only</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-6 col-sm-4 col-md-2 col-lg-auto" style="min-width: 130px;">
                            <select name="status" class="form-select rounded-3" id="filter-status-select" onchange="this.form.submit()">
                                <option value="" {{ request('status') === null || request('status') === '' ? 'selected' : '' }}>All Status</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active Only</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive Only</option>
                            </select>
                        </div>

                        <!-- Year Filter (for KPI) -->
                        @if (!empty($typeConfig['is_year_scoped']) && !empty($kpiYears))
                            <div class="col-6 col-sm-4 col-md-2 col-lg-auto" style="min-width: 130px;">
                                <select name="year" class="form-select rounded-3" id="option-year-filter" onchange="this.form.submit()">
                                    <option value="">All KPI Years</option>
                                    @foreach ($kpiYears as $yr)
                                        <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Parent Filter (for generic hierarchical types) -->
                        @if (!empty($typeConfig['is_hierarchical']) && $activeType !== 'classification' && $parentOptions->isNotEmpty())
                            <div class="col-6 col-sm-4 col-md-3 col-lg-auto" style="min-width: 170px;">
                                <select name="parent_id" class="form-select rounded-3" id="option-parent-filter" onchange="this.form.submit()">
                                    <option value="">All {{ $allTypes[$typeConfig['parent_type']]['label'] ?? 'Parents' }}</option>
                                    @foreach ($parentOptions as $parent)
                                        <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Search Input -->
                        <div class="col-12 col-md-4 col-lg flex-grow-1">
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="text" 
                                       name="q" 
                                       id="manage-option-search-input" 
                                       class="form-control rounded-3" 
                                       placeholder="Search {{ strtolower($typeConfig['label']) }}..." 
                                       value="{{ request('q') ?? (request('search') ?? request('s')) }}" 
                                       autocomplete="off">
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-auto d-flex align-items-center gap-1">
                            <button type="submit" class="btn btn-primary btn-icon px-3 rounded-3" title="Search">
                                <i class="ti ti-search fs-2"></i>
                            </button>
                            <a href="{{ route('manage.classification.index', ['type' => $activeType]) }}" class="btn btn-outline-secondary btn-icon px-3 rounded-3" title="Reset Filters">
                                <i class="ti ti-refresh fs-2"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- VIEW A: TRAINING CLASSIFICATION (3-TIER HIERARCHY TREE VIEW)             --}}
        {{-- ========================================================================= --}}
        @if ($activeType === 'classification')
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white rounded-pill px-3 py-1 fw-semibold">
                        List Classification &bull; Classification Name &rarr; Sub Classification &rarr; Course Examples
                    </span>
                    <span class="text-muted small">({{ count($items) }} Main Classifications)</span>
                </div>
                <div class="btn-list">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" id="btnToggleAllAccordions" data-state="expanded">
                        <i class="ti ti-fold me-1"></i>Collapse All
                    </button>
                </div>
            </div>

            @if (count($items) > 0)
                <div class="accordion classification-tree-accordion d-flex flex-column gap-3" id="classificationAccordion">
                    @foreach ($items as $main)
                        @php
                            $collapseId = 'collapseMain_' . $main->id;
                            $subs = $main->sub_classifications ?? collect();
                            $courseCount = $main->total_course_count ?? 0;
                            $subCount = $main->total_sub_count ?? 0;
                            $initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $main->classification_name), 0, 2)) ?: 'CL';
                        @endphp
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden main-classification-card">
                            <!-- Main Classification Card Header -->
                            <div class="card-header bg-white py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2 border-0">
                                <div class="d-flex align-items-center gap-3 cursor-pointer flex-grow-1 accordion-header-clickable" 
                                     data-bs-toggle="collapse" 
                                     data-bs-target="#{{ $collapseId }}" 
                                     aria-expanded="true" 
                                     aria-controls="{{ $collapseId }}">
                                    <button class="btn btn-sm btn-icon btn-light rounded-circle accordion-chevron-btn" type="button">
                                        <i class="ti ti-chevron-down fs-2"></i>
                                    </button>
                                    <span class="avatar avatar-md rounded-3 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #003b6d 0%, #1d4ed8 100%);">
                                        {{ $initials }}
                                    </span>
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h3 class="card-title fw-bold text-dark mb-0 fs-3">{{ $main->classification_name }}</h3>
                                            @if ($main->is_active)
                                                <span class="badge bg-success-lt text-success rounded-pill px-2 py-0 small"><i class="ti ti-check me-1"></i>Active</span>
                                            @else
                                                <span class="badge bg-secondary-lt text-secondary rounded-pill px-2 py-0 small"><i class="ti ti-ban me-1"></i>Inactive</span>
                                            @endif
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-1 text-muted small">
                                            <span class="badge bg-blue-lt text-primary px-2 py-1 rounded-pill">
                                                <i class="ti ti-subtask me-1"></i>{{ $subCount }} Sub Classification(s)
                                            </span>
                                            <span class="badge bg-purple-lt text-purple px-2 py-1 rounded-pill">
                                                <i class="ti ti-certificate me-1"></i>{{ $courseCount }} Course Example(s)
                                            </span>
                                            @if (($main->total_usage ?? 0) > 0)
                                                <span class="badge bg-green-lt text-green px-2 py-1 rounded-pill" title="{{ $main->content_count }} learning contents, {{ $main->offline_count }} offline training records">
                                                    <i class="ti ti-link me-1"></i>{{ $main->total_usage }} Linked Records
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions on Main Classification -->
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-add-sub-to-main"
                                            data-main-id="{{ $main->id }}"
                                            data-main-name="{{ $main->classification_name }}"
                                            title="Add Sub Classification under {{ $main->classification_name }}">
                                        <i class="ti ti-plus me-1"></i>Sub Classification
                                    </button>

                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-edit-classification"
                                            data-id="{{ $main->id }}"
                                            data-name="{{ $main->classification_name }}"
                                            data-active="{{ $main->is_active ? '1' : '0' }}"
                                            title="Edit Main Classification">
                                        <i class="ti ti-edit me-1"></i>Edit
                                    </button>

                                    <button type="button" 
                                            class="btn btn-sm btn-icon btn-outline-danger rounded-circle btn-delete-classification"
                                            data-id="{{ $main->id }}"
                                            data-name="{{ $main->classification_name }}"
                                            data-usage="{{ $main->total_usage ?? 0 }}"
                                            title="Delete Main Classification">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Collapsible Card Body (Sub Classification Table) -->
                            <div id="{{ $collapseId }}" class="collapse show" data-bs-parent="#classificationAccordion">
                                <div class="card-body p-0 border-top">
                                    @if ($subs->count() > 0)
                                        <div class="table-responsive mb-0">
                                            <table class="table table-vcenter card-table table-hover mb-0 option-table">
                                                <thead class="bg-light-subtle">
                                                    <tr>
                                                        <th width="50" class="text-center text-muted">#</th>
                                                        <th width="220">Sub Classification</th>
                                                        <th>Course Examples</th>
                                                        <th width="120" class="text-center">Status</th>
                                                        <th width="160" class="text-end pe-4">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($subs as $sub)
                                                        @php
                                                            $courses = $sub->course_examples ?? collect();
                                                        @endphp
                                                        <tr>
                                                            <td class="text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                                                            <td>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <div class="fw-bold text-dark fs-4">{{ $sub->value }}</div>
                                                                    @if ($sub->source === 'owl')
                                                                        <span class="badge bg-indigo text-white px-2 py-0 rounded-pill small" title="OWL Source">OWL</span>
                                                                    @elseif ($sub->source === 'non_kp_owl')
                                                                        <span class="badge bg-emerald text-white px-2 py-0 rounded-pill small" style="background-color: #059669 !important;" title="Non KP-OWL Source">Non KP-OWL</span>
                                                                    @endif
                                                                </div>
                                                                @if (!empty($sub->explanation))
                                                                    <div class="text-muted small mt-1" style="max-width: 280px; font-size: 0.8rem;">
                                                                        {{ $sub->explanation }}
                                                                    </div>
                                                                @endif
                                                                @if ($sub->offline_count > 0)
                                                                    <span class="badge bg-light text-muted border px-2 py-0 rounded-pill mt-1" style="font-size: 0.75rem;">
                                                                        <i class="ti ti-check me-1"></i>{{ $sub->offline_count }} offline record(s)
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <div class="d-flex flex-wrap align-items-center gap-1 py-1 course-pill-cloud">
                                                                    @forelse ($courses as $course)
                                                                        <span class="badge bg-white text-dark border shadow-xs px-2 py-1 rounded-pill d-inline-flex align-items-center gap-1 course-example-pill" 
                                                                              title="Course Example: {{ $course->value }}">
                                                                            <i class="ti ti-certificate text-purple fs-3"></i>
                                                                            <span>{{ $course->value }}</span>
                                                                            <button type="button" 
                                                                                    class="btn-close ms-1 btn-edit-course-trigger" 
                                                                                    data-id="{{ $course->id }}"
                                                                                    data-value="{{ $course->value }}"
                                                                                    data-parent-id="{{ $sub->id }}"
                                                                                    data-parent-name="{{ $sub->value }}"
                                                                                    data-main-id="{{ $main->id }}"
                                                                                    style="font-size: 0.55rem;"
                                                                                    title="Edit this Course Example">
                                                                            </button>
                                                                        </span>
                                                                    @empty
                                                                        <span class="text-muted small fst-italic">
                                                                            No course examples defined yet.
                                                                        </span>
                                                                    @endforelse

                                                                    <button type="button" 
                                                                            class="btn btn-outline-primary btn-sm rounded-pill py-0 px-2 btn-add-course-inline" 
                                                                            data-sub-id="{{ $sub->id }}"
                                                                            data-sub-name="{{ $sub->value }}"
                                                                            data-main-id="{{ $main->id }}"
                                                                            data-main-name="{{ $main->classification_name }}"
                                                                            style="font-size: 0.75rem;" 
                                                                            title="Add Course Example to {{ $sub->value }}">
                                                                        <i class="ti ti-plus me-1"></i>Add Course
                                                                    </button>
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                @if ($sub->is_active)
                                                                    <span class="badge bg-success-lt text-success px-2 py-1 rounded-pill fw-semibold">
                                                                        <i class="ti ti-check me-1"></i>Active
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-secondary-lt text-secondary px-2 py-1 rounded-pill fw-semibold">
                                                                        <i class="ti ti-ban me-1"></i>Inactive
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="text-end pe-4">
                                                                <div class="btn-list justify-content-end">
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-icon {{ $sub->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-circle btn-toggle-status"
                                                                            data-id="{{ $sub->id }}"
                                                                            title="{{ $sub->is_active ? 'Deactivate Sub Classification' : 'Activate Sub Classification' }}">
                                                                        <i class="ti {{ $sub->is_active ? 'ti-eye-off' : 'ti-eye' }}"></i>
                                                                    </button>

                                                                    <button type="button" 
                                                                            class="btn btn-outline-primary btn-sm rounded-pill btn-edit-sub-classification"
                                                                            data-id="{{ $sub->id }}"
                                                                            data-value="{{ $sub->value }}"
                                                                            data-explanation="{{ $sub->explanation }}"
                                                                            data-source="{{ $sub->source }}"
                                                                            data-main-id="{{ $main->id }}"
                                                                            data-order="{{ $sub->sort_order }}"
                                                                            data-active="{{ $sub->is_active ? '1' : '0' }}"
                                                                            title="Edit Sub Classification">
                                                                        <i class="ti ti-edit me-1"></i>Edit
                                                                    </button>

                                                                    <button type="button" 
                                                                            class="btn btn-outline-danger btn-sm rounded-pill btn-delete-option"
                                                                            data-id="{{ $sub->id }}"
                                                                            data-value="{{ $sub->value }}"
                                                                            title="Delete Sub Classification">
                                                                        <i class="ti ti-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-4 px-3 bg-light-subtle">
                                            <div class="text-muted small mb-2">No Sub Classifications found for {{ $main->classification_name }}.</div>
                                            <button type="button" 
                                                    class="btn btn-primary btn-sm rounded-pill px-3 btn-add-sub-to-main"
                                                    data-main-id="{{ $main->id }}"
                                                    data-main-name="{{ $main->classification_name }}"
                                                    style="background-color: #003b6d; border-color: #003b6d;">
                                                <i class="ti ti-plus me-1"></i>Add First Sub Classification
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- EMPTY STATE CLASSIFICATION -->
                <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
                    <div class="avatar avatar-xl bg-light-subtle text-muted rounded-circle mx-auto mb-3" style="width: 72px; height: 72px;">
                        <i class="ti ti-category fs-1"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-1">No Classifications Found</h3>
                    <p class="text-muted small mb-4">No classification records match your filter criteria or search keyword.</p>
                    <div>
                        <a href="{{ route('manage.classification.index', ['type' => 'classification']) }}" class="btn btn-outline-secondary rounded-pill px-4 me-2">
                            Reset Filters
                        </a>
                        <button type="button" class="btn btn-primary rounded-pill px-4" style="background-color: #003b6d; border-color: #003b6d;" data-bs-toggle="modal" data-bs-target="#addMainClassificationModal">
                            <i class="ti ti-plus me-1"></i>Add Main Classification
                        </button>
                    </div>
                </div>
            @endif

        {{-- ========================================================================= --}}
        {{-- VIEW B: DELIVERY METHOD (Hierarchical Delivery -> Sub Delivery)           --}}
        {{-- ========================================================================= --}}
        @elseif ($activeType === 'delivery_method')
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header border-0 bg-transparent py-3 px-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="card-title mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="ti ti-presentation text-primary"></i>
                            List Delivery Method & Sub Delivery Methods
                        </h3>
                        <div class="text-muted small">Standardized offline, online, and blended training modes for OWL & Offline Training.</div>
                    </div>
                    <span class="badge bg-light text-muted border px-2 py-1">
                        {{ count($items) }} Method Groups
                    </span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover option-table w-100 mb-0">
                            <thead class="bg-light-subtle">
                                <tr>
                                    <th width="50" class="text-center">#</th>
                                    <th width="180">Delivery Method</th>
                                    <th width="200">Sub Delivery Method</th>
                                    <th>Explanation</th>
                                    <th width="120" class="text-center">Source</th>
                                    <th width="100" class="text-center">Status</th>
                                    <th width="140" class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $parentMethod)
                                    @php
                                        $subMethods = $parentMethod->children ?? collect();
                                        $subCount = $subMethods->count();
                                    @endphp
                                    @if ($subCount > 0)
                                        @foreach ($subMethods as $sub)
                                            <tr>
                                                @if ($loop->first)
                                                    <td rowspan="{{ $subCount }}" class="text-center text-muted fw-semibold align-top pt-3">
                                                        {{ $loop->parent->iteration }}
                                                    </td>
                                                    <td rowspan="{{ $subCount }}" class="align-top pt-3 border-end">
                                                        <div class="fw-bold text-dark fs-3">{{ $parentMethod->value }}</div>
                                                        <div class="d-flex align-items-center gap-1 mt-1">
                                                            @if ($parentMethod->source === 'owl')
                                                                <span class="badge bg-indigo text-white px-2 py-0 rounded-pill small">OWL</span>
                                                            @elseif ($parentMethod->source === 'non_kp_owl')
                                                                <span class="badge bg-emerald text-white px-2 py-0 rounded-pill small" style="background-color: #059669 !important;">Non KP-OWL</span>
                                                            @else
                                                                <span class="badge bg-blue-lt text-primary px-2 py-0 rounded-pill small">Shared</span>
                                                            @endif
                                                        </div>
                                                        <div class="mt-2">
                                                            <button type="button" 
                                                                    class="btn btn-outline-primary btn-sm rounded-pill px-2 py-0 btn-add-sub-delivery-trigger" 
                                                                    data-parent-id="{{ $parentMethod->id }}"
                                                                    data-parent-name="{{ $parentMethod->value }}"
                                                                    style="font-size: 0.75rem;">
                                                                <i class="ti ti-plus me-1"></i>Sub Method
                                                            </button>
                                                        </div>
                                                    </td>
                                                @endif
                                                <td>
                                                    <span class="fw-semibold text-dark">{{ $sub->value }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted small">{{ $sub->explanation ?: '-' }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if ($sub->source === 'owl')
                                                        <span class="badge bg-indigo text-white px-2 py-1 rounded-pill small fw-semibold">OWL</span>
                                                    @elseif ($sub->source === 'non_kp_owl')
                                                        <span class="badge bg-emerald text-white px-2 py-1 rounded-pill small fw-semibold" style="background-color: #059669 !important;">Non KP-OWL</span>
                                                    @else
                                                        <span class="badge bg-blue-lt text-primary px-2 py-1 rounded-pill small fw-semibold">Shared</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($sub->is_active)
                                                        <span class="badge bg-success-lt text-success px-2 py-1 rounded-pill fw-semibold">Active</span>
                                                    @else
                                                        <span class="badge bg-secondary-lt text-secondary px-2 py-1 rounded-pill fw-semibold">Inactive</span>
                                                    @endif
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="btn-list justify-content-end">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-icon {{ $sub->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-circle btn-toggle-status"
                                                                data-id="{{ $sub->id }}"
                                                                title="{{ $sub->is_active ? 'Deactivate' : 'Activate' }}">
                                                            <i class="ti {{ $sub->is_active ? 'ti-eye-off' : 'ti-eye' }}"></i>
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-outline-primary btn-sm rounded-pill btn-edit-generic-option"
                                                                data-id="{{ $sub->id }}"
                                                                title="Edit Sub Delivery Method">
                                                            <i class="ti ti-edit"></i>
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-outline-danger btn-sm rounded-pill btn-delete-option"
                                                                data-id="{{ $sub->id }}"
                                                                data-value="{{ $sub->value }}"
                                                                title="Delete Sub Delivery Method">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="fw-bold text-dark fs-3">{{ $parentMethod->value }}</div>
                                            </td>
                                            <td colspan="2" class="text-muted fst-italic small">No sub delivery methods defined.</td>
                                            <td class="text-center">
                                                @if ($parentMethod->source === 'owl')
                                                    <span class="badge bg-indigo text-white px-2 py-1 rounded-pill small">OWL</span>
                                                @elseif ($parentMethod->source === 'non_kp_owl')
                                                    <span class="badge bg-emerald text-white px-2 py-1 rounded-pill small" style="background-color: #059669 !important;">Non KP-OWL</span>
                                                @else
                                                    <span class="badge bg-blue-lt text-primary px-2 py-1 rounded-pill small">Shared</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($parentMethod->is_active)
                                                    <span class="badge bg-success-lt text-success px-2 py-1 rounded-pill">Active</span>
                                                @else
                                                    <span class="badge bg-secondary-lt text-secondary px-2 py-1 rounded-pill">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4">
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-sm rounded-pill btn-add-sub-delivery-trigger" 
                                                        data-parent-id="{{ $parentMethod->id }}"
                                                        data-parent-name="{{ $parentMethod->value }}">
                                                    <i class="ti ti-plus me-1"></i>Add Sub Method
                                                </button>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No delivery methods found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        {{-- ========================================================================= --}}
        {{-- VIEW C: TRAINING PROVIDER (Hierarchical Provider -> Sub Provider)         --}}
        {{-- ========================================================================= --}}
        @elseif ($activeType === 'provider')
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header border-0 bg-transparent py-3 px-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="card-title mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="ti ti-building text-primary"></i>
                            Training Providers & Sub Providers
                        </h3>
                        <div class="text-muted small">Internal and external training providers for OWL & Offline Training.</div>
                    </div>
                    <span class="badge bg-light text-muted border px-2 py-1">
                        {{ count($items) }} Provider Groups
                    </span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover option-table w-100 mb-0">
                            <thead class="bg-light-subtle">
                                <tr>
                                    <th width="50" class="text-center">#</th>
                                    <th width="180">Provider</th>
                                    <th width="220">Sub Provider</th>
                                    <th>Explanation</th>
                                    <th width="120" class="text-center">Source</th>
                                    <th width="100" class="text-center">Status</th>
                                    <th width="140" class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $parentProv)
                                    @php
                                        $subProvs = $parentProv->children ?? collect();
                                        $subCount = $subProvs->count();
                                    @endphp
                                    @if ($subCount > 0)
                                        @foreach ($subProvs as $sub)
                                            <tr>
                                                @if ($loop->first)
                                                    <td rowspan="{{ $subCount }}" class="text-center text-muted fw-semibold align-top pt-3">
                                                        {{ $loop->parent->iteration }}
                                                    </td>
                                                    <td rowspan="{{ $subCount }}" class="align-top pt-3 border-end">
                                                        <div class="fw-bold text-dark fs-3">{{ $parentProv->value }}</div>
                                                        <div class="d-flex align-items-center gap-1 mt-1">
                                                            @if ($parentProv->source === 'owl')
                                                                <span class="badge bg-indigo text-white px-2 py-0 rounded-pill small">OWL</span>
                                                            @elseif ($parentProv->source === 'non_kp_owl')
                                                                <span class="badge bg-emerald text-white px-2 py-0 rounded-pill small" style="background-color: #059669 !important;">Non KP-OWL</span>
                                                            @else
                                                                <span class="badge bg-blue-lt text-primary px-2 py-0 rounded-pill small">Shared</span>
                                                            @endif
                                                        </div>
                                                        <div class="mt-2">
                                                            <button type="button" 
                                                                    class="btn btn-outline-primary btn-sm rounded-pill px-2 py-0 btn-add-sub-provider-trigger" 
                                                                    data-parent-id="{{ $parentProv->id }}"
                                                                    data-parent-name="{{ $parentProv->value }}"
                                                                    style="font-size: 0.75rem;">
                                                                <i class="ti ti-plus me-1"></i>Sub Provider
                                                            </button>
                                                        </div>
                                                    </td>
                                                @endif
                                                <td>
                                                    <span class="fw-semibold text-dark">{{ $sub->value }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted small">{{ $sub->explanation ?: '-' }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if ($sub->source === 'owl')
                                                        <span class="badge bg-indigo text-white px-2 py-1 rounded-pill small fw-semibold">OWL</span>
                                                    @elseif ($sub->source === 'non_kp_owl')
                                                        <span class="badge bg-emerald text-white px-2 py-1 rounded-pill small fw-semibold" style="background-color: #059669 !important;">Non KP-OWL</span>
                                                    @else
                                                        <span class="badge bg-blue-lt text-primary px-2 py-1 rounded-pill small fw-semibold">Shared</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($sub->is_active)
                                                        <span class="badge bg-success-lt text-success px-2 py-1 rounded-pill fw-semibold">Active</span>
                                                    @else
                                                        <span class="badge bg-secondary-lt text-secondary px-2 py-1 rounded-pill fw-semibold">Inactive</span>
                                                    @endif
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="btn-list justify-content-end">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-icon {{ $sub->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-circle btn-toggle-status"
                                                                data-id="{{ $sub->id }}"
                                                                title="{{ $sub->is_active ? 'Deactivate' : 'Activate' }}">
                                                            <i class="ti {{ $sub->is_active ? 'ti-eye-off' : 'ti-eye' }}"></i>
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-outline-primary btn-sm rounded-pill btn-edit-generic-option"
                                                                data-id="{{ $sub->id }}"
                                                                title="Edit Sub Provider">
                                                            <i class="ti ti-edit"></i>
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-outline-danger btn-sm rounded-pill btn-delete-option"
                                                                data-id="{{ $sub->id }}"
                                                                data-value="{{ $sub->value }}"
                                                                title="Delete Sub Provider">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="fw-bold text-dark fs-3">{{ $parentProv->value }}</div>
                                            </td>
                                            <td colspan="2" class="text-muted fst-italic small">No sub providers defined.</td>
                                            <td class="text-center">
                                                @if ($parentProv->source === 'owl')
                                                    <span class="badge bg-indigo text-white px-2 py-1 rounded-pill small">OWL</span>
                                                @elseif ($parentProv->source === 'non_kp_owl')
                                                    <span class="badge bg-emerald text-white px-2 py-1 rounded-pill small" style="background-color: #059669 !important;">Non KP-OWL</span>
                                                @else
                                                    <span class="badge bg-blue-lt text-primary px-2 py-1 rounded-pill small">Shared</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($parentProv->is_active)
                                                    <span class="badge bg-success-lt text-success px-2 py-1 rounded-pill">Active</span>
                                                @else
                                                    <span class="badge bg-secondary-lt text-secondary px-2 py-1 rounded-pill">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4">
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-sm rounded-pill btn-add-sub-provider-trigger" 
                                                        data-parent-id="{{ $parentProv->id }}"
                                                        data-parent-name="{{ $parentProv->value }}">
                                                    <i class="ti ti-plus me-1"></i>Add Sub Provider
                                                </button>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No training providers found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        {{-- ========================================================================= --}}
        {{-- VIEW D: OTHER MASTER OPTIONS (Type, Category, Level, etc.)               --}}
        {{-- ========================================================================= --}}
        @else
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header border-0 bg-transparent py-3 px-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="card-title mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="ti {{ $typeConfig['icon'] }} text-primary"></i>
                            List {{ $typeConfig['label'] }}
                            @if (str_starts_with($typeConfig['label'], 'Training '))
                                <span class="visually-hidden">List {{ str_replace('Training ', '', $typeConfig['label']) }}</span>
                            @endif
                        </h3>
                        <div class="text-muted small">{{ $typeConfig['description'] }}</div>
                    </div>
                    <span class="badge bg-light text-muted border px-2 py-1">
                        {{ count($items) }} row(s) displayed
                    </span>
                </div>

                <div class="card-body p-0">
                    <div id="table-default" class="table-responsive">
                        @if (count($items) > 0)
                            <table class="table table-vcenter card-table table-hover option-table w-100 mb-0" id="MyTable">
                                <thead class="bg-light-subtle">
                                    <tr>
                                        <th width="60" class="text-center">No</th>
                                        <th>Option Value</th>
                                        @if (!empty($typeConfig['is_hierarchical']))
                                            <th>Parent ({{ $allTypes[$typeConfig['parent_type']]['label'] ?? 'Parent' }})</th>
                                        @endif
                                        <th width="140" class="text-center">Source</th>
                                        @if (!empty($typeConfig['is_year_scoped']))
                                            <th class="text-center" width="100">Year</th>
                                        @endif
                                        <th class="text-center" width="110">Status</th>
                                        <th class="text-center" width="90">Order</th>
                                        <th width="160" class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td class="text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="avatar avatar-sm bg-primary-lt text-primary rounded-circle fw-bold">
                                                        {{ strtoupper(substr($item->value ?? 'O', 0, 1)) }}
                                                    </span>
                                                    <div>
                                                        <div class="fw-semibold text-dark item-value-text">{{ $item->value }}</div>
                                                        @if (!empty($item->explanation))
                                                            <div class="text-muted small" style="font-size: 0.8rem;">{{ $item->explanation }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            @if (!empty($typeConfig['is_hierarchical']))
                                                <td>
                                                    @if ($item->parent)
                                                        <span class="badge bg-indigo-lt text-indigo px-2 py-1 rounded-pill">
                                                            <i class="ti ti-corner-down-right me-1"></i>{{ $item->parent->value }}
                                                        </span>
                                                    @elseif ($item->classification)
                                                        <span class="badge bg-blue-lt text-primary px-2 py-1 rounded-pill">
                                                            <i class="ti ti-corner-down-right me-1"></i>{{ $item->classification->classification_name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                            @endif

                                            <td class="text-center">
                                                @if ($item->source === 'owl')
                                                    <span class="badge bg-indigo text-white px-2 py-1 rounded-pill small fw-semibold">OWL</span>
                                                @elseif ($item->source === 'non_kp_owl')
                                                    <span class="badge bg-emerald text-white px-2 py-1 rounded-pill small fw-semibold" style="background-color: #059669 !important;">Non KP-OWL</span>
                                                @else
                                                    <span class="badge bg-blue-lt text-primary px-2 py-1 rounded-pill small fw-semibold">Shared</span>
                                                @endif
                                            </td>

                                            @if (!empty($typeConfig['is_year_scoped']))
                                                <td class="text-center">
                                                    <span class="badge bg-purple-lt text-purple fw-bold px-2 py-1 rounded-pill">
                                                        {{ $item->year ?: 'Global' }}
                                                    </span>
                                                </td>
                                            @endif

                                            <td class="text-center">
                                                @if ($item->is_active)
                                                    <span class="badge bg-success-lt text-success px-2 py-1 rounded-pill fw-semibold">Active</span>
                                                @else
                                                    <span class="badge bg-secondary-lt text-secondary px-2 py-1 rounded-pill fw-semibold">Inactive</span>
                                                @endif
                                            </td>

                                            <td class="text-center text-muted font-monospace small">{{ $item->sort_order }}</td>

                                            <td class="text-end pe-4">
                                                <div class="btn-list justify-content-end">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-icon {{ $item->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-circle btn-toggle-status"
                                                            data-id="{{ $item->id }}"
                                                            title="{{ $item->is_active ? 'Deactivate Option' : 'Activate Option' }}">
                                                        <i class="ti {{ $item->is_active ? 'ti-eye-off' : 'ti-eye' }}"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-outline-primary btn-sm rounded-pill btn-edit-generic-option"
                                                            data-id="{{ $item->id }}"
                                                            title="Edit Option">
                                                        <i class="ti ti-edit me-1"></i>Edit
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm rounded-pill btn-delete-option"
                                                            data-id="{{ $item->id }}"
                                                            data-value="{{ $item->value }}"
                                                            title="Delete Option">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-5 px-3">
                                <div class="avatar avatar-xl bg-light-subtle text-muted rounded-circle mx-auto mb-3" style="width: 72px; height: 72px;">
                                    <i class="ti {{ $typeConfig['icon'] }} fs-1"></i>
                                </div>
                                <h3 class="fw-bold text-dark mb-1">No {{ $typeConfig['label'] }} Options Found</h3>
                                <p class="text-muted small mb-4">No master options match your filter criteria or search keyword.</p>
                                <button type="button" class="btn btn-primary rounded-pill px-4" style="background-color: #003b6d; border-color: #003b6d;" data-bs-toggle="modal" data-bs-target="#addOptionModal">
                                    <i class="ti ti-plus me-1"></i>Add First {{ $typeConfig['label'] }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODALS SECTION                                                            --}}
{{-- ========================================================================= --}}

<!-- MODAL 1: ADD MAIN CLASSIFICATION -->
<div class="modal modal-blur fade" id="addMainClassificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="{{ route('manage.classification.options.store') }}" method="POST" id="formAddMainClassification">
                @csrf
                <input type="hidden" name="option_type" value="classification">

                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-primary-lt text-primary rounded-circle"><i class="ti ti-category"></i></span>
                        <h5 class="modal-title fw-bold">Add Main Classification</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="alert alert-danger d-none py-2 px-3 small rounded-3 mb-3 modal-error-alert"></div>

                    <div class="mb-3">
                        <label class="form-label required small fw-semibold">Main Classification Name</label>
                        <input type="text" name="classification_name" class="form-control rounded-3" placeholder="e.g. Basic & Common Duties" required autofocus>
                    </div>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="addMainIsActive">
                        <label class="form-check-label small fw-semibold" for="addMainIsActive">Active (Available for dropdowns)</label>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light-subtle rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check me-1"></i>Save Main Classification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: ADD SUB CLASSIFICATION -->
<div class="modal modal-blur fade" id="addSubClassificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="{{ route('manage.classification.options.store') }}" method="POST" id="formAddSubClassification">
                @csrf
                <input type="hidden" name="option_type" value="sub_classification">

                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-indigo-lt text-indigo rounded-circle"><i class="ti ti-subtask"></i></span>
                        <h5 class="modal-title fw-bold">Add Sub Classification</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="alert alert-danger d-none py-2 px-3 small rounded-3 mb-3 modal-error-alert"></div>

                    <div class="mb-3">
                        <label class="form-label required small fw-semibold">Parent Main Classification</label>
                        <select name="classification_id" id="modalSubParentClassification" class="form-select rounded-3" required>
                            <option value="">Select Main Classification...</option>
                            @foreach ($classificationsList ?? [] as $mOpt)
                                <option value="{{ $mOpt->id }}">{{ $mOpt->classification_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required small fw-semibold">Sub Classification Name</label>
                        <input type="text" name="value" class="form-control rounded-3" placeholder="e.g. Production, QHSE, Korean" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Source</label>
                            <select name="source" class="form-select rounded-3">
                                <option value="all">Shared (Both)</option>
                                <option value="non_kp_owl">Non KP-OWL</option>
                                <option value="owl">OWL</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control rounded-3" value="10" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Explanation / Description</label>
                        <textarea name="explanation" class="form-control rounded-3" rows="2" placeholder="Optional notes or description..."></textarea>
                    </div>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="addSubIsActive">
                        <label class="form-check-label small fw-semibold" for="addSubIsActive">Active (Available for dropdowns)</label>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light-subtle rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check me-1"></i>Save Sub Classification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: ADD COURSE EXAMPLE -->
<div class="modal modal-blur fade" id="addCourseExampleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="{{ route('manage.classification.options.store') }}" method="POST" id="formAddCourseExample">
                @csrf
                <input type="hidden" name="option_type" value="course_name">

                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-purple-lt text-purple rounded-circle"><i class="ti ti-certificate"></i></span>
                        <h5 class="modal-title fw-bold">Add Course Example</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="alert alert-danger d-none py-2 px-3 small rounded-3 mb-3 modal-error-alert"></div>

                    <div class="mb-3">
                        <label class="form-label required small fw-semibold">Main Classification</label>
                        <select id="modalCourseMainSelect" class="form-select rounded-3" required>
                            <option value="">Select Main Classification...</option>
                            @foreach ($classificationsList ?? [] as $mOpt)
                                <option value="{{ $mOpt->id }}">{{ $mOpt->classification_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required small fw-semibold">Parent Sub Classification</label>
                        <select name="parent_id" id="modalCourseSubSelect" class="form-select rounded-3" required>
                            <option value="">Select Sub Classification...</option>
                            @foreach ($subClassificationsList ?? [] as $sOpt)
                                <option value="{{ $sOpt->id }}" data-main-id="{{ $sOpt->classification_id }}">{{ $sOpt->value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required small fw-semibold">Course Example Title</label>
                        <input type="text" name="value" class="form-control rounded-3" placeholder="e.g. Basic Python, Fire Fighting" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Source</label>
                            <select name="source" class="form-select rounded-3">
                                <option value="all">Shared (Both)</option>
                                <option value="non_kp_owl">Non KP-OWL</option>
                                <option value="owl">OWL</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control rounded-3" value="10" min="0">
                        </div>
                    </div>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="addCourseIsActive">
                        <label class="form-check-label small fw-semibold" for="addCourseIsActive">Active</label>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light-subtle rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check me-1"></i>Save Course Example
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 4: GENERIC ADD OPTION (For Type, Category, Delivery Method, Provider) -->
<div class="modal modal-blur fade" id="addOptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="{{ route('manage.classification.options.store') }}" method="POST" id="addOptionForm">
                @csrf
                <input type="hidden" name="option_type" id="modalAddOptionType" value="{{ $activeType }}">

                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm bg-primary-lt text-primary rounded-circle">
                            <i class="ti {{ $typeConfig['icon'] }}"></i>
                        </div>
                        <h5 class="modal-title fw-bold">Add New {{ $typeConfig['label'] }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <div id="addOptionAlert" class="alert alert-danger d-none py-2 px-3 small rounded-3 mb-3"></div>

                    <div class="mb-3">
                        <label class="form-label required small fw-semibold">Option Value / Name</label>
                        <input type="text" name="value" id="addOptionValue" class="form-control rounded-3" placeholder="Enter option name" required autofocus>
                    </div>

                    @if (!empty($typeConfig['is_hierarchical']))
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Parent {{ $allTypes[$typeConfig['parent_type']]['label'] ?? 'Parent' }}</label>
                            <select name="parent_id" id="addOptionParentId" class="form-select rounded-3">
                                <option value="">-- No Parent (Standalone) --</option>
                                @foreach ($parentOptions as $parent)
                                    <option value="{{ $parent->id }}">{{ $parent->value }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Source</label>
                            <select name="source" class="form-select rounded-3">
                                <option value="all">Shared (Both)</option>
                                <option value="non_kp_owl" {{ $activeType === 'category' ? 'selected' : '' }}>Non KP-OWL</option>
                                <option value="owl" {{ $activeType === 'type' ? 'selected' : '' }}>OWL</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control rounded-3" value="10" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Explanation / Description</label>
                        <textarea name="explanation" class="form-control rounded-3" rows="2" placeholder="Description or guidance..."></textarea>
                    </div>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="addGenericIsActive">
                        <label class="form-check-label small fw-semibold" for="addGenericIsActive">Active (Available in dropdowns)</label>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light-subtle rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btnSubmitAddOption" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check me-1"></i>Save {{ $typeConfig['label'] }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 5: EDIT MODAL (Handles Classification & All Options) -->
<div class="modal modal-blur fade" id="editOptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="#" method="POST" id="editOptionForm">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="option_type" id="modalEditOptionType" value="">
                <input type="hidden" id="modalEditOptionId" value="">

                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-primary-lt text-primary rounded-circle"><i class="ti ti-edit"></i></span>
                        <h5 class="modal-title fw-bold" id="editModalHeaderTitle">Edit Master Option</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <div id="editOptionAlert" class="alert alert-danger d-none py-2 px-3 small rounded-3 mb-3"></div>

                    <!-- Input for Classification Name -->
                    <div class="mb-3" id="editClassificationNameGroup" style="display: none;">
                        <label class="form-label required small fw-semibold">Classification Name</label>
                        <input type="text" name="classification_name" id="editClassificationName" class="form-control rounded-3">
                    </div>

                    <!-- Input for Option Value -->
                    <div class="mb-3" id="editOptionValueGroup">
                        <label class="form-label required small fw-semibold">Option Value / Name</label>
                        <input type="text" name="value" id="editOptionValue" class="form-control rounded-3" required>
                    </div>

                    <!-- Parent Selection for Hierarchical Options -->
                    <div class="mb-3" id="editOptionParentGroup" style="display: none;">
                        <label class="form-label small fw-semibold" id="editOptionParentLabel">Parent Option</label>
                        <select name="parent_id" id="editOptionParentId" class="form-select rounded-3">
                            <option value="">-- No Parent --</option>
                        </select>
                    </div>

                    <!-- Main Classification Selection for Sub Classification -->
                    <div class="mb-3" id="editClassificationParentGroup" style="display: none;">
                        <label class="form-label small fw-semibold">Parent Main Classification</label>
                        <select name="classification_id" id="editClassificationParentId" class="form-select rounded-3">
                            <option value="">Select Main Classification...</option>
                            @foreach ($classificationsList ?? [] as $mCls)
                                <option value="{{ $mCls->id }}">{{ $mCls->classification_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3" id="editOptionMetaRow">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Source</label>
                            <select name="source" id="editOptionSource" class="form-select rounded-3">
                                <option value="all">Shared (Both)</option>
                                <option value="non_kp_owl">Non KP-OWL</option>
                                <option value="owl">OWL</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Sort Order</label>
                            <input type="number" name="sort_order" id="editOptionSortOrder" class="form-control rounded-3" min="0">
                        </div>
                    </div>

                    <div class="mb-3" id="editOptionExplanationGroup">
                        <label class="form-label small fw-semibold">Explanation / Description</label>
                        <textarea name="explanation" id="editOptionExplanation" class="form-control rounded-3" rows="2"></textarea>
                    </div>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="editOptionIsActive" value="1">
                        <label class="form-check-label small fw-semibold" for="editOptionIsActive">Active (Available in dropdowns)</label>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light-subtle rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btnSubmitEditOption" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check me-1"></i>Update Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 6: SAFE DELETE CONFIRMATION -->
<div class="modal modal-blur fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-body text-center p-4">
                <span class="avatar avatar-xl bg-red-lt text-red mx-auto mb-3 rounded-circle">
                    <i class="ti ti-trash fs-1"></i>
                </span>
                <h3 class="mb-2 fw-bold text-dark">Delete Master Item?</h3>
                <p class="text-muted mb-0">
                    <strong id="deleteTargetName"></strong> will be permanently deleted from master database.
                </p>
                <div class="alert alert-info small py-2 px-3 mt-3 text-start rounded-3 mb-0">
                    <i class="ti ti-info-circle me-1"></i>Items with linked training records or sub-items cannot be deleted to preserve relational integrity.
                </div>
            </div>
            <div class="modal-footer border-0 bg-light-subtle rounded-bottom-4 justify-content-center">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-danger rounded-pill px-4 fw-semibold">
                    <i class="ti ti-trash me-1"></i>Delete Permanently
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 7: IN-USE WARNING & DEACTIVATE SUGGESTION -->
<div class="modal modal-blur fade" id="deactivateWarningModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-body text-center p-4">
                <span class="avatar avatar-xl bg-warning-lt text-warning mx-auto mb-3 rounded-circle">
                    <i class="ti ti-alert-triangle fs-1"></i>
                </span>
                <h3 class="mb-2 fw-bold text-dark">Item is Currently in Use!</h3>
                <p class="text-muted mb-3" id="deactivateWarningText">
                    This item cannot be deleted because existing training records or child options reference it.
                </p>
                <div class="alert alert-warning py-2 px-3 small rounded-3 text-start mb-0">
                    <i class="ti ti-shield-check me-1"></i>
                    <strong>Safe Deactivation Recommended:</strong> Deactivating will hide this option from future dropdowns while preserving historical data.
                </div>
            </div>
            <div class="modal-footer border-0 bg-light-subtle rounded-bottom-4 justify-content-center">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <button type="button" id="confirmDeactivateBtn" class="btn btn-warning rounded-pill px-4 fw-semibold" style="display: none;">
                    <i class="ti ti-eye-off me-1"></i>Deactivate Option
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
    .rounded-4 {
        border-radius: 20px !important;
    }

    .btn-back {
        color: #003b6d !important;
        transition: transform 0.2s ease, opacity 0.2s ease;
    }
    .btn-back:hover {
        opacity: 0.85;
        transform: translateX(-3px);
    }

    .type-nav-pill {
        transition: all 0.15s ease-in-out;
    }
    .type-nav-pill:not(.active-pill):hover {
        background-color: #eef2ff !important;
        border-color: #003b6d !important;
        color: #003b6d !important;
    }

    .option-table {
        font-size: 13px;
    }
    .option-table thead th {
        background: var(--tblr-bg-surface-secondary);
        color: var(--tblr-muted);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
        padding-top: 12px;
        padding-bottom: 12px;
    }
    .option-table tbody td {
        vertical-align: middle;
        padding-top: 12px;
        padding-bottom: 12px;
    }

    .main-classification-card {
        transition: box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.06) !important;
    }
    .main-classification-card:hover {
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08) !important;
    }

    .course-example-pill {
        font-size: 0.8rem;
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        transition: all 0.15s ease;
    }
    .course-example-pill:hover {
        background-color: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
    }

    .accordion-chevron-btn i {
        transition: transform 0.2s ease;
    }
    .accordion-header-clickable.collapsed .accordion-chevron-btn i {
        transform: rotate(-90deg);
    }

    .metric-box {
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Dark Mode Custom Overrides */
    [data-bs-theme="dark"] .option-hero-card,
    body.dark-mode .option-hero-card,
    body.theme-dark .option-hero-card,
    [data-bs-theme="dark"] .type-selector-card,
    body.dark-mode .type-selector-card,
    body.theme-dark .type-selector-card,
    [data-bs-theme="dark"] .quick-filter-card,
    body.dark-mode .quick-filter-card,
    body.theme-dark .quick-filter-card,
    [data-bs-theme="dark"] .main-classification-card,
    body.dark-mode .main-classification-card,
    body.theme-dark .main-classification-card {
        background: var(--tblr-bg-surface, #1f2937) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }

    [data-bs-theme="dark"] .course-example-pill,
    body.dark-mode .course-example-pill,
    body.theme-dark .course-example-pill {
        background-color: #2d3748 !important;
        border-color: #4a5568 !important;
        color: #e2e8f0 !important;
    }

    [data-bs-theme="dark"] .hero-icon-box,
    body.dark-mode .hero-icon-box,
    body.theme-dark .hero-icon-box {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #93c5fd !important;
    }

    [data-bs-theme="dark"] .metric-box,
    body.dark-mode .metric-box,
    body.theme-dark .metric-box {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        var activeType = '{{ $activeType }}';
        var table = null;

        if ($('#MyTable').length) {
            table = $('#MyTable').DataTable({
                pageLength: {{ request('entries', 25) }},
                autoWidth: false,
                responsive: true,
                order: [],
                language: {
                    search: '',
                    searchPlaceholder: 'Search...',
                    zeroRecords: 'No items found',
                    info: 'Showing _START_ - _END_ of _TOTAL_ entries',
                    paginate: {
                        previous: '‹',
                        next: '›'
                    }
                }
            });

            $('#option-entries-select').on('change', function () {
                var len = parseInt($(this).val(), 10);
                table.page.len(len).draw();
            });
        }

        // Live search connection
        var searchInput = document.getElementById('manage-option-search-input');
        if (searchInput && table) {
            searchInput.addEventListener('input', function () {
                table.search(this.value.trim()).draw();
            });
        }

        // =========================================================================
        // EXPAND ALL / COLLAPSE ALL ACCORDIONS
        // =========================================================================
        $('#btnToggleAllAccordions').on('click', function () {
            var btn = $(this);
            var state = btn.attr('data-state');
            var collapses = $('.classification-tree-accordion .collapse');

            if (state === 'expanded') {
                collapses.collapse('hide');
                btn.attr('data-state', 'collapsed');
                btn.html('<i class="ti ti-unfold me-1"></i>Expand All');
            } else {
                collapses.collapse('show');
                btn.attr('data-state', 'expanded');
                btn.html('<i class="ti ti-fold me-1"></i>Collapse All');
            }
        });

        // =========================================================================
        // AJAX HELPER FOR FORM SUBMISSIONS
        // =========================================================================
        function submitAjaxForm(form, btn, alertBox, modalId) {
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
            alertBox.addClass('d-none').text('');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (res) {
                    btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Saved');
                    var modalEl = document.getElementById(modalId);
                    if (modalEl) {
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                    window.location.reload();
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Submit');
                    var msg = 'Failed to process request.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alertBox.html(msg).removeClass('d-none');
                }
            });
        }

        // Form 1: Add Main Classification
        $('#formAddMainClassification').on('submit', function (e) {
            e.preventDefault();
            submitAjaxForm($(this), $(this).find('button[type="submit"]'), $(this).find('.modal-error-alert'), 'addMainClassificationModal');
        });

        // Form 2: Add Sub Classification
        $('#formAddSubClassification').on('submit', function (e) {
            e.preventDefault();
            submitAjaxForm($(this), $(this).find('button[type="submit"]'), $(this).find('.modal-error-alert'), 'addSubClassificationModal');
        });

        // Form 3: Add Course Example
        $('#formAddCourseExample').on('submit', function (e) {
            e.preventDefault();
            submitAjaxForm($(this), $(this).find('button[type="submit"]'), $(this).find('.modal-error-alert'), 'addCourseExampleModal');
        });

        // Form 4: Generic Add Option
        $('#addOptionForm').on('submit', function (e) {
            e.preventDefault();
            submitAjaxForm($(this), $('#btnSubmitAddOption'), $('#addOptionAlert'), 'addOptionModal');
        });

        // =========================================================================
        // DYNAMIC PRE-SELECTION & CASCADE LOGIC
        // =========================================================================
        $(document).on('click', '.btn-add-sub-to-main', function () {
            var mainId = $(this).data('main-id');
            $('#modalSubParentClassification').val(mainId);
            var modal = new bootstrap.Modal(document.getElementById('addSubClassificationModal'));
            modal.show();
        });

        $(document).on('click', '.btn-add-course-inline', function () {
            var subId = $(this).data('sub-id');
            var mainId = $(this).data('main-id');

            $('#modalCourseMainSelect').val(mainId);
            $('#modalCourseSubSelect').val(subId);

            var modal = new bootstrap.Modal(document.getElementById('addCourseExampleModal'));
            modal.show();
        });

        // Filter sub options in Add Course Modal when Main is changed
        $('#modalCourseMainSelect').on('change', function () {
            var mainId = $(this).val();
            var $subSelect = $('#modalCourseSubSelect');

            if (!mainId) {
                $subSelect.find('option').show();
                return;
            }

            $.ajax({
                url: '/manage/classification/options/subs-by-main/' + mainId,
                type: 'GET',
                success: function (res) {
                    if (res.success && res.data) {
                        $subSelect.empty().append('<option value="">Select Sub Classification...</option>');
                        res.data.forEach(function (sub) {
                            $subSelect.append(new Option(sub.value, sub.id));
                        });
                    }
                }
            });
        });

        // Sub Delivery Trigger
        $(document).on('click', '.btn-add-sub-delivery-trigger', function () {
            var parentId = $(this).data('parent-id');
            $('#modalAddOptionType').val('sub_delivery_method');
            $('#addOptionParentId').val(parentId);
            var modal = new bootstrap.Modal(document.getElementById('addOptionModal'));
            modal.show();
        });

        // Sub Provider Trigger
        $(document).on('click', '.btn-add-sub-provider-trigger', function () {
            var parentId = $(this).data('parent-id');
            $('#modalAddOptionType').val('sub_provider');
            $('#addOptionParentId').val(parentId);
            var modal = new bootstrap.Modal(document.getElementById('addOptionModal'));
            modal.show();
        });

        // =========================================================================
        // EDIT CLASSIFICATION TRIGGER
        // =========================================================================
        $(document).on('click', '.btn-edit-classification', function () {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var active = $(this).data('active');

            $('#modalEditOptionType').val('classification');
            $('#modalEditOptionId').val(id);
            $('#editOptionForm').attr('action', '/manage/classification/options/' + id);

            $('#editModalHeaderTitle').text('Edit Main Classification');
            $('#editClassificationNameGroup').show();
            $('#editClassificationName').val(name);
            $('#editOptionValueGroup').hide();
            $('#editOptionParentGroup').hide();
            $('#editClassificationParentGroup').hide();
            $('#editOptionMetaRow').hide();
            $('#editOptionExplanationGroup').hide();
            $('#editOptionIsActive').prop('checked', active == '1');

            $('#editOptionAlert').addClass('d-none').text('');
            var modal = new bootstrap.Modal(document.getElementById('editOptionModal'));
            modal.show();
        });

        // =========================================================================
        // EDIT SUB CLASSIFICATION TRIGGER
        // =========================================================================
        $(document).on('click', '.btn-edit-sub-classification', function () {
            var id = $(this).data('id');
            var value = $(this).data('value');
            var explanation = $(this).data('explanation');
            var source = $(this).data('source') || 'all';
            var mainId = $(this).data('main-id');
            var order = $(this).data('order') || 0;
            var active = $(this).data('active');

            $('#modalEditOptionType').val('sub_classification');
            $('#modalEditOptionId').val(id);
            $('#editOptionForm').attr('action', '/manage/classification/options/' + id);

            $('#editModalHeaderTitle').text('Edit Sub Classification');
            $('#editClassificationNameGroup').hide();
            $('#editOptionValueGroup').show();
            $('#editOptionValue').val(value);
            $('#editOptionParentGroup').hide();
            $('#editClassificationParentGroup').show();
            $('#editClassificationParentId').val(mainId);
            $('#editOptionMetaRow').show();
            $('#editOptionSource').val(source);
            $('#editOptionSortOrder').val(order);
            $('#editOptionExplanationGroup').show();
            $('#editOptionExplanation').val(explanation || '');
            $('#editOptionIsActive').prop('checked', active == '1');

            $('#editOptionAlert').addClass('d-none').text('');
            var modal = new bootstrap.Modal(document.getElementById('editOptionModal'));
            modal.show();
        });

        // =========================================================================
        // EDIT COURSE EXAMPLE TRIGGER (From pill close button)
        // =========================================================================
        $(document).on('click', '.btn-edit-course-trigger', function (e) {
            e.stopPropagation();
            var id = $(this).data('id');
            var value = $(this).data('value');
            var parentId = $(this).data('parent-id');
            var mainId = $(this).data('main-id');

            $('#modalEditOptionType').val('course_name');
            $('#modalEditOptionId').val(id);
            $('#editOptionForm').attr('action', '/manage/classification/options/' + id);

            $('#editModalHeaderTitle').text('Edit Course Example');
            $('#editClassificationNameGroup').hide();
            $('#editOptionValueGroup').show();
            $('#editOptionValue').val(value);
            $('#editOptionParentGroup').hide();
            $('#editClassificationParentGroup').hide();
            $('#editOptionMetaRow').show();
            $('#editOptionExplanationGroup').hide();
            $('#editOptionIsActive').prop('checked', true);

            $('#editOptionAlert').addClass('d-none').text('');
            var modal = new bootstrap.Modal(document.getElementById('editOptionModal'));
            modal.show();
        });

        // =========================================================================
        // EDIT GENERIC OPTION TRIGGER
        // =========================================================================
        $(document).on('click', '.btn-edit-generic-option', function () {
            var id = $(this).data('id');
            $('#modalEditOptionId').val(id);
            $('#editOptionForm').attr('action', '/manage/classification/options/' + id);

            $.ajax({
                url: '/manage/classification/options/' + id,
                type: 'GET',
                success: function (res) {
                    if (res.success && res.data) {
                        var data = res.data;
                        $('#modalEditOptionType').val(data.option_type);
                        $('#editModalHeaderTitle').text('Edit Option');

                        $('#editClassificationNameGroup').hide();
                        $('#editOptionValueGroup').show();
                        $('#editOptionValue').val(data.value);

                        if (data.parent_id) {
                            $('#editOptionParentGroup').show();
                            $('#editOptionParentId').empty().append(new Option(data.parent ? data.parent.value : 'Parent #' + data.parent_id, data.parent_id, true, true));
                        } else {
                            $('#editOptionParentGroup').hide();
                        }

                        $('#editClassificationParentGroup').hide();
                        $('#editOptionMetaRow').show();
                        $('#editOptionSource').val(data.source || 'all');
                        $('#editOptionSortOrder').val(data.sort_order || 0);
                        $('#editOptionExplanationGroup').show();
                        $('#editOptionExplanation').val(data.explanation || '');
                        $('#editOptionIsActive').prop('checked', data.is_active == 1);

                        $('#editOptionAlert').addClass('d-none').text('');
                        var modal = new bootstrap.Modal(document.getElementById('editOptionModal'));
                        modal.show();
                    }
                }
            });
        });

        // Submit Edit Option Form
        $('#editOptionForm').on('submit', function (e) {
            e.preventDefault();
            submitAjaxForm($(this), $('#btnSubmitEditOption'), $('#editOptionAlert'), 'editOptionModal');
        });

        // =========================================================================
        // TOGGLE STATUS (AJAX)
        // =========================================================================
        $(document).on('click', '.btn-toggle-status', function () {
            var btn = $(this);
            var id = btn.data('id');
            btn.prop('disabled', true);

            $.ajax({
                url: '/manage/classification/options/' + id + '/toggle-status',
                type: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function () {
                    window.location.reload();
                },
                error: function () {
                    btn.prop('disabled', false);
                    alert('Failed to toggle active status.');
                }
            });
        });

        // =========================================================================
        // DELETE HANDLING (SAFE CHECK & CONFIRMATION)
        // =========================================================================
        var pendingDeleteType = null;
        var pendingDeleteId = null;

        // Delete Classification Trigger
        $(document).on('click', '.btn-delete-classification', function () {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var usage = parseInt($(this).data('usage'), 10);

            if (usage > 0) {
                $('#deactivateWarningText').text(
                    'Classification "' + name + '" is linked to ' + usage + ' record(s) or sub classification(s) and cannot be deleted.'
                );
                $('#confirmDeactivateBtn').hide();
                var warnModal = new bootstrap.Modal(document.getElementById('deactivateWarningModal'));
                warnModal.show();
                return;
            }

            pendingDeleteType = 'classification';
            pendingDeleteId = id;
            $('#deleteTargetName').text(name);

            var delModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            delModal.show();
        });

        // Delete Option Trigger
        $(document).on('click', '.btn-delete-option', function () {
            var id = $(this).data('id');
            var value = $(this).data('value');

            $.ajax({
                url: '/manage/classification/options/' + id + '/usage',
                type: 'GET',
                success: function (res) {
                    if (res.success && res.data && res.data.is_used) {
                        $('#deactivateWarningText').text(res.data.message);
                        if (res.data.can_deactivate) {
                            $('#confirmDeactivateBtn').show().data('id', id);
                        } else {
                            $('#confirmDeactivateBtn').hide();
                        }

                        var warnModal = new bootstrap.Modal(document.getElementById('deactivateWarningModal'));
                        warnModal.show();
                    } else {
                        pendingDeleteType = 'option';
                        pendingDeleteId = id;
                        $('#deleteTargetName').text(value);

                        var delModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                        delModal.show();
                    }
                },
                error: function () {
                    alert('Failed to check option usage.');
                }
            });
        });

        // Confirm Permanent Delete Execution
        $('#confirmDeleteBtn').on('click', function () {
            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Deleting...');

            var url = (pendingDeleteType === 'classification')
                ? '/manage/classification/' + pendingDeleteId
                : '/manage/classification/options/' + pendingDeleteId;

            $.ajax({
                url: url,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function () {
                    window.location.reload();
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<i class="ti ti-trash me-1"></i> Delete Permanently');
                    var msg = 'Failed to delete item.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alert(msg);
                }
            });
        });

        // Confirm Deactivate from Warning Modal
        $('#confirmDeactivateBtn').on('click', function () {
            var id = $(this).data('id');
            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Deactivating...');

            $.ajax({
                url: '/manage/classification/options/' + id + '/toggle-status',
                type: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function () {
                    window.location.reload();
                },
                error: function () {
                    btn.prop('disabled', false).html('<i class="ti ti-eye-off me-1"></i> Deactivate Option');
                    alert('Failed to deactivate option.');
                }
            });
        });
    });
</script>
@endsection
