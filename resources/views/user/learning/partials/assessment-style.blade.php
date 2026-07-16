<style>
    .assessment-table {
        min-width: 900px;
    }

    .assessment-filter-shell .dropdown-menu {
        min-width: 10rem;
    }

    .assessment-filter-actions {
        margin-left: auto;
    }

    .assessment-filter-modal {
        border: 0;
        border-radius: 1rem;
        overflow: hidden;
    }

    .assessment-more-filters {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0;
        max-height: calc(100vh - 220px);
        padding: 0 1.5rem;
        overflow-y: auto;
    }

    .assessment-filter-section {
        min-width: 0;
        padding: 1.25rem 1rem;
        border-bottom: 1px solid var(--tblr-border-color, #e6e7e9);
    }

    .assessment-filter-section:nth-child(odd) {
        padding-left: 0;
        border-right: 1px solid var(--tblr-border-color, #e6e7e9);
    }

    .assessment-filter-section:nth-child(even) {
        padding-right: 0;
    }

    .assessment-filter-title {
        margin-bottom: .75rem;
        color: var(--tblr-body-color, #1f2937);
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .assessment-filter-options {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem 1rem;
    }

    .assessment-filter-options .form-check {
        min-width: 5.5rem;
        margin: 0;
    }

    .assessment-datatable-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--tblr-border-color, #e6e7e9);
        background: var(--tblr-bg-surface, #fff);
    }

    .assessment-datatable-footer .dataTables_info,
    .assessment-datatable-footer .dataTables_paginate {
        padding-top: 0;
    }

    .assessment-datatable-footer .pagination {
        margin: 0;
    }

    .assessment-table th,
    .assessment-table td {
        vertical-align: middle;
    }

    .assessment-table tbody tr.assessment-row {
        cursor: pointer;
        transition: background-color .2s ease;
    }

    .assessment-table tbody tr.assessment-row > td {
        transition: background-color .2s ease, border-color .2s ease;
    }

    .assessment-table tbody tr.assessment-row:hover {
        --tblr-table-accent-bg: var(--tblr-bg-surface-secondary, #f8fafc);
    }

    .assessment-table tbody tr.assessment-row:hover > td {
        background-color: var(--tblr-bg-surface-secondary, #f8fafc);
    }

    .assessment-title {
        color: var(--tblr-body-color, #1f2937);
        line-height: 1.25;
        transition: color .2s ease, text-decoration-color .2s ease;
        text-decoration: underline;
        text-decoration-color: transparent;
        text-underline-offset: 3px;
    }

    .assessment-title-link,
    .assessment-title-link:visited {
        display: inline-block;
        color: var(--tblr-body-color, #1f2937);
        cursor: pointer;
    }

    .assessment-row:hover .assessment-title-link,
    .assessment-title-link:hover,
    .assessment-title-link:focus {
        color: var(--tblr-primary, #206bc4);
        text-decoration-color: currentColor;
    }

    .assessment-subtitle {
        margin-top: 2px;
        line-height: 1.2;
    }

    .assessment-type-badge,
    .assessment-status-badge {
        min-height: 24px;
        padding: 4px 9px;
    }

    .assessment-type-badge {
        min-width: 68px;
    }

    .assessment-status-badge {
        min-width: 82px;
    }

    .assessment-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 92px;
        min-height: 30px;
        transition: color .2s ease, background-color .2s ease, border-color .2s ease;
    }

    .assessment-sort-heading {
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }

    .assessment-table.dataTable thead th.assessment-sort-heading::before,
    .assessment-table.dataTable thead th.assessment-sort-heading::after {
        display: none !important;
    }

    .assessment-sort-heading .record-sort-icons {
        opacity: .8;
        transition: color .2s ease, opacity .2s ease;
    }

    .assessment-sort-heading:hover .record-sort-icons {
        color: #64748b;
        opacity: 1;
    }

    .assessment-sort-heading.is-sorted {
        color: var(--tblr-primary, #206bc4);
    }

    .assessment-sort-heading.is-sorted .record-sort-icons span:not(.active) {
        display: none;
    }

    .assessment-sort-heading .record-sort-icons span.active {
        color: var(--tblr-primary, #206bc4);
    }

    @media (max-width: 767.98px) {
        .assessment-table {
            min-width: 860px;
        }

        .assessment-quick-dropdowns .dropdown {
            flex: 1 1 calc(50% - .5rem);
        }

        .assessment-quick-dropdowns .dropdown-toggle {
            justify-content: space-between;
            width: 100%;
        }

        .assessment-datatable-footer {
            align-items: flex-start;
            flex-direction: column;
        }

        .assessment-more-filters {
            display: block;
            padding: 0 1rem;
        }

        .assessment-filter-section,
        .assessment-filter-section:nth-child(odd),
        .assessment-filter-section:nth-child(even) {
            padding: 1rem 0;
            border-right: 0;
        }
    }
</style>
