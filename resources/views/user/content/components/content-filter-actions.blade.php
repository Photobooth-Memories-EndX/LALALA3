<div class="content-filter-actions">
    <button type="submit" class="btn btn-primary content-filter-action-btn"
            title="Search" aria-label="Search">
        <i class="ti ti-search"></i>
    </button>

    <button type="button"
            class="btn btn-outline-primary content-filter-action-btn position-relative"
            data-bs-toggle="modal"
            data-bs-target="#contentMoreFiltersModal"
            title="More Filters"
            aria-label="More Filters">
        <i class="ti ti-adjustments"></i>
        @if ($modalFilterCount > 0)
            <span class="badge bg-primary text-white content-filter-count">{{ $modalFilterCount }}</span>
        @endif
    </button>

    <button type="submit" class="btn btn-primary content-filter-action-btn"
            title="Apply Filter" aria-label="Apply Filter">
        <i class="ti ti-check"></i>
    </button>

    <a href="{{ route('content.user.index') }}"
       class="btn btn-outline-primary content-filter-action-btn content-reset-btn {{ $activeFilterCount > 0 ? 'is-active' : '' }}"
       title="Reset Filter"
       aria-label="Reset Filter">
        <i class="ti ti-refresh"></i>
    </a>
</div>
