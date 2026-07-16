{{-- Style ditaruh inline (bukan @push) karena komponen filter selalu dirender via AJAX,
     sehingga @stack di layout utama tidak ikut ter-render. Tag <style> tetap berlaku
     saat di-inject melalui innerHTML. Dipakai bersama oleh mode Content & Playlist. --}}
<style>
    .learning-filter-group { margin-bottom: 0; }
    .learning-filter-modal .form-check { margin-bottom: 0; }
    .learning-filter-mode-row .learning-mode-switch {
        margin-bottom: 0 !important;
    }
    .learning-filter-mode-actions {
        flex: 0 0 auto;
    }
    /* Quick dropdown filters (Type / Status / Year / Month) */
    .learning-quick-dropdowns .dropdown-toggle.active {
        border-color: var(--tblr-primary, #206bc4);
        color: var(--tblr-primary, #206bc4);
        background: var(--tblr-primary-lt, rgba(32, 107, 196, .1));
    }
    .learning-quick-dropdowns .dropdown-item.active {
        background: var(--tblr-primary, #206bc4);
        color: #fff;
    }
    .learning-quick-dropdown-scroll {
        max-height: 280px;
        overflow-y: auto;
    }
    @media (max-width: 991.98px) {
        .learning-filter-mode-actions,
        .learning-filter-mode-actions .learning-filter-actions {
            width: 100%;
        }
    }
    @media (max-width: 575.98px) {
        /* Mobile: modal muncul dari bawah seperti bottom-sheet Tokopedia. */
        .learning-filter-modal {
            margin: 0;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            max-width: 100%;
        }
        .learning-filter-modal .modal-content {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            max-height: 88vh;
        }
        /* Mobile: dropdown cepat berbagi lebar dua kolom agar tetap rapi. */
        .learning-quick-dropdowns .dropdown {
            flex: 1 1 calc(50% - .5rem);
        }
        .learning-quick-dropdowns .dropdown .dropdown-toggle {
            width: 100%;
        }
        /* Mobile: mode switch selebar container. */
        .learning-mode-switch {
            display: flex;
            width: 100%;
        }
        .learning-mode-switch .btn { flex: 1 1 50%; }
    }
</style>
