<template data-learning-ongoing-hero>
    <x-shared.hero-learning-ongoing
        mode="mooc"
        :count="$ongoingMoocs->total()"
    />
</template>

<x-learning.ongoing-mooc-section :ongoing-moocs="$ongoingMoocs" />
