<template data-learning-assessment-hero>
    <x-shared.hero-learning-assessment :stats="$assessmentStats" />
</template>

<x-learning.assessment.assessment-section :assessments="$assessments" :filter-options="$filterOptions" />
