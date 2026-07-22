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
        overflow: hidden;
        border: 0;
        border-radius: 1rem;
    }

    .certificate-more-filters {
        display: grid;
        max-height: calc(100vh - 220px);
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem 1.25rem;
        overflow-y: auto;
        padding: 1.5rem;
    }

    .certificate-filter-field .form-label {
        font-size: .8rem;
        font-weight: 700;
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

        .certificate-more-filters {
            grid-template-columns: minmax(0, 1fr);
        }

        .certificate-pagination {
            align-items: flex-start;
            flex-direction: column;
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

    [data-bs-theme="dark"] .certificate-filter-modal .form-control,
    [data-bs-theme="dark"] .certificate-filter-modal .form-select,
    body.dark-mode .certificate-filter-modal .form-control,
    body.dark-mode .certificate-filter-modal .form-select {
        background: #192d45;
        border-color: rgba(255, 255, 255, .08);
        color: #f1f5f9;
    }

    [data-bs-theme="dark"] .certificate-filter-shell .dropdown-menu,
    [data-bs-theme="dark"] .certificate-action-menu,
    body.dark-mode .certificate-filter-shell .dropdown-menu,
    body.dark-mode .certificate-action-menu {
        background: #192d45;
        border-color: rgba(255, 255, 255, .09);
        color: #f1f5f9;
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
