<style>
.page-body {
    background: #fff;
}
[data-bs-theme="dark"] .page-body,
body.dark-mode .page-body,
body.theme-dark .page-body {
    background: var(--tblr-body-bg, #111827);
}

/* Content Hero Card & Icon */
.content-hero-card {
    background-color: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    transition: background-color 0.2s ease, border-color 0.2s ease;
}
.content-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(15, 94, 156, 0.1);
    color: #0f5e9c;
    display: flex;
    align-items: center;
    justify-content: center;
}
[data-bs-theme="dark"] .content-hero-card,
body.dark-mode .content-hero-card,
body.theme-dark .content-hero-card {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .content-hero-icon,
body.dark-mode .content-hero-icon,
body.theme-dark .content-hero-icon {
    background: rgba(106, 174, 214, 0.15) !important;
    color: #6aaed6 !important;
}

/* Content Stat Cards */
.content-stat-card {
    background-color: #ffffff;
    border-radius: 1rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}
.content-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08) !important;
}
[data-bs-theme="dark"] .content-stat-card,
body.dark-mode .content-stat-card,
body.theme-dark .content-stat-card {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] #moreContentFiltersModal .modal-content,
body.dark-mode #moreContentFiltersModal .modal-content,
body.theme-dark #moreContentFiltersModal .modal-content {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #moreContentFiltersModal .text-dark,
body.dark-mode #moreContentFiltersModal .text-dark,
body.theme-dark #moreContentFiltersModal .text-dark {
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #moreContentFiltersModal .form-control,
[data-bs-theme="dark"] #moreContentFiltersModal .form-select,
body.dark-mode #moreContentFiltersModal .form-control,
body.dark-mode #moreContentFiltersModal .form-select,
body.theme-dark #moreContentFiltersModal .form-control,
body.theme-dark #moreContentFiltersModal .form-select {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #modalSyncLearningHours .modal-content,
[data-bs-theme="dark"] #modalTotalPageSynchronization .modal-content,
[data-bs-theme="dark"] #contentDetailModal .modal-content,
body.dark-mode #modalSyncLearningHours .modal-content,
body.dark-mode #modalTotalPageSynchronization .modal-content,
body.dark-mode #contentDetailModal .modal-content,
body.theme-dark #modalSyncLearningHours .modal-content,
body.theme-dark #modalTotalPageSynchronization .modal-content,
body.theme-dark #contentDetailModal .modal-content {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #modalSyncLearningHours .modal-footer,
[data-bs-theme="dark"] #modalTotalPageSynchronization .modal-footer,
[data-bs-theme="dark"] #contentDetailModal .modal-footer,
body.dark-mode #modalSyncLearningHours .modal-footer,
body.dark-mode #modalTotalPageSynchronization .modal-footer,
body.dark-mode #contentDetailModal .modal-footer,
body.theme-dark #modalSyncLearningHours .modal-footer,
body.theme-dark #modalTotalPageSynchronization .modal-footer,
body.theme-dark #contentDetailModal .modal-footer {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
}
[data-bs-theme="dark"] #modalSyncLearningHours .bg-light,
[data-bs-theme="dark"] #modalTotalPageSynchronization .bg-light,
[data-bs-theme="dark"] #modalTotalPageSynchronization .bg-light-subtle,
[data-bs-theme="dark"] #contentDetailModal .bg-light,
[data-bs-theme="dark"] #contentDetailModal .bg-white,
body.dark-mode #modalSyncLearningHours .bg-light,
body.dark-mode #modalTotalPageSynchronization .bg-light,
body.dark-mode #modalTotalPageSynchronization .bg-light-subtle,
body.dark-mode #contentDetailModal .bg-light,
body.dark-mode #contentDetailModal .bg-white,
body.theme-dark #modalSyncLearningHours .bg-light,
body.theme-dark #modalTotalPageSynchronization .bg-light,
body.theme-dark #modalTotalPageSynchronization .bg-light-subtle,
body.theme-dark #contentDetailModal .bg-light,
body.theme-dark #contentDetailModal .bg-white {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
[data-bs-theme="dark"] #modalSyncLearningHours .text-dark,
[data-bs-theme="dark"] #modalTotalPageSynchronization .text-dark,
[data-bs-theme="dark"] #contentDetailModal .text-dark,
body.dark-mode #modalSyncLearningHours .text-dark,
body.dark-mode #modalTotalPageSynchronization .text-dark,
body.dark-mode #contentDetailModal .text-dark,
body.theme-dark #modalSyncLearningHours .text-dark,
body.theme-dark #modalTotalPageSynchronization .text-dark,
body.theme-dark #contentDetailModal .text-dark {
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #modalTotalPageSynchronization .list-group-item,
body.dark-mode #modalTotalPageSynchronization .list-group-item,
body.theme-dark #modalTotalPageSynchronization .list-group-item {
    color: #cbd5e1 !important;
}
[data-theme="dark"] .card,
body.theme-dark .card {
    background: var(--tblr-bg-surface, #1f2937);
}
.manage-content-search-container {
    position: relative;
    width: 100%;
}
#manage-content-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 2000;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    border: 1px solid #e2e8f0;
    background: #ffffff;
    overflow: hidden;
}
#manage-content-autocomplete-list {
    max-height: 280px;
    overflow-y: auto;
    background-color: #ffffff;
    scrollbar-width: thin;
}
#manage-content-search-footer {
    background-color: #f8f9fa;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
}
#manage-content-search-results .dropdown-item {
    padding: .6rem .85rem;
    transition: background-color .15s ease;
    text-decoration: none;
}
#manage-content-search-results .dropdown-item:hover,
#manage-content-search-results .dropdown-item:focus {
    background-color: rgba(15, 94, 156, .08);
}
#manage-content-search-results .dropdown-item:not(:last-child) {
    border-bottom: 1px solid rgba(0, 0, 0, .05);
}
[data-bs-theme="dark"] #manage-content-search-results,
body.dark-mode #manage-content-search-results,
body.theme-dark #manage-content-search-results {
    background: var(--tblr-bg-surface, #1f2937) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
}
[data-bs-theme="dark"] #manage-content-autocomplete-list,
body.dark-mode #manage-content-autocomplete-list,
body.theme-dark #manage-content-autocomplete-list {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
}
[data-bs-theme="dark"] #manage-content-search-footer,
body.dark-mode #manage-content-search-footer,
body.theme-dark #manage-content-search-footer {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-top-color: rgba(255, 255, 255, 0.1) !important;
}
[data-bs-theme="dark"] #manage-content-search-results .dropdown-item,
body.dark-mode #manage-content-search-results .dropdown-item,
body.theme-dark #manage-content-search-results .dropdown-item {
    color: #f8fafc;
}
[data-bs-theme="dark"] #manage-content-search-results .dropdown-item:not(:last-child),
body.dark-mode #manage-content-search-results .dropdown-item:not(:last-child),
body.theme-dark #manage-content-search-results .dropdown-item:not(:last-child) {
    border-bottom-color: rgba(255, 255, 255, 0.08);
}
[data-bs-theme="dark"] #manage-content-search-results .dropdown-item:hover,
[data-bs-theme="dark"] #manage-content-search-results .dropdown-item:focus,
body.dark-mode #manage-content-search-results .dropdown-item:hover,
body.dark-mode #manage-content-search-results .dropdown-item:focus,
body.theme-dark #manage-content-search-results .dropdown-item:hover,
body.theme-dark #manage-content-search-results .dropdown-item:focus {
    background: rgba(106, 174, 214, 0.16);
}
[data-bs-theme="dark"] #manage-content-search-results .dropdown-item .text-dark,
body.dark-mode #manage-content-search-results .dropdown-item .text-dark,
body.theme-dark #manage-content-search-results .dropdown-item .text-dark {
    color: #f8fafc !important;
}
</style>
