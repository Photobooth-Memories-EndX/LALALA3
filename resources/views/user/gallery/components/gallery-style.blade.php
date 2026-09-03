<link rel="stylesheet" href="{{ asset('css/gallery.css') }}">
<style>
/* Ensure search container establishes proper stacking context */
.gallery-search-container {
    position: relative !important;
}

/* Isolate gallery dropdown overlay */
#gallery-search-results {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
    overflow: hidden !important;
    top: 100% !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    z-index: 2000 !important;
    margin-top: 4px !important;
}

/* Scrollable item list */
#gallery-autocomplete-list {
    max-height: 280px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    background-color: #ffffff !important;
    scrollbar-width: thin;
}

#gallery-autocomplete-list::-webkit-scrollbar {
    width: 5px;
}

#gallery-autocomplete-list::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.18);
    border-radius: 4px;
}

/* Autocomplete items */
#gallery-search-results .dropdown-item {
    background-color: #ffffff !important;
    color: #333333 !important;
}

#gallery-search-results .dropdown-item:hover,
#gallery-search-results .dropdown-item:focus {
    background-color: #f8f9fa !important;
}

/* Sticky footer pinned outside scroll area */
#gallery-search-footer {
    background-color: #f8f9fa !important;
    border-top: 1px solid #e2e8f0 !important;
    position: sticky !important;
    bottom: 0 !important;
    z-index: 2005 !important;
}
</style>
