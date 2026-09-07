<style>
/* Global & Module Autocomplete Search Dropdown Containers */
.dropdown-menu.search-results-dropdown,
.search-autocomplete-container,
#playlist-search-results {
    background-color: #ffffff !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12) !important;
    border: 1px solid #e2e8f0 !important;
    overflow: hidden !important;
}

/* Scrollable List Container (Where items are rendered) */
.autocomplete-list-group,
#playlist-autocomplete-list {
    background-color: #ffffff !important;
    background: #ffffff !important;
}

/* Autocomplete Items */
#playlist-search-results .dropdown-item,
.dropdown-menu .dropdown-item,
.autocomplete-list-group .dropdown-item {
    background-color: #ffffff !important;
    color: #333333 !important;
}

#playlist-search-results .dropdown-item:hover,
#playlist-search-results .dropdown-item:focus,
.dropdown-menu .dropdown-item:hover,
.dropdown-menu .dropdown-item:focus {
    background-color: #f8f9fa !important;
}

/* Sticky Bottom Footer Area */
.search-footer,
#playlist-search-footer {
    background-color: #f8f9fa !important;
    border-top: 1px solid #e2e8f0 !important;
}

/* ============================================================
   DARK MODE OVERRIDES (SCOPED TO PLAYLIST LISTING)
   ============================================================ */
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .dropdown-menu.search-results-dropdown,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .search-autocomplete-container,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlist-search-results {
    background-color: #152235 !important;
    background: #152235 !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .autocomplete-list-group,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlist-autocomplete-list {
    background-color: #152235 !important;
    background: #152235 !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlist-search-results .dropdown-item,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .dropdown-menu .dropdown-item,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .autocomplete-list-group .dropdown-item {
    background-color: #152235 !important;
    color: #f1f5f9 !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlist-search-results .dropdown-item:hover,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlist-search-results .dropdown-item:focus,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .dropdown-menu .dropdown-item:hover,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .dropdown-menu .dropdown-item:focus {
    background-color: rgba(106, 174, 214, 0.14) !important;
    color: #93c5fd !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .search-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlist-search-footer {
    background-color: #192d45 !important;
    border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
    color: #cbd5e1 !important;
}

/* Playlist Card Scoped Enhancements */
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-card {
    background: #152235 !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    color: #f1f5f9 !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-card .playlist-title a {
    color: #f8fafc !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-card .playlist-title a:hover {
    color: #93c5fd !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-card .text-muted {
    color: #cbd5e1 !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-card .text-muted span,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-card .text-muted div {
    color: #cbd5e1 !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-card .text-muted i {
    color: #94a3b8 !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-card .progress-wrapper .text-primary,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-card .playlist-content-list .text-primary {
    color: #60a5fa !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-card .progress-wrapper .text-muted {
    color: #94a3b8 !important;
}
</style>
