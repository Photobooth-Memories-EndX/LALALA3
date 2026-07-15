@props([
    'assessments',
])

<div class="table-responsive">
    <table class="table table-vcenter table-hover card-table mb-0 assessment-table" id="assessmentTable">
        <thead>
            <tr>
                @foreach (['No', 'Assessment', 'Datetime', 'Type', 'Batch', 'Passing Grade', 'Status'] as $heading)
                    <th class="assessment-sort-heading" data-assessment-column="{{ $loop->index }}">
                        <span class="record-sort-header">
                            <span class="record-sort-label">{{ $heading }}</span>
                            <span class="record-sort-icons" aria-hidden="true">
                                <span class="record-sort-asc">&#9650;</span>
                                <span class="record-sort-desc">&#9660;</span>
                            </span>
                        </span>
                    </th>
                @endforeach
                <th class="text-end">Action</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($assessments as $assessment)
                <x-learning.assessment.assessment-row
                    :assessment="$assessment"
                    :index="$loop->iteration"
                />
            @endforeach
        </tbody>
    </table>
</div>
