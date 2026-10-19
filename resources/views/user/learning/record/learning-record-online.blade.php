<template data-learning-record-hero>
    <x-shared.hero-learning-record
        :stats="[
            ['label' => 'Course Completed', 'value' => number_format($recordStats['courseCompleted'] ?? 0)],
            ['label' => 'Playlist Completed', 'value' => number_format($recordStats['playlistCompleted'] ?? 0)],
            ['label' => 'Learning Hours', 'value' => number_format($recordStats['totalLearningHours'] ?? 0, 2)],
        ]"
    />
</template>

<div class="learning-record-shell">
    {{-- Learning Record Online: tabel Blade existing; search, filter, sorting & pagination client-side --}}
    <x-learning.record-section :rows="$rows ?? collect()" :filter-options="$filterOptions ?? []" />
</div>
