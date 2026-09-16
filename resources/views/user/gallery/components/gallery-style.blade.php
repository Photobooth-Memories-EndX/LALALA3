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

/* Dark mode overrides for autocomplete */
:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) #gallery-search-results {
    background-color: #152235 !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.45) !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) #gallery-autocomplete-list {
    background-color: #152235 !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) #gallery-autocomplete-list::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) #gallery-search-results .dropdown-item {
    background-color: #152235 !important;
    color: #f1f5f9 !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) #gallery-search-results .dropdown-item:hover,
:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) #gallery-search-results .dropdown-item:focus {
    background-color: #203a57 !important;
    color: #ffffff !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) #gallery-search-footer {
    background-color: #101c2c !important;
    border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) #gallery-search-footer a {
    background-color: transparent !important;
    color: #6aaed6 !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) #gallery-search-footer a:hover {
    color: #90cdf4 !important;
}

/* Gallery Card Action Buttons (View Gallery) aligned with My Learning tokens */
:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) .gallery-card .btn-primary,
:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) .gallery-card .btn-learning-primary {
    background: var(--learning-btn-primary, #0F5E9C) !important;
    border-color: var(--learning-btn-primary, #0F5E9C) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) .gallery-card .btn-primary:hover,
:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) .gallery-card .btn-primary:focus,
:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) .gallery-card .btn-learning-primary:hover,
:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) .gallery-card .btn-learning-primary:focus {
    background: var(--learning-btn-primary-hover, #0B4F84) !important;
    border-color: var(--learning-btn-primary-hover, #0B4F84) !important;
    color: #ffffff !important;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35) !important;
    transform: translateY(-1px);
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) .gallery-card .btn-primary:active,
:is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode, body.theme-dark) .gallery-card .btn-learning-primary:active {
    background: var(--learning-btn-primary-hover, #0B4F84) !important;
    border-color: var(--learning-btn-primary-hover, #0B4F84) !important;
    color: #ffffff !important;
    transform: translateY(0);
}
</style>
