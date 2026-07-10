@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
    .learning-section-padding {
        padding: 1.25rem;
    }

    .learning-dashboard-container {
        max-width: 1500px;
        margin: auto;
    }

    .learning-hero {
        background: linear-gradient(135deg, #0d6efd 0%, #1e40af 100%);
        border-radius: 1.5rem;
        color: #fff;
    }

    .learning-mini-stat {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        color: #fff;
        backdrop-filter: blur(10px);
    }

    .learning-mini-stat-label {
        font-size: .75rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        opacity: .8;
        margin-bottom: .35rem;
    }

    .learning-mini-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
    }

    /* 4 card statistik hero: grid 2x2, glass effect, tinggi seragam, tanpa overflow */
    .learning-hero-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        align-items: stretch;
    }

    .learning-hero-stats .learning-mini-stat {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: .5rem;
        text-align: center;
        padding: 1.25rem 1rem;
    }

    .learning-hero-stats .learning-mini-stat-label {
        font-size: .75rem;
        opacity: .7;
    }

    .learning-hero-stats .learning-mini-stat-value {
        font-size: 2rem;
        font-weight: 700;
    }

    @media (max-width: 575.98px) {
        .learning-hero-cta {
            width: 100%;
        }
    }

    @media (max-width: 419.98px) {
        .learning-hero-stats {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    .learning-filter-card .form-control,
    .learning-filter-card .form-select {
        min-height: 42px;
        border-radius: .9rem;
    }

    .learning-filter-card .form-label {
        font-weight: 600;
    }

    /* Tombol Filter/Reset sejajar & sama tinggi dengan input di baris filter */
    .learning-filter-actions .btn {
        min-height: 42px;
        white-space: nowrap;
    }

    .learning-modern-card {
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .learning-modern-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.08) !important;
    }

    .learning-thumb {
        height: 180px;
        object-fit: cover;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
    }

    .learning-placeholder {
        height: 180px;
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .learning-progress-bar {
        height: .7rem;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .learning-progress-bar .progress-bar {
        border-radius: 999px;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .learning-card-title {
        min-height: calc(1.4em * 2);
    }

    .rounded-4 {
        border-radius: 1rem !important;
    }

    .learning-tab-btn {
        white-space: nowrap;
    }

    .text-white-75 {
        color: rgba(255, 255, 255, .75) !important;
    }

    /* Tabel Learning Record: compact, fit di card tanpa horizontal scroll di desktop */
    .learning-record-table {
        table-layout: fixed;
        width: 100%;
        font-size: 12px;
    }

    .learning-record-table th,
    .learning-record-table td {
        padding: 8px 8px !important;
        vertical-align: middle;
        white-space: normal;
        overflow-wrap: break-word;
    }

    .learning-record-title {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.35;
    }

    .learning-record-progress {
        line-height: 1.2;
    }

    .learning-record-progress .percent {
        font-weight: 600;
        color: #1f2937;
    }

    .learning-record-progress .minimum {
        font-size: 10px;
        color: #16a34a;
    }

    .learning-record-progress .minimum.is-failed {
        color: #dc2626;
    }

    .learning-record-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .learning-record-cert-btn {
        padding: 4px 10px;
        font-size: 11px;
        border-radius: 999px;
        white-space: nowrap;
    }

    /* Header tabel Learning Record tetap terlihat saat scroll (sticky). */
    .learning-record-scroll {
        max-height: 65vh;
        overflow-y: auto;
    }

    .learning-record-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--tblr-bg-surface, #fff);
        box-shadow: inset 0 -1px 0 var(--tblr-border-color, #e6e7e9);
    }

    /* Mobile: fixed layout dilepas agar table-responsive bisa scroll horizontal */
    @media (max-width: 767.98px) {
        .learning-record-table {
            table-layout: auto;
            min-width: 900px;
        }
    }

    .pagination {
        gap: .4rem;
    }

    .page-item .page-link {
        border-radius: 12px !important;
        border: 1px solid #dbe4f0;
        color: #1e40af;
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .page-item.active .page-link {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .25);
    }

    .page-item .page-link:hover {
        background: #eff6ff;
    }
</style>
@endsection