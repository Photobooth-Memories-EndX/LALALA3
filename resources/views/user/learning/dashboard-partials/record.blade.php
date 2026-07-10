<x-learning.record-section
    :content-user="$content_user"
    :average-progress="$averageProgress"
    :certificate-settings="$certificateSettings"
    :completed-playlists="$completedPlaylists ?? collect()"
/>
