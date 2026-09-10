<link href="{{ asset('css/bootstrap-toggle.min.css') }}" rel="stylesheet">

<style>
    .user-card {
        border-radius: 16px;
        overflow: hidden;
    }

    .avatar {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .user-avatar {
        width: 38px;
        height: 38px;
        min-width: 38px;
        border-radius: 12px;
        background: #edf2ff;
        color: #05507D;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .table-modern thead th {
        font-size: 11px;
        text-transform: uppercase;
        color: #6c757d;
        background: #f8fafc;
        border-bottom: 1px solid #e9ecef;
        white-space: nowrap;
        padding: 14px 12px;
    }

    .table-modern tbody td {
        vertical-align: middle;
        padding-top: 14px;
        padding-bottom: 14px;
        border-color: #eef1f4;
        font-size: 13px;
    }

    .table-modern tbody tr:hover {
        background: #f9fbfd;
    }

    .btn-white {
        background: #ffffff;
        border: 1px solid #e6e8eb;
        color: #495057;
    }

    .btn-white:hover {
        background: #f8f9fa;
    }

    .btn-icon {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 8px;
    }

    .badge-light.border {
        border-color: #e6e8eb !important;
        color: #495057;
        background: #f8f9fa;
    }

    .modal {
        z-index: 99999 !important;
    }

    .modal-backdrop {
        z-index: 99998 !important;
    }

    #overlay,
    #overlay-create-ptkp,
    #overlay-edit {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: .25rem;
        z-index: 1000;
        background: rgba(255,255,255,.85);
    }

    .modal-close-btn {
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 8px;
        background: rgba(255,255,255,.18);
        color: #fff;
        font-size: 22px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .modal-close-btn:hover {
        background: rgba(255,255,255,.28);
        color: #fff;
    }

    #modalCreatePtkp .modal-content,
    #modalEditPtkp .modal-content,
    #modalCreateOutsourcing .modal-content,
    #modalEditOutsourcing .modal-content {
        border-radius: 12px;
        overflow: hidden;
    }

    /* ==========================
       DARK MODE SUPPORT
    ========================== */
    [data-bs-theme="dark"] .card.bg-white,
    [data-theme="dark"] .card.bg-white,
    body.theme-dark .card.bg-white {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .card-header.bg-white,
    [data-bs-theme="dark"] .card-footer.bg-white,
    [data-theme="dark"] .card-header.bg-white,
    [data-theme="dark"] .card-footer.bg-white,
    body.theme-dark .card-header.bg-white,
    body.theme-dark .card-footer.bg-white {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .text-dark,
    [data-theme="dark"] .text-dark,
    body.theme-dark .text-dark {
        color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .table-modern thead th,
    [data-theme="dark"] .table-modern thead th,
    body.theme-dark .table-modern thead th {
        background: #0f172a !important;
        color: #94a3b8 !important;
        border-bottom-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .table-modern tbody td,
    [data-theme="dark"] .table-modern tbody td,
    body.theme-dark .table-modern tbody td {
        border-color: rgba(255, 255, 255, 0.08) !important;
        color: #cbd5e1 !important;
    }

    [data-bs-theme="dark"] .table-modern tbody tr:hover,
    [data-theme="dark"] .table-modern tbody tr:hover,
    body.theme-dark .table-modern tbody tr:hover {
        background: #243044 !important;
    }

    [data-bs-theme="dark"] .form-select,
    [data-bs-theme="dark"] .form-control,
    [data-theme="dark"] .form-select,
    [data-theme="dark"] .form-control,
    body.theme-dark .form-select,
    body.theme-dark .form-control {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .form-select option,
    [data-theme="dark"] .form-select option,
    body.theme-dark .form-select option {
        background-color: #0f172a;
        color: #f8fafc;
    }

    [data-bs-theme="dark"] .btn-white,
    [data-theme="dark"] .btn-white,
    body.theme-dark .btn-white {
        background: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .btn-white:hover,
    [data-theme="dark"] .btn-white:hover,
    body.theme-dark .btn-white:hover {
        background: #1e293b !important;
    }

    [data-bs-theme="dark"] .badge-light.border,
    [data-theme="dark"] .badge-light.border,
    body.theme-dark .badge-light.border {
        background: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #cbd5e1 !important;
    }

    [data-bs-theme="dark"] .modal-content,
    [data-theme="dark"] .modal-content,
    body.theme-dark .modal-content {
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .modal-footer.bg-light,
    [data-theme="dark"] .modal-footer.bg-light,
    body.theme-dark .modal-footer.bg-light {
        background-color: #0f172a !important;
        border-top-color: rgba(255, 255, 255, 0.1) !important;
    }

    .filter-label-head {
        color: #1e293b;
    }

    [data-bs-theme="dark"] .filter-label-head,
    [data-theme="dark"] .filter-label-head,
    body.theme-dark .filter-label-head {
        color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .empty-title,
    [data-theme="dark"] .empty-title,
    body.theme-dark .empty-title {
        color: #f1f5f9 !important;
    }
</style>
