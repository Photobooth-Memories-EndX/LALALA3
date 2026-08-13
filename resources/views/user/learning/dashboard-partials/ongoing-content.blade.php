<template data-learning-ongoing-hero>
    <x-shared.hero-learning-ongoing
        mode="content"
        :count="$ongoingContents->total()"
    />
</template>

<x-learning.ongoing-content-section :ongoing-contents="$ongoingContents" />
