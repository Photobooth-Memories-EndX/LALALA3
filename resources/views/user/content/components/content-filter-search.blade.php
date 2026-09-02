<div class="content-filter-search">
    <label class="visually-hidden" for="content-search-input">Search learning content</label>
    <div class="position-relative content-search-container flex-grow-1">
        <div class="input-icon">
            <span class="input-icon-addon">
                <i class="ti ti-search"></i>
            </span>
            <input type="text"
                   name="s"
                   id="content-search-input"
                   value="{{ $filterSearch }}"
                   class="form-control"
                   placeholder="Search learning..."
                   autocomplete="off">
        </div>
        <div id="content-search-results" class="dropdown-menu shadow-sm w-100 overflow-auto position-absolute mt-1" style="display: none; max-height: 300px; z-index: 1050;"></div>
    </div>
</div>
