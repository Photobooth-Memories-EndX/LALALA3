<div class="row" id="playlistGrid">

    @foreach($trainingplan as $playlist)

        @include('user.playlist.components.playlist-card', [
            'playlist' => $playlist
        ])

    @endforeach

</div>
