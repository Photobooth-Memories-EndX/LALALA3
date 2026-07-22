<style>
    .certificate-table {
        min-width: 1180px;
    }

    .certificate-table-wrap {
        max-height: 620px;
    }

    .certificate-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--tblr-bg-surface, #fff);
        white-space: nowrap;
    }

    .certificate-table th,
    .certificate-table td {
        vertical-align: middle;
    }

    .certificate-table tbody tr.certificate-row > td {
        transition: background-color .2s ease, border-color .2s ease;
    }

    .certificate-table tbody tr.certificate-row:hover > td {
        background: var(--tblr-bg-surface-secondary, #f8fafc);
    }

    .certificate-expand-column {
        width: 42px;
    }

    .certificate-number {
        color: var(--tblr-primary, #206bc4);
        text-decoration: none;
        white-space: nowrap;
    }

    .certificate-title,
    .certificate-target {
        max-width: 280px;
        margin-top: .2rem;
        color: var(--tblr-body-color, #1f2937);
    }

    .certificate-target {
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .certificate-type-badge,
    .certificate-status-badge {
        min-height: 25px;
        padding: 5px 10px;
    }

    .certificate-playlist-badge {
        cursor: help;
    }

    .certificate-copy-btn {
        display: inline-flex;
        align-items: center;
        max-width: 190px;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .certificate-copy-btn span {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .certificate-actions .btn {
        min-height: 32px;
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

    .certificate-detail-child {
        padding: 0 !important;
        background: var(--tblr-bg-surface-secondary, #f8fafc);
    }

    .certificate-detail-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        padding: 1.25rem 1.5rem 1.5rem 4.5rem;
        border-left: 3px solid var(--tblr-primary, #206bc4);
        animation: certificate-detail-in .2s ease;
    }

    .certificate-detail-item {
        min-width: 0;
    }

    .certificate-detail-item > span {
        display: block;
        margin-bottom: .25rem;
        color: var(--tblr-secondary, #626976);
        font-size: .75rem;
    }

    .certificate-detail-item strong,
    .certificate-detail-item a {
        display: block;
        overflow-wrap: anywhere;
    }

    .certificate-detail-wide {
        grid-column: span 2;
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

    @keyframes certificate-detail-in {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 991.98px) {
        .certificate-filter-actions {
            width: 100%;
            margin-left: 0;
        }

        .certificate-detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding-left: 1.5rem;
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

        .certificate-more-filters,
        .certificate-detail-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .certificate-detail-wide {
            grid-column: auto;
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
    [data-bs-theme="dark"] .certificate-detail-child,
    body.dark-mode .certificate-table tbody tr.certificate-row:hover > td,
    body.dark-mode .certificate-detail-child {
        background: #192d45;
    }

    [data-bs-theme="dark"] .certificate-title,
    [data-bs-theme="dark"] .certificate-target,
    body.dark-mode .certificate-title,
    body.dark-mode .certificate-target {
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
</style>
