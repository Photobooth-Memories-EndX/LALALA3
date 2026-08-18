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

    /* Generous spacing under step navigator */
    .assessment-wizard .nav {
        margin-bottom: 2.25rem !important;
    }
</style>
