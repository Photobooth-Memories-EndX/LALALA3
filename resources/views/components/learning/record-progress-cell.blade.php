@props(['row'])

<div class="learning-record-progress">
    <div class="percent">{{ (int) $row['progress'] }}%</div>
</div>
