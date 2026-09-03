<link rel="stylesheet" href="{{ asset('css/content.css') }}">
<style>
/* Global & Module Autocomplete Search Dropdown Containers */
.dropdown-menu.search-results-dropdown,
.search-autocomplete-container,
#content-search-results {
    background-color: #ffffff !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12) !important;
    border: 1px solid #e2e8f0 !important;
    overflow: hidden !important;
}

/* Scrollable List Container (Where items are rendered) */
.autocomplete-list-group,
#content-autocomplete-list {
    background-color: #ffffff !important;
    background: #ffffff !important;
}

/* Autocomplete Items */
#content-search-results .dropdown-item,
.dropdown-menu .dropdown-item,
.autocomplete-list-group .dropdown-item {
    background-color: #ffffff !important;
    color: #333333 !important;
}

#content-search-results .dropdown-item:hover,
#content-search-results .dropdown-item:focus,
.dropdown-menu .dropdown-item:hover,
.dropdown-menu .dropdown-item:focus {
    background-color: #f8f9fa !important;
}

/* Sticky Bottom Footer Area */
.search-footer,
#content-search-footer {
    background-color: #f8f9fa !important;
    border-top: 1px solid #e2e8f0 !important;
}
</style>
