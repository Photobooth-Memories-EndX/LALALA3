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
        color: #206bc4;
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
    }

    .table-modern tbody td {
        vertical-align: middle;
        padding-top: 12px;
        padding-bottom: 12px;
        border-color: #eef1f4;
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
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
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
</style>