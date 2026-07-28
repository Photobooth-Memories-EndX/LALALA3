@props([
    'certificates',
    'activeSort' => 'issued_desc',
])

@php
    $sortColumns = [
        'no' => ['id_asc', 'id_desc'],
        'certificate' => ['target_asc', 'target_desc'],
        'type' => ['type_asc', 'type_desc'],
        'number' => ['number_asc', 'number_desc'],
        'issued' => ['issued_asc', 'issued_desc'],
    ];
    $noSortDirection = $activeSort === 'id_asc'
        ? 'asc'
        : ($activeSort === 'id_desc' ? 'desc' : null);
    $certificateSortDirection = $activeSort === 'target_asc'
        ? 'asc'
        : ($activeSort === 'target_desc' ? 'desc' : null);
    $typeSortDirection = $activeSort === 'type_asc'
        ? 'asc'
        : ($activeSort === 'type_desc' ? 'desc' : null);
    $numberSortDirection = $activeSort === 'number_asc'
        ? 'asc'
        : ($activeSort === 'number_desc' ? 'desc' : null);
    $issuedSortDirection = $activeSort === 'issued_asc'
        ? 'asc'
        : ($activeSort === 'issued_desc' ? 'desc' : null);
@endphp

<style>
    .certificate-table {
        width: 100% !important;
        min-width: 0;
        table-layout: fixed;
    }

    .certificate-table th,
    .certificate-table td {
        padding-right: 10px;
        padding-left: 10px;
    }

    .certificate-table .certificate-col-no {
        width: 4%;
        padding-right: 4px;
        padding-left: 4px;
    }

    .certificate-table .certificate-col-main {
        width: 26%;
    }

    .certificate-table .certificate-col-type {
        width: 8%;
    }

    .certificate-table .certificate-col-number {
        width: 19%;
    }

    .certificate-table .certificate-col-credential {
        width: 17%;
    }

    .certificate-table .certificate-col-issued {
        width: 10%;
    }

    .certificate-table .certificate-col-action {
        width: 16%;
        padding-right: 4px;
        padding-left: 4px;
    }

    .certificate-table tbody td {
        height: 92px;
    }

    .certificate-title {
        line-height: 1.3;
        -webkit-line-clamp: 2;
    }

    .certificate-participant {
        min-width: 0;
        margin-top: .25rem;
    }

    .certificate-participant span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .certificate-type-badge {
        min-width: 0;
        padding: 4px 7px;
        white-space: nowrap;
    }

    .certificate-identifier {
        display: inline-flex;
        width: 100%;
        gap: 4px;
        white-space: nowrap;
    }

    .certificate-identifier-value {
        font-size: .8rem;
    }

    .certificate-identifier-copy {
        width: 1.25rem;
        height: 1.25rem;
        flex-basis: 1.25rem;
        font-size: .85rem;
    }

    .certificate-actions {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }

    .certificate-action-button {
        display: inline-flex;
        width: 30px;
        height: 30px;
        min-width: 30px;
        min-height: 30px;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid var(--tblr-border-color, #dce1e7);
        border-radius: 6px;
        background: var(--tblr-bg-surface, #fff);
        box-shadow: none;
        font-size: 1rem;
        line-height: 1;
    }

    .certificate-action-button:hover,
    .certificate-action-button:focus {
        border-color: currentColor;
        background: var(--tblr-bg-surface-secondary, #f8fafc);
        box-shadow: none;
    }

    .certificate-col-no .certificate-sort-heading {
        gap: 0;
    }

    .certificate-col-no .certificate-sort-heading .certificate-sort-icon {
        width: .75rem;
        flex-basis: .75rem;
        font-size: .75rem;
    }

    @media (max-width: 991.98px) {
        .certificate-table {
            min-width: 940px;
        }
    }
</style>

@if ($certificates->count())
    <div class="table-responsive certificate-table-wrap">
        <table class="table table-vcenter table-hover card-table mb-0 certificate-table" id="certificateTable">
            <thead>
                <tr>
                    <th class="certificate-col-no text-center">
                        <button type="button" class="certificate-sort-heading {{ in_array($activeSort, $sortColumns['no'], true) ? 'is-sorted' : '' }}" data-sort="{{ $activeSort === 'id_asc' ? 'id_desc' : 'id_asc' }}">
                            NO
                            <i class="ti certificate-sort-icon {{ $noSortDirection === 'asc' ? 'ti-arrow-up' : ($noSortDirection === 'desc' ? 'ti-arrow-down' : 'ti-arrows-sort') }}" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th class="certificate-col-main">
                        <button type="button" class="certificate-sort-heading {{ in_array($activeSort, $sortColumns['certificate'], true) ? 'is-sorted' : '' }}" data-sort="{{ $activeSort === 'target_asc' ? 'target_desc' : 'target_asc' }}">
                            CERTIFICATE
                            <i class="ti certificate-sort-icon {{ $certificateSortDirection === 'asc' ? 'ti-arrow-up' : ($certificateSortDirection === 'desc' ? 'ti-arrow-down' : 'ti-arrows-sort') }}" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th class="certificate-col-type text-center">
                        <button type="button" class="certificate-sort-heading {{ in_array($activeSort, $sortColumns['type'], true) ? 'is-sorted' : '' }}" data-sort="{{ $activeSort === 'type_asc' ? 'type_desc' : 'type_asc' }}">
                            TYPE
                            <i class="ti certificate-sort-icon {{ $typeSortDirection === 'asc' ? 'ti-arrow-up' : ($typeSortDirection === 'desc' ? 'ti-arrow-down' : 'ti-arrows-sort') }}" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th class="certificate-col-number">
                        <button type="button" class="certificate-sort-heading {{ in_array($activeSort, $sortColumns['number'], true) ? 'is-sorted' : '' }}" data-sort="{{ $activeSort === 'number_asc' ? 'number_desc' : 'number_asc' }}">
                            CERTIFICATE NUMBER
                            <i class="ti certificate-sort-icon {{ $numberSortDirection === 'asc' ? 'ti-arrow-up' : ($numberSortDirection === 'desc' ? 'ti-arrow-down' : 'ti-arrows-sort') }}" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th class="certificate-col-credential">CREDENTIAL ID</th>
                    <th class="certificate-col-issued text-center">
                        <button type="button" class="certificate-sort-heading {{ in_array($activeSort, $sortColumns['issued'], true) ? 'is-sorted' : '' }}" data-sort="{{ $activeSort === 'issued_asc' ? 'issued_desc' : 'issued_asc' }}">
                            ISSUED DATE
                            <i class="ti certificate-sort-icon {{ $issuedSortDirection === 'asc' ? 'ti-arrow-up' : ($issuedSortDirection === 'desc' ? 'ti-arrow-down' : 'ti-arrows-sort') }}" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th class="certificate-col-action text-center">ACTION</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($certificates as $certificate)
                    <x-learning.certificate.certificate-row
                        :certificate="$certificate"
                        :index="($certificates->firstItem() ?? 1) + $loop->index"
                    />
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="certificate-pagination">
        <div class="text-muted small">
            Showing {{ $certificates->firstItem() }} to {{ $certificates->lastItem() }} of {{ $certificates->total() }} certificates
        </div>
        {{ $certificates->onEachSide(1)->links() }}
    </div>
@else
    <div class="certificate-empty-state">
        <span class="avatar avatar-xl bg-blue-lt text-blue certificate-empty-icon"><i class="ti ti-certificate"></i></span>
        <h3 class="mb-2">{{ collect(request()->all())->keys()->contains(fn ($key) => str_starts_with($key, 'certificate_')) ? 'No Matching Certificates' : 'Belum ada Certificate.' }}</h3>
        <p class="text-muted text-center mb-0">
            {{ collect(request()->all())->keys()->contains(fn ($key) => str_starts_with($key, 'certificate_'))
                ? 'Try changing or resetting the active filters.'
                : 'Selesaikan Learning dan Assessment untuk mendapatkan Certificate.' }}
        </p>
    </div>
@endif
