@php
    $vpContentFile = json_decode($content->file ?? 'null');
    $vpGetContentFile = is_array($vpContentFile) ? ($vpContentFile[0] ?? '') : '';
    $vpIsDocument = in_array($content->type, ['Document', 'SOP/Regulation']);
@endphp

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const progressTracker = new OwlLearningProgress({
            url: "{{ route('content.user.progress.update') }}",
            csrf: "{{ csrf_token() }}",
            contentId: {{ (int) $content->id }},
            type: '{{ $vpIsDocument ? 'document' : 'video' }}',
            onUpdate: window.owlUpdateLearningUi
        });

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
                    // Resume: prefer the exact saved position, fall back to the
                    // legacy progress-percentage estimate.
                    let resumeSeconds = {{ (int) ($content_user->current_time ?? 0) }};

                    if (!resumeSeconds) {
                        let progression = $('#player_progress').val();
                        let durasi = $('#player_duration').val();
                        resumeSeconds = (progression * durasi) / 100;
                    }

                    if (resumeSeconds > 0 && resumeSeconds < player.duration) {
                        player.currentTime = resumeSeconds;
                    }
                });

                player.on('loadedmetadata', function() {
                    if (player.duration) {
                        progressTracker.send({ duration_seconds: player.duration }, true);
                    }
                });

                player.on('play', function() {
                    progressTracker.send({
                        status: 'in_progress',
                        current_time: player.currentTime,
                        duration_seconds: player.duration
                    }, true);
                });

                player.on('timeupdate', function() {
                    if (!player.duration) return;

                    progressTracker.send({
                        current_time: player.currentTime,
                        duration_seconds: player.duration
                    });
                });

                player.on('pause', function() {
                    if (player.ended) return;

                    progressTracker.send({
                        status: 'paused',
                        current_time: player.currentTime,
                        duration_seconds: player.duration
                    }, true);
                });

                player.on('ended', function() {
                    progressTracker.send({
                        status: 'completed',
                        current_time: player.duration,
                        duration_seconds: player.duration
                    }, true);
                });
            @endif
        }

        @if ($vpIsDocument && $vpGetContentFile)
            if (typeof PDFjsViewer !== 'undefined' && document.querySelector('.playlist-pdfjs-viewer')) {
                var pdfjsLib = window["pdfjs-dist/build/pdf"];
                pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/pdf.worker.min.js') }}";

                let fileName = "{{ $vpGetContentFile }}";
                let fileNameFixed = fileName
                    .replaceAll(' ', '%20')
                    .replaceAll('&amp;', '&');
                let pdfFile = "{{ asset('storage/files/content/' . $content->id) }}/" + fileNameFixed;
                var playlistPdfReader = new OwlPdfReader({
                    shellId: 'playlist-pdf-shell',
                    viewerSelector: '.playlist-pdfjs-viewer',
                    downloadButtonId: 'playlist-pdf-download-btn',
                    fullscreenButtonId: 'playlist-pdf-fs-btn',
                    fullscreenIconId: 'playlist-pdf-fs-icon',
                    readingModeButtonId: 'playlist-pdf-mode-btn',
                    readingModeIconId: 'playlist-pdf-mode-icon',
                    readingModeLabelId: 'playlist-pdf-mode-label'
                });

                window.playlistPdfViewer = new PDFjsViewer(
                    $(".playlist-pdfjs-viewer"),
                    playlistPdfReader.getViewerOptions({
                        onDocumentReady: function() {
                            var totalPages = playlistPdfViewer.getPageCount();

                            // Resume: prefer the exact saved page, fall back to the
                            // legacy progress-percentage estimate.
                            var lastPage = {{ (int) ($content_user->last_page ?? 0) }};

                            if (!lastPage) {
                                var progression = {{ (int) ($content_user->progress ?? 0) }};
                                lastPage = parseInt((progression * totalPages) / 100);
                            }

                            if (lastPage > 1 && lastPage < totalPages) {
                                playlistPdfViewer.scrollToPage(lastPage);
                            }

                            progressTracker.send({ total_pages: totalPages }, true);
                        },

                        onActivePageChanged: function(page, pageno) {
                            progressTracker.sendDebounced({
                                last_page: pageno,
                                total_pages: this.getPageCount()
                            });
                        }
                    }));

                playlistPdfReader.attach(playlistPdfViewer, pdfFile);

                window.playlistPdfRotateLeft = function() {
                    return playlistPdfReader.rotate(-90);
                };
                window.playlistPdfRotateRight = function() {
                    return playlistPdfReader.rotate(90);
                };
                window.playlistPdfToggleReadingMode = function() {
                    return playlistPdfReader.toggleReadingMode();
                };
                window.playlistPdfDoPrint = function() {
                    return playlistPdfReader.print();
                };
                window.playlistPdfToggleFullscreen = function() {
                    return playlistPdfReader.toggleFullscreen();
                };

                playlistPdfViewer.loadDocument(pdfFile).then(function() {
                    playlistPdfViewer.setZoom('width');
                });
            }
        @endif

        const playlistSearchInput = document.getElementById('playlistSearchInput');

        if (playlistSearchInput) {
            playlistSearchInput.addEventListener('keyup', function() {
                const keyword = this.value.toLowerCase();
                const lessons = document.querySelectorAll('.playlist-lesson-item');

                lessons.forEach(function(item) {
                    const title = item.getAttribute('data-title') || '';
                    item.style.display = title.includes(keyword) ? '' : 'none';
                });
            });
        }
    });

    // Legacy manual progress endpoint — kept as the fallback flow for the
    // "Mark as Complete" button.
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


    async function shareContent() {
        const shareData = {
            title: document.title,
            text: 'Lihat materi pembelajaran ini',
            url: window.location.href
        };

        try {
            if (navigator.share) {
                await navigator.share(shareData);
            } else {
                await navigator.clipboard.writeText(window.location.href);

                if (window.Toastify) {
                    Toastify({
                        text: "Link berhasil disalin",
                        duration: 3000,
                        gravity: "top",
                        position: "right"
                    }).showToast();
                } else {
                    alert('Link berhasil disalin');
                }
            }
        } catch (err) {
            console.log(err);
        }
    }

</script>
