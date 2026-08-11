@props([
    'active' => 'ongoing',
])

<div class="card border-0 shadow-sm rounded-4 mb-2 learning-tab-card">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2" role="tablist">
            <button class="btn {{ $active === 'ongoing' ? 'btn-primary' : 'btn-white' }} learning-tab-btn btn-learning-tab"
                    data-target="#tab-ongoing"
                    type="button"
                    role="tab"
                    aria-selected="{{ $active === 'ongoing' ? 'true' : 'false' }}">
                <i class="ti ti-player-play"></i>
                <span>Learning On Going</span>
            </button>

            <button class="btn {{ $active === 'record' ? 'btn-primary' : 'btn-white' }} learning-tab-btn btn-learning-tab"
                    data-target="#tab-record"
                    type="button"
                    role="tab"
                    aria-selected="{{ $active === 'record' ? 'true' : 'false' }}">
                <i class="ti ti-history"></i>
                <span>Learning Record</span>
            </button>

            <button class="btn {{ $active === 'assessment' ? 'btn-primary' : 'btn-white' }} learning-tab-btn btn-learning-tab"
                    data-target="#tab-assessment"
                    type="button"
                    role="tab"
                    aria-selected="{{ $active === 'assessment' ? 'true' : 'false' }}">
                <i class="ti ti-clipboard-check"></i>
                <span>Assessment</span>
            </button>

            {{-- <button class="btn btn-white learning-tab-btn" data-target="#tab-claim" type="button">
                Klaim Learning Hour
            </button> --}}

            <button class="btn {{ $active === 'certificate' ? 'btn-primary' : 'btn-white' }} learning-tab-btn btn-learning-tab"
                    data-target="#tab-certificate"
                    type="button"
                    role="tab"
                    aria-selected="{{ $active === 'certificate' ? 'true' : 'false' }}">
                <i class="ti ti-certificate"></i>
                <span>Certificate</span>
            </button>

            {{-- <button class="btn btn-white learning-tab-btn" data-target="#tab-monitoring" type="button">
                Monitoring Progress
            </button> --}}
        </div>
    </div>
</div>
