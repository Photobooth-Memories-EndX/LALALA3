<div class="content-filter-field content-filter-field-category">
    <select name="classification" id="contentFilterClassification"
            class="form-select content-filter-control content-auto-submit"
            aria-label="Classification">
        <option value="">Classification</option>
        @foreach ($classifications as $classification)
            <option value="{{ $classification->id }}" {{ $filterClassification == $classification->id ? 'selected' : '' }}>
                {{ $classification->classification_name }}
            </option>
        @endforeach
    </select>
</div>

<div class="content-filter-field content-filter-field-type">
    <select name="type" id="contentFilterType"
            class="form-select content-filter-control content-auto-submit"
            aria-label="Type">
        <option value="">Type</option>
        <option value="Document" {{ $filterType == 'Document' ? 'selected' : '' }}>Document</option>
        <option value="Video" {{ $filterType == 'Video' ? 'selected' : '' }}>Video</option>
        <option value="YouTube" {{ $filterType == 'YouTube' ? 'selected' : '' }}>YouTube</option>
    </select>
</div>

<div class="content-filter-field content-filter-field-month">
    <select name="month" id="contentFilterMonth"
            class="form-select content-filter-control"
            aria-label="Month">
        <option value="">Month</option>
        @foreach ($monthOptions as $value => $label)
            <option value="{{ $value }}" {{ $selectedMonth == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="content-filter-field content-filter-field-year">
    <select name="year" id="contentFilterYear"
            class="form-select content-filter-control"
            aria-label="Year">
        <option value="">Year</option>
        @foreach ($yearOptions as $year)
            <option value="{{ $year }}" {{ (string) $selectedYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
        @endforeach
    </select>
</div>
