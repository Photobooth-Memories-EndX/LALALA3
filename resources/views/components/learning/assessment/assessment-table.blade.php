@props([
    'assessments',
])

<div class="table-responsive">
    <table class="table table-vcenter table-hover card-table mb-0 assessment-table" id="assessmentTable">
        <thead>
            <tr>
                @php
                    $headers = [
                        ['label' => 'NO', 'class' => 'text-center align-middle', 'style' => 'width: 50px;'],
                        ['label' => 'ASSESSMENT', 'class' => 'text-start align-middle', 'style' => ''],
                        ['label' => 'TYPE', 'class' => 'text-center align-middle', 'style' => 'width: 155px;'],
                        ['label' => 'CATEGORY', 'class' => 'text-center align-middle', 'style' => 'width: 110px;'],
                        ['label' => 'DATE & TIME', 'class' => 'text-start align-middle', 'style' => 'width: 170px;'],
                        ['label' => 'SCORE', 'class' => 'text-center align-middle', 'style' => 'width: 95px;'],
                        ['label' => 'STATUS', 'class' => 'text-center align-middle', 'style' => 'width: 130px;'],
                    ];
                @endphp

                @foreach ($headers as $index => $header)
                    <th class="assessment-sort-heading {{ $header['class'] }}" data-assessment-column="{{ $index }}" style="{{ $header['style'] }}">
                        <span class="record-sort-header">
                            <span class="record-sort-label">{{ $header['label'] }}</span>
                            <span class="record-sort-icons" aria-hidden="true">
                                <span class="record-sort-asc">&#9650;</span>
                                <span class="record-sort-desc">&#9660;</span>
                            </span>
                        </span>
                    </th>
                @endforeach
                <th class="text-center align-middle" style="width: 135px;">ACTION</th>
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
