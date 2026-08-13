<div class="row" id="playlistGrid">

    @foreach($trainingplan as $playlist)

        @include('user.playlist.components.playlist-card', [
            'playlist' => $playlist,
            'stats'    => $playlistStats[$playlist->id] ?? [],
        ])

    @endforeach

</div>

<div id="playlistFilterEmpty" class="d-none mt-3">
    @include('user.playlist.components.playlist-empty')
</div>
