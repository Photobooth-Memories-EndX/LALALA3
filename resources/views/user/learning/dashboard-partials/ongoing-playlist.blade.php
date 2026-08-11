<template data-learning-ongoing-hero>
    <x-shared.hero-learning-ongoing
        mode="playlist"
        :count="$ongoingPlaylists->total()"
    />
</template>

<x-learning.ongoing-playlist-section :ongoing-playlists="$ongoingPlaylists" />
