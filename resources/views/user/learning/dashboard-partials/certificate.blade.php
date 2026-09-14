<template data-learning-certificate-hero>
    <x-shared.hero-learning-certificate
        :stats="[
            ['label' => 'Total Certificate', 'value' => number_format(data_get($certificateStats, 'total', 0))],
            ['label' => 'Playlist Certificate', 'value' => number_format(data_get($certificateStats, 'playlist', 0))],
            ['label' => 'Content Certificate', 'value' => number_format(data_get($certificateStats, 'content', 0))],
        ]"
    />
</template>

<x-learning.certificate-section
    :user-certificates="$userCertificates"
    :filter-options="$filterOptions"
/>
