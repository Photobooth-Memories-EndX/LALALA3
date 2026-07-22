@props([
    'certificates',
    'activeSort' => 'issued_desc',
])

@php
    $sortColumns = [
        'certificate' => ['number_asc', 'number_desc'],
        'participant' => ['participant_asc', 'participant_desc'],
        'target' => ['target_asc', 'target_desc'],
        'issued' => ['issued_asc', 'issued_desc'],
    ];
@endphp

@if ($certificates->count())
    <div class="table-responsive certificate-table-wrap">
        <table class="table table-vcenter table-hover card-table mb-0 certificate-table" id="certificateTable">
            <thead>
                <tr>
                    <th class="certificate-expand-column"><span class="sr-only">Expand</span></th>
                    <th>No</th>
                    <th>
                        <button type="button" class="certificate-sort-heading {{ in_array($activeSort, $sortColumns['certificate'], true) ? 'is-sorted' : '' }}" data-sort="{{ $activeSort === 'number_asc' ? 'number_desc' : 'number_asc' }}">
                            Certificate <i class="ti {{ $activeSort === 'number_desc' ? 'ti-chevron-down' : 'ti-chevron-up' }}"></i>
                        </button>
                    </th>
                    <th>Certificate Type</th>
                    <th>
                        <button type="button" class="certificate-sort-heading {{ in_array($activeSort, $sortColumns['target'], true) ? 'is-sorted' : '' }}" data-sort="{{ $activeSort === 'target_asc' ? 'target_desc' : 'target_asc' }}">
                            Target <i class="ti {{ $activeSort === 'target_desc' ? 'ti-chevron-down' : 'ti-chevron-up' }}"></i>
                        </button>
                    </th>
                    <th>Credential ID</th>
                    <th>
                        <button type="button" class="certificate-sort-heading {{ in_array($activeSort, $sortColumns['issued'], true) ? 'is-sorted' : '' }}" data-sort="{{ $activeSort === 'issued_asc' ? 'issued_desc' : 'issued_asc' }}">
                            Issued Date <i class="ti {{ $activeSort === 'issued_desc' ? 'ti-chevron-down' : 'ti-chevron-up' }}"></i>
                        </button>
                    </th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
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
