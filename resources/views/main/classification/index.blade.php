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

        <!-- Training Option Management Hero Header Box -->
        <div class="card border-0 shadow-sm p-4 mb-3 rounded-3 bg-white option-hero-card">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <!-- Left Group: Icon Box + Title & Subtitle -->
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                        <i class="ti ti-settings" style="font-size: 2.2rem !important;"></i>
                    </div>

                    <!-- Title & Subtitle -->
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h2 class="h3 fw-bold mb-0 text-dark">Training Option Management</h2>
                            <span class="badge bg-primary-lt text-primary px-2 py-1 rounded-pill small fw-semibold">
                                {{ $typeConfig['label'] }}
                            </span>
                        </div>
                        <p class="text-muted small mb-0">Manage training classifications, types, categories, levels, delivery methods, and other dropdown options across OWL LMS.</p>
                    </div>
                </div>

                <!-- Right Group: Primary Action Button -->
                <div class="flex-shrink-0">
                    <button type="button" 
                            class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm rounded-3 btn-add-option-trigger" 
                            style="background-color: #003b6d; border-color: #003b6d;"
                            data-bs-toggle="modal" 
                            data-bs-target="#addOptionModal">
                        <i class="ti ti-plus fs-2"></i><span>Add {{ $typeConfig['label'] }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- OPTION TYPE SELECTOR TOOLBAR -->
        <div class="card border-0 shadow-sm rounded-4 mb-3 type-selector-card">
            <div class="card-body p-2 p-md-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <!-- Left: Primary Type Badges / Pills -->
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

                        <!-- Secondary Dropdown for Other Options -->
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
                                <span>{{ $isSecondaryActive ? $typeConfig['label'] : 'More Options (' . count($secondaryTypes) . ')' }}</span>
                            </button>
                            <ul class="dropdown-menu shadow-lg py-1 border-0 rounded-3 mt-1" aria-labelledby="secondaryTypesDropdown" style="min-width: 220px; max-height: 340px; overflow-y: auto;">
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

                    <!-- Right: Summary Metric Badge -->
                    <div class="text-muted small d-none d-lg-flex align-items-center gap-3">
                        <span title="Total options defined in this section">
                            <strong class="text-dark">{{ $total_count }}</strong> {{ strtolower($typeConfig['plural_label']) }}
                        </span>
                        @if ($activeType !== 'classification')
                            <span class="text-success" title="Active options">
                                <i class="ti ti-circle-check-filled text-success me-1"></i>{{ $active_count }} active
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

        {{-- SEARCH & ENTRIES TOOLBAR (Quick Filter) --}}
        <div class="card border-0 shadow-sm rounded-4 mb-3 quick-filter-card">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-secondary small fw-semibold" style="color: #6c757d !important; font-size: 0.85rem;">
                        <i class="ti ti-filter me-1"></i>Quick Filter
                    </div>
                    <div class="text-muted small">
                        Filtering <strong>{{ $typeConfig['label'] }}</strong>
                    </div>
                </div>
                
                <form action="{{ route('manage.classification.index') }}" method="GET" id="manageOptionFilterForm">
                    <input type="hidden" name="type" value="{{ $activeType }}">
                    
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Entries Dropdown -->
                        <div class="entries-select-wrapper" style="min-width: 130px;">
                            <select id="option-entries-select" name="entries" class="form-select rounded-3">
                                <option value="10" {{ request('entries') == 10 ? 'selected' : '' }}>10 Entries</option>
                                <option value="25" {{ (request('entries') == 25 || !request()->has('entries')) ? 'selected' : '' }}>25 Entries</option>
                                <option value="50" {{ request('entries') == 50 ? 'selected' : '' }}>50 Entries</option>
                                <option value="100" {{ request('entries') == 100 ? 'selected' : '' }}>100 Entries</option>
                            </select>
                        </div>

                        <!-- Status Filter (for offline_training_options) -->
                        @if ($activeType !== 'classification')
                            <div style="min-width: 130px;">
                                <select name="status" class="form-select rounded-3" id="option-status-filter" onchange="this.form.submit()">
                                    <option value="" {{ request('status') === null || request('status') === '' ? 'selected' : '' }}>All Status</option>
                                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active Only</option>
                                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive Only</option>
                                </select>
                            </div>
                        @endif

                        <!-- Year Filter (for Corporate KPI) -->
                        @if (!empty($typeConfig['is_year_scoped']) && !empty($kpiYears))
                            <div style="min-width: 130px;">
                                <select name="year" class="form-select rounded-3" id="option-year-filter" onchange="this.form.submit()">
                                    <option value="">All KPI Years</option>
                                    @foreach ($kpiYears as $yr)
                                        <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Parent Filter (if hierarchical) -->
                        @if (!empty($typeConfig['is_hierarchical']) && $parentOptions->isNotEmpty())
                            <div style="min-width: 170px;">
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

                        <!-- Live Search Input -->
                        <div class="position-relative manage-search-container flex-grow-1" style="min-width: 200px;">
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
                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            <!-- Search Submit Button -->
                            <button type="submit" class="btn btn-primary btn-icon px-3 rounded-3" title="Search">
                                <i class="ti ti-search fs-2"></i>
                            </button>

                            <!-- More Filters Trigger Button -->
                            <button type="button" class="btn btn-outline-primary btn-icon px-3 rounded-3 position-relative" data-bs-toggle="modal" data-bs-target="#moreFilterModal" title="More Filters">
                                <i class="ti ti-adjustments-horizontal fs-2"></i>
                            </button>

                            <!-- Reset Button -->
                            <a href="{{ route('manage.classification.index', ['type' => $activeType]) }}" class="btn btn-outline-secondary btn-icon px-3 rounded-3" title="Reset Filters">
                                <i class="ti ti-refresh fs-2"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE CARD --}}
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header border-0 bg-transparent py-3 px-4 d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="card-title mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="ti {{ $typeConfig['icon'] }} text-primary"></i>
                        List {{ $typeConfig['label'] }}
                    </h3>
                    <div class="text-muted small">
                        {{ $typeConfig['description'] }}
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-muted border px-2 py-1">
                        {{ count($items) }} row(s) displayed
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div id="table-default" class="table-responsive">
                    @if (count($items) > 0)
                        <table class="table table-vcenter card-table table-hover option-table w-100 mb-0" id="MyTable">
                            <thead class="bg-light-subtle">
                                <tr>
                                    <th width="60" class="text-center">No</th>
                                    @if ($activeType === 'classification')
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th class="text-center" width="160">Usage</th>
                                    @else
                                        <th>Option Value</th>
                                        @if (!empty($typeConfig['is_hierarchical']))
                                            <th>Parent ({{ $allTypes[$typeConfig['parent_type']]['label'] ?? 'Parent' }})</th>
                                        @endif
                                        @if (!empty($typeConfig['is_year_scoped']))
                                            <th class="text-center" width="100">Year</th>
                                        @endif
                                        <th class="text-center" width="110">Status</th>
                                        <th class="text-center" width="90">Order</th>
                                    @endif
                                    <th width="180" class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td class="text-center text-muted fw-semibold">
                                            {{ $loop->iteration }}
                                        </td>

                                        @if ($activeType === 'classification')
                                            <!-- CLASSIFICATION COLUMNS -->
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="avatar avatar-sm bg-primary-lt text-primary rounded-circle fw-bold">
                                                        {{ strtoupper(substr($item->classification_name ?? 'C', 0, 1)) }}
                                                    </span>
                                                    <div>
                                                        <div class="fw-semibold text-dark item-value-text">
                                                            {{ $item->classification_name }}
                                                        </div>
                                                        <div class="text-muted small">
                                                            ID: #{{ $item->id }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="font-monospace small text-muted bg-light px-2 py-1 rounded">
                                                    /{{ $item->slug ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if (($item->total_usage ?? 0) > 0)
                                                    <span class="badge bg-blue-lt text-primary px-2 py-1 rounded-pill" title="{{ $item->content_count }} content(s), {{ $item->offline_count }} training record(s)">
                                                        <i class="ti ti-link me-1"></i>{{ $item->total_usage }} used
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted border px-2 py-1 rounded-pill">
                                                        Unused
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-list justify-content-end pe-2">
                                                    <button type="button" 
                                                            class="btn btn-outline-primary btn-sm rounded-pill btn-edit-classification"
                                                            data-id="{{ $item->id }}"
                                                            data-name="{{ $item->classification_name }}"
                                                            data-slug="{{ $item->slug }}"
                                                            title="Edit Classification">
                                                        <i class="ti ti-edit me-1"></i>Edit
                                                    </button>

                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm rounded-pill btn-delete-classification"
                                                            data-id="{{ $item->id }}"
                                                            data-name="{{ $item->classification_name }}"
                                                            data-usage="{{ $item->total_usage ?? 0 }}"
                                                            title="Delete Classification">
                                                        <i class="ti ti-trash me-1"></i>Delete
                                                    </button>
                                                </div>
                                            </td>
                                        @else
                                            <!-- OFFLINE TRAINING OPTION COLUMNS -->
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="avatar avatar-sm bg-primary-lt text-primary rounded-circle fw-bold">
                                                        {{ strtoupper(substr($item->value ?? 'O', 0, 1)) }}
                                                    </span>
                                                    <div>
                                                        <div class="fw-semibold text-dark item-value-text">
                                                            {{ $item->value }}
                                                        </div>
                                                        @if ($item->created_from === 'manual')
                                                            <div class="text-muted small" style="font-size: 0.75rem;">
                                                                <i class="ti ti-user me-1"></i>Manual
                                                            </div>
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
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                            @endif

                                            @if (!empty($typeConfig['is_year_scoped']))
                                                <td class="text-center">
                                                    <span class="badge bg-purple-lt text-purple fw-bold px-2 py-1 rounded-pill">
                                                        {{ $item->year ?: 'Global' }}
                                                    </span>
                                                </td>
                                            @endif

                                            <td class="text-center">
                                                @if ($item->is_active)
                                                    <span class="badge bg-success-lt text-success px-2 py-1 rounded-pill fw-semibold">
                                                        <i class="ti ti-check me-1"></i>Active
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-lt text-secondary px-2 py-1 rounded-pill fw-semibold">
                                                        <i class="ti ti-ban me-1"></i>Inactive
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="text-center text-muted font-monospace small">
                                                {{ $item->sort_order }}
                                            </td>

                                            <td>
                                                <div class="btn-list justify-content-end pe-2">
                                                    <!-- Toggle Active/Inactive Quick Action -->
                                                    <button type="button" 
                                                            class="btn btn-sm btn-icon {{ $item->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-circle btn-toggle-status"
                                                            data-id="{{ $item->id }}"
                                                            data-active="{{ $item->is_active ? '1' : '0' }}"
                                                            title="{{ $item->is_active ? 'Deactivate Option' : 'Activate Option' }}">
                                                        <i class="ti {{ $item->is_active ? 'ti-eye-off' : 'ti-eye' }}"></i>
                                                    </button>

                                                    <!-- Edit Option Button -->
                                                    <button type="button" 
                                                            class="btn btn-outline-primary btn-sm rounded-pill btn-edit-option"
                                                            data-id="{{ $item->id }}"
                                                            title="Edit Option">
                                                        <i class="ti ti-edit me-1"></i>Edit
                                                    </button>

                                                    <!-- Delete Option Button -->
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm rounded-pill btn-delete-option"
                                                            data-id="{{ $item->id }}"
                                                            data-value="{{ $item->value }}"
                                                            data-active="{{ $item->is_active ? '1' : '0' }}"
                                                            title="Delete Option">
                                                        <i class="ti ti-trash me-1"></i>Delete
                                                    </button>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <!-- EMPTY STATE -->
                        <div class="text-center py-5 px-3">
                            <div class="avatar avatar-xl bg-light-subtle text-muted rounded-circle mx-auto mb-3" style="width: 72px; height: 72px;">
                                <i class="ti {{ $typeConfig['icon'] }} fs-1"></i>
                            </div>
                            <h3 class="mt-2 mb-1 fw-bold text-dark">
                                No {{ $typeConfig['label'] }} Options Found
                            </h3>
                            <p class="text-muted mb-4 small" style="max-width: 420px; margin: 0 auto;">
                                Belum ada data opsi untuk <strong>{{ $typeConfig['label'] }}</strong> atau pencarian Anda tidak menemukan hasil yang cocok.
                            </p>
                            <button type="button" 
                                    class="btn btn-primary rounded-pill px-4" 
                                    style="background-color: #003b6d; border-color: #003b6d;"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#addOptionModal">
                                <i class="ti ti-plus me-1"></i>
                                Add First {{ $typeConfig['label'] }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODALS SECTION                                                            --}}
{{-- ========================================================================= --}}

<!-- 1. ADD OPTION MODAL -->
<div class="modal modal-blur fade" id="addOptionModal" tabindex="-1" aria-labelledby="addOptionModalLabel" aria-hidden="true">
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
                        <h5 class="modal-title fw-bold" id="addOptionModalLabel">
                            Add New {{ $typeConfig['label'] }}
                        </h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <p class="text-muted small mb-3">
                        Create a standardized option for <strong>{{ $typeConfig['label'] }}</strong>. It will immediately be available in learning dropdowns.
                    </p>

                    <div id="addOptionAlert" class="alert alert-danger d-none py-2 px-3 small rounded-3 mb-3"></div>

                    @if ($activeType === 'classification')
                        <div class="mb-3">
                            <label class="form-label required small fw-semibold">Classification Name</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-category"></i></span>
                                <input type="text" name="classification_name" id="addClassificationName" class="form-control rounded-3" placeholder="e.g. Leadership Development" required autofocus>
                            </div>
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label required small fw-semibold">Option Value</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti {{ $typeConfig['icon'] }}"></i></span>
                                <input type="text" name="value" id="addOptionValue" class="form-control rounded-3" placeholder="Enter option name / value" required autofocus autocomplete="off">
                            </div>
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

                        @if (!empty($typeConfig['is_year_scoped']))
                            <div class="mb-3">
                                <label class="form-label required small fw-semibold">KPI Year</label>
                                <select name="year" id="addOptionYear" class="form-select rounded-3">
                                    @foreach ($kpiYears as $yr)
                                        <option value="{{ $yr }}" {{ $yr == date('Y') ? 'selected' : '' }}>{{ $yr }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Sort Order</label>
                                <input type="number" name="sort_order" id="addOptionSortOrder" class="form-control rounded-3" value="0" min="0" max="9999">
                            </div>
                            <div class="col-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="addOptionIsActive" value="1" checked>
                                    <label class="form-check-label small fw-semibold" for="addOptionIsActive">Active</label>
                                </div>
                            </div>
                        </div>
                    @endif
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

<!-- 2. EDIT OPTION MODAL -->
<div class="modal modal-blur fade" id="editOptionModal" tabindex="-1" aria-labelledby="editOptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="#" method="POST" id="editOptionForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="option_type" id="modalEditOptionType" value="{{ $activeType }}">
                <input type="hidden" id="modalEditOptionId" value="">

                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm bg-primary-lt text-primary rounded-circle">
                            <i class="ti ti-edit"></i>
                        </div>
                        <h5 class="modal-title fw-bold" id="editOptionModalLabel">
                            Edit {{ $typeConfig['label'] }}
                        </h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <div id="editOptionAlert" class="alert alert-danger d-none py-2 px-3 small rounded-3 mb-3"></div>

                    @if ($activeType === 'classification')
                        <div class="mb-3">
                            <label class="form-label required small fw-semibold">Classification Name</label>
                            <input type="text" name="classification_name" id="editClassificationName" class="form-control rounded-3" required autofocus>
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label required small fw-semibold">Option Value</label>
                            <input type="text" name="value" id="editOptionValue" class="form-control rounded-3" required autofocus autocomplete="off">
                        </div>

                        @if (!empty($typeConfig['is_hierarchical']))
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Parent {{ $allTypes[$typeConfig['parent_type']]['label'] ?? 'Parent' }}</label>
                                <select name="parent_id" id="editOptionParentId" class="form-select rounded-3">
                                    <option value="">-- No Parent (Standalone) --</option>
                                    @foreach ($parentOptions as $parent)
                                        <option value="{{ $parent->id }}">{{ $parent->value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if (!empty($typeConfig['is_year_scoped']))
                            <div class="mb-3">
                                <label class="form-label required small fw-semibold">KPI Year</label>
                                <select name="year" id="editOptionYear" class="form-select rounded-3">
                                    @foreach ($kpiYears as $yr)
                                        <option value="{{ $yr }}">{{ $yr }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Sort Order</label>
                                <input type="number" name="sort_order" id="editOptionSortOrder" class="form-control rounded-3" min="0" max="9999">
                            </div>
                            <div class="col-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="editOptionIsActive" value="1">
                                    <label class="form-check-label small fw-semibold" for="editOptionIsActive">Active</label>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer border-0 bg-light-subtle rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btnSubmitEditOption" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check me-1"></i>Update {{ $typeConfig['label'] }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. SAFE DELETE CONFIRMATION MODAL -->
<div class="modal modal-blur fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-body text-center p-4">
                <span class="avatar avatar-xl bg-red-lt text-red mx-auto mb-3 rounded-circle">
                    <i class="ti ti-trash fs-1"></i>
                </span>
                <h3 class="mb-2 fw-bold text-dark">Delete this {{ $typeConfig['label'] }}?</h3>
                <p class="text-muted mb-0">
                    <strong id="deleteTargetName"></strong> will be permanently deleted from the database.
                </p>
                <div class="alert alert-info small py-2 px-3 mt-3 text-start rounded-3 mb-0">
                    <i class="ti ti-info-circle me-1"></i>Only options that are not referenced by historical training records can be deleted.
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

<!-- 4. IN-USE WARNING & DEACTIVATE SUGGESTION MODAL -->
<div class="modal modal-blur fade" id="deactivateWarningModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-body text-center p-4">
                <span class="avatar avatar-xl bg-warning-lt text-warning mx-auto mb-3 rounded-circle">
                    <i class="ti ti-alert-triangle fs-1"></i>
                </span>
                <h3 class="mb-2 fw-bold text-dark">Option is Currently in Use!</h3>
                <p class="text-muted mb-3" id="deactivateWarningText">
                    This option cannot be deleted because existing training records or learning contents reference it.
                </p>
                <div class="alert alert-warning py-2 px-3 small rounded-3 text-start mb-0">
                    <i class="ti ti-shield-check me-1"></i>
                    <strong>Safe Deactivation Recommended:</strong> Deactivating will hide this option from future creation dropdowns while keeping historical records intact.
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

<!-- 5. MORE FILTERS MODAL -->
<div class="modal fade" id="moreFilterModal" tabindex="-1" aria-labelledby="moreFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="moreFilterModalLabel">
                    <i class="ti ti-adjustments-horizontal me-2 text-primary"></i>More Filters: {{ $typeConfig['label'] }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <form action="{{ route('manage.classification.index') }}" method="GET" id="moreFilterModalForm">
                    <input type="hidden" name="type" value="{{ $activeType }}">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Entries Per Page</label>
                        <select name="entries" class="form-select rounded-3">
                            <option value="10" {{ request('entries') == 10 ? 'selected' : '' }}>10 Entries</option>
                            <option value="25" {{ (request('entries') == 25 || !request()->has('entries')) ? 'selected' : '' }}>25 Entries</option>
                            <option value="50" {{ request('entries') == 50 ? 'selected' : '' }}>50 Entries</option>
                            <option value="100" {{ request('entries') == 100 ? 'selected' : '' }}>100 Entries</option>
                        </select>
                    </div>

                    @if ($activeType !== 'classification')
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Option Status</label>
                            <select name="status" class="form-select rounded-3">
                                <option value="" {{ request('status') === null || request('status') === '' ? 'selected' : '' }}>All Status</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active Only</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive Only</option>
                            </select>
                        </div>
                    @endif

                    @if (!empty($typeConfig['is_year_scoped']) && !empty($kpiYears))
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">KPI Year</label>
                            <select name="year" class="form-select rounded-3">
                                <option value="">All KPI Years</option>
                                @foreach ($kpiYears as $yr)
                                    <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if (!empty($typeConfig['is_hierarchical']) && $parentOptions->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Parent {{ $allTypes[$typeConfig['parent_type']]['label'] ?? 'Parent' }}</label>
                            <select name="parent_id" class="form-select rounded-3">
                                <option value="">All Parents</option>
                                @foreach ($parentOptions as $parent)
                                    <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>{{ $parent->value }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('manage.classification.index', ['type' => $activeType]) }}" class="btn btn-outline-secondary rounded-pill px-3">
                            Reset
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" style="background-color: #003b6d; border-color: #003b6d;">
                            Apply Filters
                        </button>
                    </div>
                </form>
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
        padding-top: 14px;
        padding-bottom: 14px;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        display: none !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 8px !important;
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
    body.theme-dark .quick-filter-card {
        background: var(--tblr-bg-surface, #1f2937) !important;
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
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'All']
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search {{ strtolower($typeConfig['label']) }}...',
                    lengthMenu: '_MENU_',
                    zeroRecords: 'No {{ strtolower($typeConfig['label']) }} found',
                    info: 'Showing _START_ - _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries available',
                    infoFiltered: '(filtered from _MAX_ total entries)',
                    paginate: {
                        previous: '‹',
                        next: '›'
                    }
                }
            });

            // Bind inline entries dropdown to DataTable
            $('#option-entries-select').on('change', function () {
                var len = parseInt($(this).val(), 10);
                table.page.len(len).draw();
            });
        }

        // Connect quick search input to DataTable
        var searchInput = document.getElementById('manage-option-search-input');
        if (searchInput && table) {
            searchInput.addEventListener('input', function () {
                table.search(this.value.trim()).draw();
            });
        }

        // =========================================================================
        // ADD OPTION / CLASSIFICATION (AJAX SUBMIT)
        // =========================================================================
        $('#addOptionForm').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#btnSubmitAddOption');
            var alertBox = $('#addOptionAlert');

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
                    var modal = bootstrap.Modal.getInstance(document.getElementById('addOptionModal'));
                    if (modal) modal.hide();

                    // Reload page to reflect new data
                    window.location.reload();
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Save {{ $typeConfig['label'] }}');
                    var msg = 'Failed to save option.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        msg = Object.values(errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alertBox.html(msg).removeClass('d-none');
                }
            });
        });

        // =========================================================================
        // EDIT CLASSIFICATION TRIGGER
        // =========================================================================
        $(document).on('click', '.btn-edit-classification', function () {
            var id = $(this).data('id');
            var name = $(this).data('name');

            $('#editClassificationName').val(name);
            $('#editOptionForm').attr('action', '/manage/classification/' + id);
            $('#modalEditOptionType').val('classification');
            $('#modalEditOptionId').val(id);
            $('#editOptionAlert').addClass('d-none').text('');

            var modal = new bootstrap.Modal(document.getElementById('editOptionModal'));
            modal.show();
        });

        // =========================================================================
        // EDIT OFFLINE TRAINING OPTION TRIGGER
        // =========================================================================
        $(document).on('click', '.btn-edit-option', function () {
            var id = $(this).data('id');
            var alertBox = $('#editOptionAlert');
            alertBox.addClass('d-none').text('');

            $('#modalEditOptionId').val(id);
            $('#editOptionForm').attr('action', '/manage/classification/options/' + id);

            // Fetch current details via AJAX
            $.ajax({
                url: '/manage/classification/options/' + id,
                type: 'GET',
                success: function (res) {
                    if (res.success && res.data) {
                        var data = res.data;
                        $('#editOptionValue').val(data.value);
                        if ($('#editOptionParentId').length) {
                            $('#editOptionParentId').val(data.parent_id || '');
                        }
                        if ($('#editOptionYear').length) {
                            $('#editOptionYear').val(data.year || '');
                        }
                        $('#editOptionSortOrder').val(data.sort_order || 0);
                        $('#editOptionIsActive').prop('checked', data.is_active == 1);

                        var modal = new bootstrap.Modal(document.getElementById('editOptionModal'));
                        modal.show();
                    }
                },
                error: function () {
                    alert('Failed to load option details.');
                }
            });
        });

        // =========================================================================
        // SUBMIT EDIT FORM (AJAX)
        // =========================================================================
        $('#editOptionForm').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#btnSubmitEditOption');
            var alertBox = $('#editOptionAlert');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');
            alertBox.addClass('d-none').text('');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (res) {
                    btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Updated');
                    var modal = bootstrap.Modal.getInstance(document.getElementById('editOptionModal'));
                    if (modal) modal.hide();

                    window.location.reload();
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Update {{ $typeConfig['label'] }}');
                    var msg = 'Failed to update option.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        msg = Object.values(errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alertBox.html(msg).removeClass('d-none');
                }
            });
        });

        // =========================================================================
        // TOGGLE STATUS (ACTIVE / INACTIVE)
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
                success: function (res) {
                    window.location.reload();
                },
                error: function () {
                    btn.prop('disabled', false);
                    alert('Failed to toggle option status.');
                }
            });
        });

        // =========================================================================
        // DELETE HANDLING (SAFE VALIDATION & DEACTIVATE WARNING)
        // =========================================================================
        var pendingDeleteType = null;
        var pendingDeleteId = null;

        // Delete Classification Trigger
        $(document).on('click', '.btn-delete-classification', function () {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var usage = parseInt($(this).data('usage'), 10);

            if (usage > 0) {
                // In-use warning modal
                $('#deactivateWarningText').text(
                    'Classification "' + name + '" is currently used by ' + usage + ' record(s) and cannot be deleted.'
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

            // Check usage first via API
            $.ajax({
                url: '/manage/classification/options/' + id + '/usage',
                type: 'GET',
                success: function (res) {
                    if (res.success && res.data && res.data.is_used) {
                        // Option is in use -> suggest deactivate!
                        $('#deactivateWarningText').text(res.data.message);
                        if (res.data.can_deactivate) {
                            $('#confirmDeactivateBtn')
                                .show()
                                .data('id', id);
                        } else {
                            $('#confirmDeactivateBtn').hide();
                        }

                        var warnModal = new bootstrap.Modal(document.getElementById('deactivateWarningModal'));
                        warnModal.show();
                    } else {
                        // Unused -> allow permanent delete
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
                success: function (res) {
                    window.location.reload();
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<i class="ti ti-trash me-1"></i> Delete Permanently');
                    var msg = 'Failed to delete.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alert(msg);
                }
            });
        });

        // Confirm Deactivate Action from Warning Modal
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
