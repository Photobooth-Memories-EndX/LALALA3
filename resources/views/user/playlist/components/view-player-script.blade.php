<script>
    document.addEventListener("DOMContentLoaded", function() {
        const playerElement = document.querySelector('#player');

        if (playerElement && typeof Plyr !== 'undefined') {
            @if (($content->type == 'Video') || ($content->type == 'YouTube'))
                @if (($content_user->progress ?? 0) >= 100)
                    const player = new Plyr('#player');
                @else
                    const player = new Plyr('#player', {
                        listeners: {
                            seek(e) {
                                e.preventDefault();
                                return false;
                            }
                        }
                    });
                @endif

                player.on('loadeddata', function() {
                    let progression = $('#player_progress').val();
                    let durasi = $('#player_duration').val();
                    let currentPlayerTime = (progression * durasi) / 100;

                    if (currentPlayerTime < durasi) {
                        player.currentTime = currentPlayerTime;
                    }
                });

                player.on('pause', function() {
                    updateLearningProgress(player.currentTime, $('#player_duration').val());
                });
            @endif
        }

        const playlistSearchInput = document.getElementById('playlistSearchInput');

        if (playlistSearchInput) {
            playlistSearchInput.addEventListener('keyup', function() {
                const keyword = this.value.toLowerCase();
                const lessons = document.querySelectorAll('.playlist-lesson-item');

                lessons.forEach(function(item) {
                    const title = item.getAttribute('data-title') || '';
                    item.style.display = title.includes(keyword) ? 'flex' : 'none';
                });
            });
        }
    });

    function updateLearningProgress(currentTime, duration) {
        $.ajax({
            type: "PUT",
            url: $('#player_paused').data('url'),
            data: {
                current_time: currentTime,
                duration: duration,
                _token: '{{ csrf_token() }}'
            },
            success: function(data) {
                console.log(data.success);
            }
        });
    }

    function markContentComplete() {
        let duration = $('#player_duration').val() || 100;

        updateLearningProgress(duration, duration);

        setTimeout(function() {
            window.location.reload();
        }, 700);
    }
</script>