@php
    $vpContentFile = json_decode($content->file ?? 'null');
    $vpGetContentFile = is_array($vpContentFile) ? ($vpContentFile[0] ?? '') : '';
    $vpIsDocument = in_array($content->type, ['Document', 'SOP/Regulation']);
@endphp

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const progressTracker = new OwlLearningProgress({
            url: "{{ route('playlist.user.progress.update') }}",
            csrf: "{{ csrf_token() }}",
            contentId: {{ (int) $content->id }},
            trainingPlanId: {{ (int) ($playlist->id ?? $trainingplan->id) }},
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

                if (window.playlistWatchTimeTracker && typeof window.playlistWatchTimeTracker.destroy === 'function') {
                    window.playlistWatchTimeTracker.destroy();
                }

                const watchTimeTracker = new VideoWatchTimeTracker({
                    player: player,
                    url: "{{ route('playlist.user.progress.update') }}",
                    csrf: "{{ csrf_token() }}",
                    contentId: {{ (int) $content->id }},
                    trainingPlanId: {{ (int) ($playlist->id ?? $trainingplan->id) }},
                    maxActualWatchSeconds: {{ $content->max_actual_watch_seconds !== null ? (int) $content->max_actual_watch_seconds : 'null' }},
                    initialActualWatchSeconds: {{ (int) ($content_user->actual_watch_seconds ?? 0) }},
                    heartbeatIntervalMs: 10000,
                    onUpdate: window.owlUpdateLearningUi
                });

                window.playlistWatchTimeTracker = watchTimeTracker;

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
            @endif
        }

        @if ($vpIsDocument && $vpGetContentFile)
            if (typeof PDFjsViewer !== 'undefined' && document.querySelector('.playlist-pdfjs-viewer')) {
                var pdfjsLib = window["pdfjs-dist/build/pdf"];
                pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/pdf.worker.min.js') }}";

                if (window.playlistDocumentReadingTracker && typeof window.playlistDocumentReadingTracker.destroy === 'function') {
                    window.playlistDocumentReadingTracker.destroy();
                }

                const playlistDocumentReadingTracker = new DocumentReadingTracker({
                    url: "{{ route('playlist.user.progress.update') }}",
                    csrf: "{{ csrf_token() }}",
                    contentId: {{ (int) $content->id }},
                    trainingPlanId: {{ (int) ($playlist->id ?? $trainingplan->id) }},
                    maxActualWatchSeconds: {{ $content->max_actual_watch_seconds !== null ? (int) $content->max_actual_watch_seconds : 'null' }},
                    initialActualWatchSeconds: {{ (int) ($content_user->actual_watch_seconds ?? 0) }},
                    heartbeatIntervalMs: 10000,
                    onUpdate: window.owlUpdateLearningUi
                });

                window.playlistDocumentReadingTracker = playlistDocumentReadingTracker;

                let fileName = "{{ $vpGetContentFile }}";
                let fileNameFixed = fileName
                    .replaceAll(' ', '%20')
                    .replaceAll('&amp;', '&');
                let pdfFile = "{{ asset('storage/files/content/' . $content->id) }}/" + fileNameFixed;

                var isDocument = @json($content->type === 'Document');
                var rawDuration = @json($content->page_duration ?? ($content->duration_page ?? null));
                var rawPageDuration = rawDuration !== null && rawDuration !== undefined ? Number(rawDuration) : 30;
                var isPlaylistScrollMode = isDocument && rawPageDuration === 0;

                if (isPlaylistScrollMode) {
                    var playlistScrollReader = new DocumentScrollMode({
                        shellId: 'playlist-pdf-shell',
                        viewerSelector: '.playlist-pdfjs-viewer',
                        downloadButtonId: 'playlist-pdf-download-btn',
                        fullscreenButtonId: 'playlist-pdf-fs-btn',
                        fullscreenIconId: 'playlist-pdf-fs-icon',
                        readingModeButtonId: 'playlist-pdf-mode-btn',
                        readingModeIconId: 'playlist-pdf-mode-icon',
                        readingModeLabelId: 'playlist-pdf-mode-label',
                        previousButtonId: 'playlist-pdf-previous-page',
                        nextButtonId: 'playlist-pdf-next-page',
                        completedPages: {{ (int) ($content_user->last_page ?? 0) }},
                        contentCompleted: {{ (int) ($content_user->finish ?? 0) }} === 1,
                        totalPages: {{ (int) ($content->total_page ?? 0) }},
                        onPageProgress: function(readPage, totalPages) {
                            progressTracker.sendDebounced({
                                last_page: readPage,
                                total_pages: totalPages,
                                status: 'in_progress'
                            });
                        }
                    });

                    window.playlistPdfViewer = new PDFjsViewer(
                        $(".playlist-pdfjs-viewer"),
                        playlistScrollReader.getViewerOptions({
                            onDocumentReady: function() {
                                var totalPages = playlistPdfViewer.getPageCount();
                                var lastPage = {{ (int) ($content_user->last_page ?? 0) }};

                                if (!lastPage) {
                                    var progression = {{ (int) ($content_user->progress ?? 0) }};
                                    lastPage = parseInt((progression * totalPages) / 100);
                                }

                                var initialPage = lastPage > 1 ? Math.min(totalPages, lastPage) : 1;
                                if (initialPage > 1) {
                                    playlistScrollReader.scrollToPage(initialPage);
                                }

                                progressTracker.send({ total_pages: totalPages }, true);
                            }
                        })
                    );

                    playlistScrollReader.attach(playlistPdfViewer, pdfFile);

                    window.playlistPdfRotateLeft = function() {
                        return playlistScrollReader.rotate(-90);
                    };
                    window.playlistPdfRotateRight = function() {
                        return playlistScrollReader.rotate(90);
                    };
                    window.playlistPdfToggleReadingMode = function() {
                        return Promise.resolve();
                    };
                    window.playlistPdfDoPrint = function() {
                        return playlistScrollReader.print();
                    };
                    window.playlistPdfToggleFullscreen = function() {
                        return playlistScrollReader.toggleFullscreen();
                    };

                    playlistPdfViewer.loadDocument(pdfFile).then(function() {
                        playlistScrollReader.scheduleAutoFit(true);
                    });
                } else {
                    // EXISTING PLAYLIST DOCUMENT PAGINATION / SOP LOGIC (100% UNCHANGED)
                    var isTimedDocument = isDocument;
                    var documentPageDuration = Math.max(1, rawPageDuration || 30);
                    @php
                        $vpInitialLastPage = (int) ($content_user->last_page ?? 0);
                        if ($vpInitialLastPage <= 0 && (int) ($content_user->progress ?? 0) > 0 && (int) ($content->total_page ?? 0) > 0) {
                            $vpInitialLastPage = min((int) $content->total_page, (int) floor(((int) $content_user->progress * (int) $content->total_page) / 100));
                        }
                    @endphp
                    var playlistPdfReader = new OwlPdfReader({
                        shellId: 'playlist-pdf-shell',
                        viewerSelector: '.playlist-pdfjs-viewer',
                        downloadButtonId: 'playlist-pdf-download-btn',
                        fullscreenButtonId: 'playlist-pdf-fs-btn',
                        fullscreenIconId: 'playlist-pdf-fs-icon',
                        readingModeButtonId: 'playlist-pdf-mode-btn',
                        readingModeIconId: 'playlist-pdf-mode-icon',
                        readingModeLabelId: 'playlist-pdf-mode-label',
                        pageTimingEnabled: isTimedDocument,
                        pageDuration: documentPageDuration,
                        completedPages: {{ $vpInitialLastPage }},
                        contentCompleted: {{ (int) ($content_user->finish ?? 0) }} === 1,
                        onPageStarted: function(page, totalPages) {
                            return progressTracker.send({
                                active_page: page,
                                total_pages: totalPages,
                                status: 'in_progress'
                            }, true);
                        },
                        onPageCompleted: function(page, totalPages, elapsedSeconds) {
                            return progressTracker.send({
                                active_page: page,
                                completed_page: page,
                                page_elapsed_seconds: elapsedSeconds,
                                total_pages: totalPages,
                                status: 'in_progress'
                            }, true);
                        }
                    });

                    window.playlistPdfViewer = new PDFjsViewer(
                        $(".playlist-pdfjs-viewer"),
                        playlistPdfReader.getViewerOptions({
                            onDocumentReady: function() {
                                var totalPages = playlistPdfViewer.getPageCount();

                                var lastPage = {{ $vpInitialLastPage }};

                                if (!lastPage) {
                                    var progression = {{ (int) ($content_user->progress ?? 0) }};
                                    lastPage = parseInt((progression * totalPages) / 100);
                                }

                                var initialPage = isTimedDocument
                                    ? ({{ (int) ($content_user->finish ?? 0) }} === 1
                                        ? totalPages
                                        : Math.min(totalPages, lastPage + 1))
                                    : lastPage;

                                if (initialPage > 1 && initialPage <= totalPages) {
                                    playlistPdfViewer.scrollToPage(initialPage);
                                }

                                if (!isTimedDocument
                                    || {{ (int) ($content_user->finish ?? 0) }} === 1
                                    || lastPage >= totalPages) {
                                    progressTracker.send({ total_pages: totalPages }, true);
                                }
                            },

                            onActivePageChanged: function(page, pageno) {
                                if (isTimedDocument) return;

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
                        playlistPdfReader.scheduleAutoFit(true);
                    });
                }
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

    /* =====================================================
       PLAYLIST ASSESSMENT ACCESS MODAL HANDLER
    ===================================================== */
    $(document).on('click', '[data-assessment-trigger="true"]', function (e) {
        e.preventDefault();

        var $btn = $(this);
        if ($btn.hasClass('lesson-assessment-button')) {
            return;
        }
        var $modal = $('#assessmentAccessModal');

        var targetUrl = $btn.data('target-url') || $btn.attr('data-target-url');

        if (!$modal.length) {
            if (targetUrl) {
                window.location.href = targetUrl;
            }
            return;
        }

        var title = $btn.data('assessment-title') || '-';
        var typeBadge = $btn.data('assessment-type') || 'ASSESSMENT CONTENT';
        var batch = $btn.data('assessment-batch') || '-';
        var fromDate = $btn.data('assessment-from-date') || '-';
        var toDate = $btn.data('assessment-to-date') || '-';
        var passingGrade = $btn.data('assessment-passing-grade') || '-';
        var attempts = $btn.data('assessment-attempts') || '-';

        var batchDisplay = (batch && batch !== '-')
            ? (String(batch).toLowerCase().indexOf('batch') !== -1 ? batch : 'Batch ' + batch)
            : '-';

        var dateTimeDisplay = '-';
        if (fromDate !== '-' && toDate !== '-') {
            dateTimeDisplay = '<div>' + fromDate + '</div><div>' + toDate + '</div>';
        } else if (fromDate !== '-') {
            dateTimeDisplay = '<div>' + fromDate + '</div>';
        } else if (toDate !== '-') {
            dateTimeDisplay = '<div>' + toDate + '</div>';
        }

        var duration = $btn.data('assessment-duration') || '60';

        $modal.find('#assessmentAccessModalTypeBadge').text(typeBadge);
        $modal.find('#assessmentAccessModalName').text(title);
        $modal.find('#assessmentAccessModalSubText').text(batchDisplay);
        $modal.find('#assessmentAccessModalBatch').text(batchDisplay !== '-' ? batchDisplay : 'Batch 1');
        $modal.find('#assessmentAccessModalFromDate').text(fromDate);
        $modal.find('#assessmentAccessModalToDate').text(toDate);
        $modal.find('#assessmentAccessModalDateTime').html(dateTimeDisplay);
        $modal.find('#assessmentAccessModalPassingGrade').text(passingGrade);
        $modal.find('#assessmentAccessModalDuration').text(duration + ' Minutes');
        $modal.find('#assessmentAccessModalAttempt').text(attempts);

        $('#btnStartAssessmentAccess')
            .data('target-url', targetUrl)
            .attr('data-target-url', targetUrl);

        $modal
            .data('target-url', targetUrl)
            .attr('data-target-url', targetUrl);

        if (typeof $.fn.modal === 'function') {
            $modal.modal('show');
        } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var bsModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
            bsModal.show();
        } else {
            $modal.addClass('show').css('display', 'block');
        }
    });

    $(document).off('click', '#btnStartAssessmentAccess').on('click', '#btnStartAssessmentAccess', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var $modal = $('#assessmentAccessModal');

        var targetUrl = $btn.data('target-url') ||
                        $btn.attr('data-target-url') ||
                        $modal.data('target-url') ||
                        $modal.attr('data-target-url');

        if (!targetUrl) {
            return;
        }

        window.location.href = targetUrl;
    });

</script>
