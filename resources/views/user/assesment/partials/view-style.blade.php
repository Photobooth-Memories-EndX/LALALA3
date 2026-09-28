<link href="{{ asset('css/smart_wizard.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('css/smart_wizard_dots.css') }}" rel="stylesheet" type="text/css" />

<style>
    .rounded-4 {
        border-radius: 1rem !important;
    }

    .assessment-question {
        font-size: 1.05rem;
        line-height: 1.7;
        font-weight: 600;
    }

    #smartwizard table tr td {
        border: 1px solid #000 !important;
        padding: 0.2rem;
    }

    .assessment-wizard .form-selectgroup-label {
        border: 1px solid rgba(98, 105, 118, .18);
        transition: .2s ease;
    }

    .assessment-wizard .form-selectgroup-label:hover {
        border-color: var(--tblr-primary);
        background: rgba(32, 107, 196, .04);
    }

    .sw-theme-dots > .nav .nav-link.error.done {
        color: #FF8C85 !important;
    }

    .sw-theme-dots > .nav .nav-link.error.done::after {
        background-color: #FF8C85 !important;
    }

    @media (max-width: 768px) {
        .sticky-top {
            position: relative !important;
            top: auto !important;
        }
    }

    /* Layout spacing: Safe clearance at bottom for fixed toolbars (e.g. Debugbar/extensions) */
    .page-body {
        padding-bottom: 90px !important;
    }

    /* Generous spacing & flex layout under step navigator */
    .assessment-wizard .nav {
        display: flex !important;
        flex-wrap: wrap !important;
        padding-left: 0 !important;
        margin-top: 0 !important;
        margin-bottom: 2.25rem !important;
        gap: 0px !important;
    }

    .assessment-wizard .nav .nav-item {
        flex: 0 0 auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .assessment-wizard .nav .nav-link {
        display: block !important;
        position: relative !important;
        width: 36px !important;
        min-width: 36px !important;
        height: 1px !important;
        padding: 0 !important;
        margin-top: 40px !important;
        margin-bottom: 6px !important;
    }

    .assessment-wizard .nav .nav-link::after {
        width: 35px !important;
        height: 35px !important;
        border-width: 3px !important;
        top: -40px !important;
        left: 0 !important;
        right: 0 !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }

    .assessment-wizard .nav .nav-link > .num {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        white-space: nowrap !important;
        font-size: 0.725rem !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        top: -40px !important;
        height: 35px !important;
        left: 0 !important;
        right: 0 !important;
        margin-left: auto !important;
        margin-right: auto !important;
        width: 100% !important;
        text-align: center !important;
    }

    /* Modal Body Text Color */
    .assessment-modal-body {
        color: #212529;
    }

    /* =========================================================================
       Dark Mode Styles: Leave & Finish Assessment Modals
       ========================================================================= */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #leaveAssessmentModal .modal-content,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #finishAssessmentModal .modal-content {
        background-color: var(--tblr-bg-surface, #152238) !important;
        border: 1px solid var(--tblr-border-color, rgba(148, 163, 184, 0.16)) !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #leaveAssessmentModal .assessment-modal-body,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #finishAssessmentModal .assessment-modal-body {
        color: var(--owl-text-secondary, #cbd5e1) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #leaveAssessmentModal .assessment-modal-body strong,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #finishAssessmentModal .assessment-modal-body strong {
        color: var(--owl-text, #ffffff) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #leaveAssessmentModal .btn-close,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #finishAssessmentModal .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #leaveAssessmentModal .btn-light,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #finishAssessmentModal .btn-light {
        background-color: var(--tblr-bg-surface-secondary, #223247) !important;
        border-color: var(--tblr-border-color, #33465f) !important;
        color: #cbd5e1 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #leaveAssessmentModal .btn-light:hover,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #finishAssessmentModal .btn-light:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }

    /* =========================================================================
       Dark Mode Styles: AFK Alert Banner & Indicators
       ========================================================================= */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #afkAlertBanner.alert-warning {
        background-color: rgba(245, 159, 0, 0.12) !important;
        border: 1px solid rgba(245, 159, 0, 0.3) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #afkAlertBanner .afk-icon-box {
        background-color: rgba(245, 159, 0, 0.2) !important;
        border: 1px solid rgba(245, 159, 0, 0.35) !important;
        color: #fbbf24 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #afkAlertBanner .afk-status-title {
        color: #fef3c7 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #afkAlertBanner .afk-status-desc {
        color: #fed7aa !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #afkAlertBanner #afkElapsedCounter {
        background-color: rgba(245, 159, 0, 0.25) !important;
        border: 1px solid rgba(245, 159, 0, 0.5) !important;
        color: #fef08a !important;
    }

    /* Dark Mode Table Border in questions */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #smartwizard table tr td {
        border-color: var(--tblr-border-color, rgba(148, 163, 184, 0.2)) !important;
    }

    /* =========================================================================
       Dark Mode Styles: Header Dropdown Menus (Profile & Notifications)
       ========================================================================= */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-menu {
        background-color: var(--tblr-bg-surface, #152238) !important;
        background: var(--tblr-bg-surface, #152238) !important;
        border: 1px solid var(--tblr-border-color, rgba(148, 163, 184, 0.16)) !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45) !important;
        color: var(--tblr-body-color, #f1f5f9) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-menu-arrow:before {
        background: var(--tblr-bg-surface, #152238) !important;
        border-color: var(--tblr-border-color, rgba(148, 163, 184, 0.16)) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-header {
        color: var(--tblr-muted, #94a3b8) !important;
        border-bottom: 1px solid var(--tblr-border-color, rgba(148, 163, 184, 0.12)) !important;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 0.5rem 0.875rem;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-menu .dropdown-item {
        background-color: transparent !important;
        background: transparent !important;
        color: var(--owl-text, #f1f5f9) !important;
        border: 0 !important;
        text-align: left;
        width: 100%;
        display: flex;
        align-items: center;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-menu .dropdown-item :is(i, .ti) {
        color: var(--tblr-muted, #94a3b8) !important;
        transition: color 0.15s ease;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-menu .dropdown-item:hover,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-menu .dropdown-item:focus {
        background-color: var(--tblr-bg-surface-secondary, #1a2942) !important;
        background: var(--tblr-bg-surface-secondary, #1a2942) !important;
        color: #ffffff !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-menu .dropdown-item:hover :is(i, .ti),
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-menu .dropdown-item:focus :is(i, .ti) {
        color: #38bdf8 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-menu .dropdown-item.text-muted {
        color: var(--tblr-muted, #94a3b8) !important;
        background-color: transparent !important;
        background: transparent !important;
        cursor: default;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-menu .dropdown-item.text-muted:hover {
        background-color: transparent !important;
        background: transparent !important;
        color: var(--tblr-muted, #94a3b8) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) :is(.app-topbar, .owl-assessment-mobile-actions) .dropdown-menu .dropdown-divider {
        border-color: var(--tblr-border-color, rgba(148, 163, 184, 0.14)) !important;
        margin: 0.25rem 0;
    }
</style>
