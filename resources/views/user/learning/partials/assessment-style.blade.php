<style>
    .assessment-table {
        min-width: 900px;
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
    }
</style>
