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
</style>
