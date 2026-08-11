<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 col-xl-3 mb-2">
                <label class="form-label">Search</label>
                <input id="certificate-search" type="search" class="form-control" placeholder="Name, assessment, issuer...">
            </div>
            @foreach([
                'type' => ['Type', ['all' => 'All', 'content' => 'Content', 'playlist' => 'Playlist', 'global' => 'Global']],
                'status' => ['Status', ['all' => 'All', '1' => 'ON', '0' => 'OFF']],
                'assessment-required' => ['Assessment Required', ['all' => 'All', '1' => 'Required', '0' => 'Optional']],
                'issuer' => ['Issuer', ['all' => 'All', 'OWL Training Management' => 'OWL Training Management', 'PT Krakatau Posco' => 'PT Krakatau Posco', 'HR Training Management' => 'HR Training Management']],
                'minimum-progress' => ['Min Progress', ['all' => 'All', '80' => '80%', '90' => '90%', '100' => '100%']],
                'minimum-score' => ['Min Score', ['all' => 'All', '70' => '70', '80' => '80', '90' => '90', '100' => '100']],
            ] as $id => [$label, $options])
                <div class="col-md-4 col-xl-3 mb-2">
                    <label class="form-label">{{ $label }}</label>
                    <select id="certificate-{{ $id }}" class="form-control certificate-filter">
                        @foreach($options as $value => $text)
                            <option value="{{ $value }}">{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="col-md-4 col-xl-3 mb-2 d-flex align-items-end">
                <button id="certificate-filter-reset" type="button" class="btn btn-outline-secondary w-100">
                    <i class="ti ti-refresh mr-1"></i> Reset Filter
                </button>
            </div>
        </div>
    </div>
</div>
