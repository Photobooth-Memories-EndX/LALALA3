<style>
    /* OWL Profile Page Custom Responsive Styles */
    .my-profile-card {
        border-radius: 12px;
        border: 1px solid var(--tblr-border-color, #e6e7e9);
        background: var(--tblr-bg-surface, #ffffff);
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    /* Reset Tabler negative margin on transparent list group for Settings sidebar */
    .owl-profile-sidebar-nav.list-group-transparent {
        margin: 0 !important;
    }

    /* Mobile Tabs */
    .owl-profile-mobile-tabs {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .owl-profile-mobile-tabs::-webkit-scrollbar {
        display: none;
    }

    .owl-profile-mobile-tabs .nav-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border-radius: 20px;
        background-color: var(--tblr-bg-surface-secondary, #f8fafc);
        color: var(--tblr-body-color, #475569);
        border: 1px solid var(--tblr-border-color, #e2e8f0);
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.5rem 0.875rem;
        transition: all 0.2s ease;
    }

    .owl-profile-mobile-tabs .nav-link.active {
        background-color: var(--tblr-primary, #05507d);
        color: #ffffff !important;
        border-color: var(--tblr-primary, #05507d);
    }

    .owl-profile-mobile-tabs .nav-link .icon-nav {
        width: 18px;
        min-width: 18px;
        height: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1rem;
    }

    /* Desktop Sidebar Nav Alignment & Spacing Fix */
    .owl-profile-sidebar-nav .list-group-item {
        display: flex;
        align-items: center;
        gap: 0.625rem; /* 10px spacing between icon and text */
        padding: 0.625rem 0.875rem !important; /* 10px top/bottom, 14px left/right */
        border: none;
        border-radius: 8px !important;
        color: var(--tblr-body-color, #475569);
        font-weight: 500;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        background: transparent;
    }

    .owl-profile-sidebar-nav .list-group-item .icon-nav {
        width: 20px;
        min-width: 20px;
        height: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.15rem;
        margin-right: 0 !important;
        color: var(--tblr-muted, #8a8d8f);
        transition: color 0.2s ease;
    }

    .owl-profile-sidebar-nav .list-group-item:hover {
        background-color: var(--tblr-bg-surface-secondary, #f1f5f9) !important;
        color: var(--tblr-primary, #05507d);
    }

    .owl-profile-sidebar-nav .list-group-item:hover .icon-nav {
        color: var(--tblr-primary, #05507d) !important;
    }

    .owl-profile-sidebar-nav .list-group-item.active {
        background-color: var(--tblr-primary-lt, rgba(5, 80, 125, 0.1)) !important;
        color: var(--tblr-primary, #05507d) !important;
        font-weight: 600;
    }

    .owl-profile-sidebar-nav .list-group-item.active .icon-nav {
        color: var(--tblr-primary, #05507d) !important;
    }

    /* Avatar Box & Photo Styling */
    .my-profile-avatar-box {
        background-color: var(--tblr-bg-surface-secondary, #f8fafc);
        border: 1px solid var(--tblr-border-color, #e2e8f0);
        border-radius: 12px;
        padding: 1.25rem;
    }

    .my-profile-avatar-img {
        width: 88px;
        height: 88px;
        object-fit: cover;
        border-radius: 50%;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        border: 3px solid var(--tblr-bg-surface, #ffffff);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-position: center;
        background-size: cover;
    }

    /* Actions footer mobile */
    @media (max-width: 575.98px) {
        .my-profile-actions {
            display: flex !important;
            width: 100%;
            gap: 10px;
        }
        .my-profile-actions .btn {
            flex: 1 1 50%;
            width: 50%;
            text-align: center;
            justify-content: center;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        
        .my-profile-avatar-box {
            padding: 1rem;
        }
    }

    /* Dark mode compatibility */
    [data-bs-theme="dark"] .my-profile-card,
    [data-theme="dark"] .my-profile-card,
    body.dark-mode .my-profile-card {
        background-color: var(--tblr-bg-surface, #182433);
        border-color: var(--tblr-border-color, #25384d);
    }

    [data-bs-theme="dark"] .owl-profile-mobile-tabs .nav-link,
    [data-theme="dark"] .owl-profile-mobile-tabs .nav-link,
    body.dark-mode .owl-profile-mobile-tabs .nav-link {
        background-color: var(--tblr-bg-surface-secondary, #1f2d3d);
        color: var(--tblr-body-color, #dce6f1);
        border-color: var(--tblr-border-color, #25384d);
    }

    [data-bs-theme="dark"] .my-profile-avatar-box,
    [data-theme="dark"] .my-profile-avatar-box,
    body.dark-mode .my-profile-avatar-box {
        background-color: var(--tblr-bg-surface-secondary, #1f2d3d);
        border-color: var(--tblr-border-color, #25384d);
    }

    [data-bs-theme="dark"] .my-profile-avatar-img,
    [data-theme="dark"] .my-profile-avatar-img,
    body.dark-mode .my-profile-avatar-img {
        border-color: var(--tblr-bg-surface, #182433);
    }

    [data-bs-theme="dark"] .owl-profile-sidebar-nav .list-group-item,
    [data-theme="dark"] .owl-profile-sidebar-nav .list-group-item,
    body.dark-mode .owl-profile-sidebar-nav .list-group-item {
        color: var(--tblr-body-color, #dce6f1);
    }

    [data-bs-theme="dark"] .owl-profile-sidebar-nav .list-group-item:hover,
    [data-theme="dark"] .owl-profile-sidebar-nav .list-group-item:hover,
    body.dark-mode .owl-profile-sidebar-nav .list-group-item:hover {
        background-color: rgba(255, 255, 255, 0.06) !important;
        color: #ffffff;
    }

    [data-bs-theme="dark"] .owl-profile-sidebar-nav .list-group-item.active,
    [data-theme="dark"] .owl-profile-sidebar-nav .list-group-item.active,
    body.dark-mode .owl-profile-sidebar-nav .list-group-item.active {
        background-color: rgba(255, 255, 255, 0.12) !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .owl-profile-sidebar-nav .list-group-item.active .icon-nav,
    [data-theme="dark"] .owl-profile-sidebar-nav .list-group-item.active .icon-nav,
    body.dark-mode .owl-profile-sidebar-nav .list-group-item.active .icon-nav {
        color: #ffffff !important;
    }
</style>
