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
                    <th class="certificate-col-main text-start">
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
                    <th class="certificate-col-number text-start">
                        <button type="button" class="certificate-sort-heading {{ in_array($activeSort, $sortColumns['number'], true) ? 'is-sorted' : '' }}" data-sort="{{ $activeSort === 'number_asc' ? 'number_desc' : 'number_asc' }}">
                            CERTIFICATE NUMBER
                            <i class="ti certificate-sort-icon {{ $numberSortDirection === 'asc' ? 'ti-arrow-up' : ($numberSortDirection === 'desc' ? 'ti-arrow-down' : 'ti-arrows-sort') }}" aria-hidden="true"></i>
                        </button>
                    </th>
                    <th class="certificate-col-credential text-start">CREDENTIAL ID</th>
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

    <x-certificate.hover-preview />
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
