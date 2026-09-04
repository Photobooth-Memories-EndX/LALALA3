<div class="content-filter-search">
    <label class="visually-hidden" for="content-search-input">Search learning content</label>
    <div class="position-relative content-search-container flex-grow-1 search-input-wrapper">
        <div class="input-icon">
            <span class="input-icon-addon">
                <i class="ti ti-search"></i>
            </span>
            <input type="text"
                   name="s"
                   id="content-search-input"
                   value="{{ request('q', $filterSearch) }}"
                   class="form-control module-search-input"
                   placeholder="Search learning..."
                   autocomplete="off">
        </div>
        <div id="content-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 module-search-dropdown" style="display: none; max-height: 380px; overflow-y: auto; z-index: 1050; padding: 0;">
            <div class="autocomplete-list-group list-group list-group-flush"></div>
            <div class="search-footer border-top bg-light p-2 text-center sticky-bottom"></div>
        </div>
    </div>
</div>
