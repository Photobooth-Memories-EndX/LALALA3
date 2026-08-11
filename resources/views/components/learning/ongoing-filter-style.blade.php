{{-- Shared modal helpers retained for the content search filter, which also uses this component. --}}
<style>
    .learning-filter-group { margin-bottom: 0; }
    .learning-filter-modal .form-check { margin-bottom: 0; }

    @media (max-width: 575.98px) {
        .learning-filter-modal {
            position: fixed;
            right: 0;
            bottom: 0;
            left: 0;
            max-width: 100%;
            margin: 0;
        }

        .learning-filter-modal .modal-content {
            max-height: 88vh;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
    }
</style>
