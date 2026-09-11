<template data-learning-record-hero>
    <x-shared.hero-learning-record
        :stats="[
            ['label' => 'Course Completed', 'value' => number_format($recordStats['courseCompleted'] ?? 0)],
            ['label' => 'Playlist Completed', 'value' => number_format($recordStats['playlistCompleted'] ?? 0)],
            ['label' => 'Learning Hours', 'value' => number_format($recordStats['totalLearningHours'] ?? 0, 2)],
        ]"
    />
</template>

{{-- Learning Record: tabel Blade biasa; search, filter, sorting & pagination client-side (jQuery, tanpa AJAX). --}}
<x-learning.record-section :rows="$rows" :filter-options="$filterOptions" />
