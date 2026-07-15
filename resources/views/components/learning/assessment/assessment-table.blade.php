@props([
    'assessments',
])

<div class="table-responsive">
    <table class="table table-vcenter card-table" id="assessmentTable">
        <thead>
            <tr>
                <th>No</th>
                <th>Assessment</th>
                <th>Datetime</th>
                <th>Type</th>
                <th>Batch</th>
                <th>Passing Grade</th>
                <th>Status</th>
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
