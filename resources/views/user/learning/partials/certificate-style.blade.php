<style>
    .certificate-table {
        width: 100% !important;
        min-width: 1120px;
        table-layout: fixed;
    }

    .certificate-table-wrap {
        max-height: 620px;
    }

    .certificate-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--tblr-bg-surface, #fff);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .025em;
        white-space: nowrap;
    }

    .certificate-table th,
    .certificate-table td {
        padding: .85rem 1rem;
        vertical-align: middle;
    }

    .certificate-table tbody td {
        height: 106px;
    }

    .certificate-col-no {
        width: 56px;
    }

    .certificate-col-main {
        width: auto;
    }

    .certificate-col-type {
        width: 120px;
    }

    .certificate-col-credential {
        width: 180px;
    }

    .certificate-col-issued {
        width: 96px;
    }

    .certificate-col-status {
        width: 122px;
    }

    .certificate-col-action {
        width: 126px;
    }

    .certificate-table tbody tr.certificate-row > td {
        transition: background-color .2s ease, border-color .2s ease;
    }

    .certificate-table tbody tr.certificate-row:hover > td {
        background: var(--tblr-bg-surface-secondary, #f8fafc);
    }

    .certificate-card-shell,
    .certificate-card-shell > .card-body:first-child,
    .certificate-filter-shell,
    .certificate-filter-shell .learning-record-filter-row,
    .certificate-filter-shell .learning-record-fast-filter,
    .certificate-quick-dropdowns {
        overflow: visible !important;
    }

    .certificate-card-shell {
        position: relative;
    }

    .certificate-card-shell > .card-body:first-child,
    .certificate-filter-shell {
        position: relative;
        z-index: 20;
    }

    .certificate-quick-dropdowns .dropdown {
        position: relative;
    }

    .certificate-filter-shell .dropdown-menu {
        min-width: 10rem;
        z-index: 1085;
    }

    .certificate-filter-shell .dropdown-menu.show {
        top: 100% !important;
        right: auto !important;
        bottom: auto !important;
        left: 0 !important;
        transform: translateY(.25rem) !important;
    }

    .certificate-filter-value {
        display: inline-block;
        min-width: 1.5rem;
        color: inherit;
        opacity: 1;
        vertical-align: baseline;
        visibility: visible;
    }

    .certificate-quick-dropdowns .dropdown-toggle.active {
        color: var(--tblr-primary, #206bc4);
        border-color: var(--tblr-primary, #206bc4);
        background: rgba(32, 107, 196, .08);
    }

    .certificate-quick-dropdowns .dropdown-toggle.active .certificate-filter-value {
        color: inherit;
        background: transparent;
    }

    .certificate-number {
        color: var(--tblr-primary, #206bc4);
        text-decoration: none;
        white-space: nowrap;
    }

    .certificate-title {
        display: -webkit-box;
        margin-top: .2rem;
        overflow: hidden;
        color: var(--tblr-body-color, #1f2937);
        font-weight: 600;
        line-height: 1.35;
        overflow-wrap: anywhere;
        word-break: normal;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
    }

    .certificate-participant {
        display: flex;
        align-items: center;
        gap: .35rem;
        margin-top: .35rem;
        line-height: 1.2;
    }

    .certificate-participant i {
        flex: 0 0 auto;
        font-size: 1rem;
    }

    .certificate-type-badge,
    .certificate-status-badge {
        display: inline-flex;
        min-height: 28px;
        align-items: center;
        justify-content: center;
        padding: 5px 10px;
        border-radius: 7px;
        line-height: 1;
        text-transform: uppercase;
    }

    .certificate-type-badge {
        min-width: 82px;
    }

    .certificate-status-badge {
        min-width: 98px;
    }

    .certificate-playlist-badge {
        cursor: help;
    }

    .certificate-copy-btn {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .certificate-copy-btn span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .certificate-issued-date {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        line-height: 1.2;
        white-space: nowrap;
    }

    .certificate-issued-date strong {
        margin-top: .15rem;
        color: var(--tblr-secondary, #626976);
        font-size: .78rem;
        font-weight: 600;
    }

    .certificate-action-dropdown {
        display: inline-block;
    }

    .certificate-action-trigger {
        display: inline-flex;
        min-height: 34px;
        align-items: center;
        gap: .35rem;
        justify-content: center;
        width: 102px;
        white-space: nowrap;
    }

    .certificate-action-menu {
        min-width: 230px;
        padding: .4rem;
        border-radius: .75rem;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .16);
        z-index: 1080;
    }

    .certificate-action-menu .dropdown-item {
        display: flex;
        width: 100%;
        max-width: none;
        min-height: 38px;
        align-items: center;
        gap: .65rem;
        padding: .55rem .7rem;
        border-radius: .5rem;
        font-weight: 500;
        transition: color .15s ease, background-color .15s ease;
    }

    .certificate-action-menu .dropdown-item i {
        width: 1.1rem;
        flex: 0 0 auto;
        color: var(--tblr-secondary, #626976);
        font-size: 1.05rem;
    }

    .certificate-action-menu .dropdown-item:hover,
    .certificate-action-menu .dropdown-item:focus {
        background: var(--tblr-bg-surface-secondary, #f1f5f9);
        color: var(--tblr-primary, #206bc4);
    }

    .certificate-action-menu .dropdown-item:hover i,
    .certificate-action-menu .dropdown-item:focus i {
        color: currentColor;
    }

    .certificate-sort-heading {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        font-weight: 600;
        cursor: pointer;
    }

    .certificate-sort-heading i {
        color: #94a3b8;
        opacity: .65;
    }

    .certificate-sort-heading .certificate-sort-icon {
        width: 1rem;
        height: 1rem;
        flex: 0 0 auto;
        font-size: .9rem;
        line-height: 1;
        vertical-align: middle;
    }

    .certificate-sort-heading.is-sorted,
    .certificate-sort-heading.is-sorted i {
        color: var(--tblr-primary, #206bc4);
        opacity: 1;
    }

    .certificate-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--tblr-border-color, #e6e7e9);
    }

    .certificate-pagination nav > div:first-child {
        display: none !important;
    }

    .certificate-pagination nav > div:last-child > div:first-child {
        display: none;
    }

    .certificate-pagination .pagination {
        margin: 0;
    }

    .certificate-empty-state {
        display: flex;
        min-height: 360px;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 3rem 1.5rem;
    }

    .certificate-empty-icon {
        display: flex;
        width: 90px;
        height: 90px;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.25rem;
    }

    .certificate-empty-icon i {
        font-size: 42px;
    }

    .certificate-filter-actions {
        margin-left: auto;
    }

    .certificate-filter-modal {
        display: flex;
        max-height: calc(100vh - 2rem);
        flex-direction: column;
        overflow: hidden;
        border: 0;
        border-radius: 1.1rem;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .22);
    }

    #certificateMoreFiltersModal .modal-dialog {
        width: calc(100% - 2rem);
        max-width: 960px;
    }

    .certificate-filter-modal-header {
        position: sticky;
        top: 0;
        z-index: 3;
        flex: 0 0 auto;
        align-items: center;
        padding: 1.15rem 1.5rem;
        border-bottom: 1px solid var(--tblr-border-color, #e6e7e9);
        background: var(--tblr-bg-surface, #fff);
    }

    .certificate-filter-modal-header .modal-title {
        color: var(--tblr-body-color, #1f2937);
        font-size: 1.05rem;
        font-weight: 700;
    }

    .certificate-filter-modal-icon {
        display: inline-flex;
        width: 42px;
        height: 42px;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        margin-right: .8rem;
        border-radius: .75rem;
        background: var(--tblr-primary-lt, #e9f2ff);
        color: var(--tblr-primary, #206bc4);
        font-size: 1.2rem;
    }

    .certificate-more-filters {
        display: block;
        min-height: 0;
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 1.5rem;
        scrollbar-gutter: stable;
    }

    .certificate-filter-section {
        padding: 1.25rem;
        border: 1px solid var(--tblr-border-color, #e6e7e9);
        border-radius: .9rem;
        background: var(--tblr-bg-surface, #fff);
    }

    .certificate-filter-section + .certificate-filter-section {
        margin-top: 1.25rem;
    }

    .certificate-filter-section-heading {
        display: flex;
        align-items: center;
        gap: .55rem;
        margin-bottom: 1.25rem;
        padding-bottom: .75rem;
        border-bottom: 1px solid var(--tblr-border-color, #e6e7e9);
    }

    .certificate-filter-section-heading i {
        color: var(--tblr-primary, #206bc4);
        font-size: 1.05rem;
    }

    .certificate-filter-section-heading h6 {
        margin: 0;
        color: var(--tblr-body-color, #1f2937);
        font-size: .82rem;
        font-weight: 700;
        letter-spacing: .025em;
        text-transform: uppercase;
    }

    .certificate-filter-grid {
        row-gap: 1.5rem;
    }

    .certificate-filter-field .form-label {
        margin-bottom: .5rem;
        color: var(--tblr-body-color, #1f2937);
        font-size: .8rem;
        font-weight: 600;
        letter-spacing: .015em;
        text-transform: uppercase;
    }

    .certificate-filter-field .form-control,
    .certificate-filter-field .form-select {
        height: 44px;
        min-height: 44px;
        padding-top: .6rem;
        padding-bottom: .6rem;
        border-radius: .65rem;
        font-size: .875rem;
        transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .certificate-filter-field .form-control::placeholder {
        color: #94a3b8;
        opacity: 1;
    }

    .certificate-filter-field .form-control:focus,
    .certificate-filter-field .form-select:focus {
        border-color: rgba(32, 107, 196, .55);
        box-shadow: 0 0 0 .2rem rgba(32, 107, 196, .12);
    }

    .certificate-date-input,
    .certificate-date-input .form-control {
        height: 44px;
    }

    .certificate-date-input .input-icon-addon {
        display: flex;
        height: 44px;
        align-items: center;
        justify-content: center;
        color: #64748b;
    }

    .certificate-filter-modal-footer {
        position: sticky;
        bottom: 0;
        z-index: 3;
        flex: 0 0 auto;
        gap: .65rem;
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--tblr-border-color, #e6e7e9);
        background: var(--tblr-bg-surface, #fff);
    }

    .certificate-filter-modal-footer .btn {
        display: inline-flex;
        min-width: 112px;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        border-radius: .65rem;
        font-weight: 600;
    }

    #certificateMoreFiltersModal.fade .modal-dialog {
        transform: translateY(12px) scale(.985);
        transition: transform .22s ease-out, opacity .22s ease-out;
    }

    #certificateMoreFiltersModal.show .modal-dialog {
        transform: translateY(0) scale(1);
    }

    .certificate-dropdown-scroll {
        max-height: 260px;
        overflow-y: auto;
    }

    .certificate-playlist-popover {
        min-width: 230px;
    }

    .certificate-playlist-popover hr {
        margin: .5rem 0;
    }

    .certificate-playlist-popover ol {
        max-height: 240px;
        margin: 0;
        padding-left: 1.25rem;
        overflow-y: auto;
    }

    .certificate-playlist-popover li {
        padding: .25rem 0;
    }

    @media (max-width: 991.98px) {
        .certificate-filter-actions {
            width: 100%;
            margin-left: 0;
        }

    }

    @media (max-width: 767.98px) {
        .certificate-quick-dropdowns .dropdown {
            flex: 1 1 calc(50% - .5rem);
        }

        .certificate-quick-dropdowns .dropdown-toggle {
            justify-content: space-between;
            width: 100%;
        }

        .certificate-pagination {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 575.98px) {
        #certificateMoreFiltersModal .modal-dialog {
            width: auto;
            margin: .75rem;
        }

        .certificate-filter-modal {
            max-height: calc(100vh - 1.5rem);
        }

        .certificate-filter-modal-header,
        .certificate-filter-modal-footer {
            padding-right: 1rem;
            padding-left: 1rem;
        }

        .certificate-more-filters {
            padding: 1rem;
        }

        .certificate-filter-section {
            padding: 1rem;
        }

        .certificate-filter-modal-footer .btn {
            min-width: 104px;
        }
    }

    [data-bs-theme="dark"] .certificate-table thead th,
    body.dark-mode .certificate-table thead th {
        background: #152235;
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .certificate-table tbody tr.certificate-row:hover > td,
    body.dark-mode .certificate-table tbody tr.certificate-row:hover > td {
        background: #192d45;
    }

    [data-bs-theme="dark"] .certificate-title,
    body.dark-mode .certificate-title {
        color: #f1f5f9;
    }

    [data-bs-theme="dark"] .certificate-filter-modal,
    body.dark-mode .certificate-filter-modal {
        background: #152235;
        color: #f1f5f9;
    }

    [data-bs-theme="dark"] .certificate-filter-modal-header,
    [data-bs-theme="dark"] .certificate-filter-modal-footer,
    [data-bs-theme="dark"] .certificate-filter-section,
    body.dark-mode .certificate-filter-modal-header,
    body.dark-mode .certificate-filter-modal-footer,
    body.dark-mode .certificate-filter-section {
        background: #152235;
        border-color: rgba(255, 255, 255, .08);
    }

    [data-bs-theme="dark"] .certificate-filter-section-heading,
    body.dark-mode .certificate-filter-section-heading {
        border-color: rgba(255, 255, 255, .08);
    }

    [data-bs-theme="dark"] .certificate-filter-modal-header .modal-title,
    [data-bs-theme="dark"] .certificate-filter-section-heading h6,
    [data-bs-theme="dark"] .certificate-filter-field .form-label,
    body.dark-mode .certificate-filter-modal-header .modal-title,
    body.dark-mode .certificate-filter-section-heading h6,
    body.dark-mode .certificate-filter-field .form-label {
        color: #f1f5f9;
    }

    [data-bs-theme="dark"] .certificate-filter-modal-icon,
    body.dark-mode .certificate-filter-modal-icon {
        background: rgba(59, 130, 246, .14);
        color: #93c5fd;
    }

    [data-bs-theme="dark"] .certificate-filter-modal .form-control,
    [data-bs-theme="dark"] .certificate-filter-modal .form-select,
    body.dark-mode .certificate-filter-modal .form-control,
    body.dark-mode .certificate-filter-modal .form-select {
        background: #192d45;
        border-color: rgba(255, 255, 255, .08);
        color: #f1f5f9;
    }

    [data-bs-theme="dark"] .certificate-filter-modal .form-control::placeholder,
    body.dark-mode .certificate-filter-modal .form-control::placeholder {
        color: #718096;
    }

    [data-bs-theme="dark"] .certificate-date-input .input-icon-addon,
    body.dark-mode .certificate-date-input .input-icon-addon {
        color: #94a3b8;
    }

    [data-bs-theme="dark"] .certificate-filter-shell .dropdown-menu,
    [data-bs-theme="dark"] .certificate-action-menu,
    body.dark-mode .certificate-filter-shell .dropdown-menu,
    body.dark-mode .certificate-action-menu {
        background: #192d45;
        border-color: rgba(255, 255, 255, .09);
        color: #f1f5f9;
    }

    [data-bs-theme="dark"] .certificate-quick-dropdowns .dropdown-toggle.active,
    body.dark-mode .certificate-quick-dropdowns .dropdown-toggle.active {
        color: #93c5fd;
        border-color: rgba(147, 197, 253, .65);
        background: rgba(59, 130, 246, .16);
    }

    [data-bs-theme="dark"] .certificate-filter-shell .dropdown-item,
    [data-bs-theme="dark"] .certificate-action-menu .dropdown-item,
    body.dark-mode .certificate-filter-shell .dropdown-item,
    body.dark-mode .certificate-action-menu .dropdown-item {
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .certificate-filter-shell .dropdown-item:hover,
    [data-bs-theme="dark"] .certificate-filter-shell .dropdown-item:focus,
    [data-bs-theme="dark"] .certificate-action-menu .dropdown-item:hover,
    [data-bs-theme="dark"] .certificate-action-menu .dropdown-item:focus,
    body.dark-mode .certificate-filter-shell .dropdown-item:hover,
    body.dark-mode .certificate-filter-shell .dropdown-item:focus,
    body.dark-mode .certificate-action-menu .dropdown-item:hover,
    body.dark-mode .certificate-action-menu .dropdown-item:focus {
        background: #213a57;
        color: #93c5fd;
    }
</style>
