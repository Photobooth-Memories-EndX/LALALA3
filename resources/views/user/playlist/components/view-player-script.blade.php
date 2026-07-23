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
                let playlistPdfRotation = 0;
                let playlistPdfRestoringView = false;
                let playlistPdfChangingLayout = false;
                let playlistPdfModeTransitionTimer = null;

                function syncPlaylistPdfSinglePage(pageno) {
                    if (!pageno) return;

                    var viewerElement = document.querySelector('.playlist-pdfjs-viewer');
                    if (!viewerElement) return;

                    viewerElement.querySelectorAll('.pdfpage').forEach(function(pageElement) {
                        var isActive = Number(pageElement.dataset.page) === Number(pageno);

                        pageElement.classList.toggle('playlist-pdf-page-active', isActive);
                        pageElement.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                    });

                    viewerElement.scrollTop = 0;
                    viewerElement.scrollLeft = 0;

                    var refreshPage = function() {
                        var activePage = playlistPdfViewer.getActivePage();
                        var activePageNumber = activePage && typeof activePage.data === 'function'
                            ? activePage.data('page')
                            : null;

                        if (
                            Number(activePageNumber) === Number(pageno)
                            && typeof playlistPdfViewer.refreshAll === 'function'
                        ) {
                            playlistPdfViewer.refreshAll();
                        }
                    };

                    if (typeof window.requestAnimationFrame === 'function') {
                        window.requestAnimationFrame(refreshPage);
                    } else {
                        setTimeout(refreshPage, 0);
                    }
                }

                window.playlistPdfViewer = new PDFjsViewer($(".playlist-pdfjs-viewer"), {
                    extraPagesToLoad: 0,

                    onNewPage: function(page, pageno) {
                        var pageElement = page.get(0);
                        var isActive = Number(pageno) === 1;

                        pageElement.classList.toggle('playlist-pdf-page-active', isActive);
                        pageElement.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                    },

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

                    onZoomChange: function(zoom) {
                        zoom = parseInt(zoom * 10000) / 100;
                        $('#playlist-pdf-shell .zoomval').text(zoom + "%");
                    },

                    onActivePageChanged: function(page, pageno) {
                        if (!pageno) return;

                        syncPlaylistPdfSinglePage(pageno);
                        $('#playlist-pdf-shell .pageno').text(pageno + "/" + this.getPageCount());

                        if (playlistPdfRestoringView || playlistPdfChangingLayout) return;

                        progressTracker.sendDebounced({
                            last_page: pageno,
                            total_pages: this.getPageCount()
                        });
                    }
                });

                if (typeof playlistPdfViewer._areaOfPageVisible === 'function') {
                    var measurePlaylistPdfVisibleArea = playlistPdfViewer._areaOfPageVisible;

                    playlistPdfViewer._areaOfPageVisible = function(page) {
                        if (
                            page
                            && typeof page.hasClass === 'function'
                            && !page.hasClass('playlist-pdf-page-active')
                        ) {
                            return 0;
                        }

                        return measurePlaylistPdfVisibleArea.call(this, page);
                    };
                }

                var playlistPdfDownloadButton = document.getElementById('playlist-pdf-download-btn');
                if (playlistPdfDownloadButton) {
                    playlistPdfDownloadButton.href = pdfFile;
                }

                playlistPdfViewer.loadDocument(pdfFile).then(function() {
                    playlistPdfViewer.setZoom('width');
                });

                function getPlaylistPdfViewState() {
                    var activePage = playlistPdfViewer.getActivePage();
                    var pageNumber = activePage && typeof activePage.data === 'function'
                        ? activePage.data('page')
                        : 1;

                    return {
                        page: pageNumber || 1,
                        zoom: typeof playlistPdfViewer.getZoom === 'function'
                            ? playlistPdfViewer.getZoom()
                            : 1
                    };
                }

                function applyPlaylistPdfRotation(degrees) {
                    var viewState = getPlaylistPdfViewState();
                    var rotationResult;

                    playlistPdfRotation = (degrees + 360) % 360;
                    playlistPdfRestoringView = true;

                    if (typeof playlistPdfViewer.setRotation === 'function') {
                        rotationResult = playlistPdfViewer.setRotation(playlistPdfRotation);
                    } else if (typeof playlistPdfViewer.rotate === 'function') {
                        rotationResult = playlistPdfViewer.rotate(playlistPdfRotation);
                    } else {
                        var viewerElement = document.querySelector('.playlist-pdfjs-viewer');
                        if (viewerElement) {
                            viewerElement.style.transform = 'rotate(' + playlistPdfRotation + 'deg)';
                        }
                    }

                    return Promise.resolve(rotationResult).then(function() {
                        if (typeof playlistPdfViewer.setZoom === 'function') {
                            playlistPdfViewer.setZoom(viewState.zoom);
                        }

                        playlistPdfRestoringView = false;
                        playlistPdfViewer.scrollToPage(
                            Math.min(viewState.page, playlistPdfViewer.getPageCount())
                        );
                    }, function() {
                        playlistPdfRestoringView = false;
                    });
                }

                window.playlistPdfRotateLeft = function() {
                    return applyPlaylistPdfRotation(playlistPdfRotation - 90);
                };

                window.playlistPdfRotateRight = function() {
                    return applyPlaylistPdfRotation(playlistPdfRotation + 90);
                };

                function applyPlaylistPdfReadingMode(mode) {
                    var shell = document.getElementById('playlist-pdf-shell');
                    if (!shell) return;

                    var mobileQuery = typeof window.matchMedia === 'function'
                        ? window.matchMedia('(max-width: 575.98px)')
                        : null;

                    if (mobileQuery && mobileQuery.matches) {
                        mode = 'portrait';
                    }

                    var isLandscape = mode === 'landscape';
                    var viewState = getPlaylistPdfViewState();
                    var button = document.getElementById('playlist-pdf-mode-btn');
                    var icon = document.getElementById('playlist-pdf-mode-icon');
                    var label = document.getElementById('playlist-pdf-mode-label');

                    playlistPdfChangingLayout = true;
                    shell.classList.toggle('playlist-pdf-mode-portrait', !isLandscape);
                    shell.classList.toggle('playlist-pdf-mode-landscape', isLandscape);
                    shell.dataset.pdfReadingMode = isLandscape ? 'landscape' : 'portrait';

                    if (button) {
                        button.setAttribute('aria-pressed', isLandscape ? 'true' : 'false');
                        button.title = isLandscape
                            ? 'Switch to Portrait layout'
                            : 'Switch to Landscape layout';
                    }
                    if (icon) {
                        icon.className = isLandscape
                            ? 'ti ti-rectangle'
                            : 'ti ti-rectangle-vertical';
                    }
                    if (label) {
                        label.textContent = isLandscape ? 'Landscape' : 'Portrait';
                    }

                    if (playlistPdfModeTransitionTimer) {
                        clearTimeout(playlistPdfModeTransitionTimer);
                    }

                    playlistPdfModeTransitionTimer = setTimeout(function() {
                        if (typeof playlistPdfViewer.refreshAll === 'function') {
                            playlistPdfViewer.refreshAll();
                        }

                        if (
                            typeof playlistPdfViewer.getZoom === 'function'
                            && Math.abs(playlistPdfViewer.getZoom() - viewState.zoom) > 0.0001
                        ) {
                            playlistPdfViewer.setZoom(viewState.zoom);
                        }

                        var currentState = getPlaylistPdfViewState();
                        if (currentState.page !== viewState.page) {
                            playlistPdfViewer.scrollToPage(viewState.page);
                        }

                        playlistPdfChangingLayout = false;
                        playlistPdfModeTransitionTimer = null;
                    }, 340);
                }

                window.playlistPdfToggleReadingMode = function() {
                    var shell = document.getElementById('playlist-pdf-shell');
                    if (!shell) return;

                    applyPlaylistPdfReadingMode(
                        shell.classList.contains('playlist-pdf-mode-landscape')
                            ? 'portrait'
                            : 'landscape'
                    );
                };

                window.playlistPdfDoPrint = function() {
                    var frame = document.createElement('iframe');
                    var handled = false;

                    frame.style.cssText = 'position:fixed;left:-9999px;top:-9999px;width:0;height:0;border:none;';

                    function removePrintFrame() {
                        if (frame.parentNode) {
                            frame.parentNode.removeChild(frame);
                        }
                    }

                    function openPrintFallback() {
                        if (handled) return;
                        handled = true;
                        removePrintFrame();
                        window.open(pdfFile, '_blank', 'noopener');
                    }

                    frame.onload = function() {
                        try {
                            if (!frame.contentWindow || typeof frame.contentWindow.print !== 'function') {
                                throw new Error('Browser print is unavailable.');
                            }

                            frame.contentWindow.focus();
                            frame.contentWindow.print();
                            handled = true;
                            setTimeout(removePrintFrame, 3000);
                        } catch (error) {
                            openPrintFallback();
                        }
                    };
                    frame.onerror = openPrintFallback;
                    frame.src = pdfFile;
                    document.body.appendChild(frame);
                    setTimeout(openPrintFallback, 5000);
                };

                function syncPlaylistPdfFullscreenButton() {
                    var shell = document.getElementById('playlist-pdf-shell');
                    var icon = document.getElementById('playlist-pdf-fs-icon');
                    var button = document.getElementById('playlist-pdf-fs-btn');
                    var fullscreenElement = document.fullscreenElement
                        || document.webkitFullscreenElement
                        || null;
                    var isFullscreen = fullscreenElement === shell;

                    if (icon) {
                        icon.className = isFullscreen ? 'ti ti-minimize' : 'ti ti-maximize';
                    }
                    if (button) {
                        button.title = isFullscreen ? 'Exit Fullscreen' : 'Fullscreen';
                        button.setAttribute('aria-pressed', isFullscreen ? 'true' : 'false');
                    }
                }

                window.playlistPdfToggleFullscreen = function() {
                    var shell = document.getElementById('playlist-pdf-shell');
                    if (!shell) return;

                    var fullscreenElement = document.fullscreenElement
                        || document.webkitFullscreenElement
                        || null;
                    var fullscreenAction;

                    if (!fullscreenElement) {
                        fullscreenAction = shell.requestFullscreen
                            || shell.webkitRequestFullscreen
                            || shell.mozRequestFullScreen;

                        if (fullscreenAction) {
                            var requestResult = fullscreenAction.call(shell);
                            if (requestResult && typeof requestResult.catch === 'function') {
                                requestResult.catch(function() {});
                            }
                        }
                    } else {
                        fullscreenAction = document.exitFullscreen
                            || document.webkitExitFullscreen
                            || document.mozCancelFullScreen;

                        if (fullscreenAction) {
                            var exitResult = fullscreenAction.call(document);
                            if (exitResult && typeof exitResult.catch === 'function') {
                                exitResult.catch(function() {});
                            }
                        }
                    }
                };

                var playlistPdfShell = document.getElementById('playlist-pdf-shell');

                if (playlistPdfShell && playlistPdfShell.dataset.pdfToolbarBound !== 'true') {
                    playlistPdfShell.dataset.pdfToolbarBound = 'true';

                    document.addEventListener('fullscreenchange', syncPlaylistPdfFullscreenButton);
                    document.addEventListener('webkitfullscreenchange', syncPlaylistPdfFullscreenButton);

                    if (typeof window.matchMedia === 'function') {
                        var playlistPdfMobileQuery = window.matchMedia('(max-width: 575.98px)');
                        var enforcePlaylistPdfMobileMode = function(event) {
                            if (
                                event.matches
                                && playlistPdfShell.classList.contains('playlist-pdf-mode-landscape')
                            ) {
                                applyPlaylistPdfReadingMode('portrait');
                            }
                        };

                        if (typeof playlistPdfMobileQuery.addEventListener === 'function') {
                            playlistPdfMobileQuery.addEventListener('change', enforcePlaylistPdfMobileMode);
                        } else if (typeof playlistPdfMobileQuery.addListener === 'function') {
                            playlistPdfMobileQuery.addListener(enforcePlaylistPdfMobileMode);
                        }
                    }

                    var playlistPdfViewerElement = document.querySelector('.playlist-pdfjs-viewer');

                    if (playlistPdfViewerElement) {
                        playlistPdfViewerElement.addEventListener('wheel', function(event) {
                            if (!event.ctrlKey) return;

                            event.preventDefault();
                            playlistPdfViewer.setZoom(event.deltaY < 0 ? 'in' : 'out');
                        }, { passive: false });

                        playlistPdfViewerElement.addEventListener('dblclick', function() {
                            playlistPdfViewer.setZoom('width');
                        });
                    }

                    document.addEventListener('keydown', function(event) {
                        var tag = (event.target.tagName || '').toLowerCase();

                        if (tag === 'input' || tag === 'textarea' || event.target.isContentEditable) {
                            return;
                        }

                        if (event.ctrlKey) {
                            switch (event.key) {
                                case '+':
                                case '=':
                                    event.preventDefault();
                                    playlistPdfViewer.setZoom('in');
                                    break;
                                case '-':
                                    event.preventDefault();
                                    playlistPdfViewer.setZoom('out');
                                    break;
                                case '0':
                                    event.preventDefault();
                                    playlistPdfViewer.setZoom('fit');
                                    break;
                            }
                            return;
                        }

                        switch (event.key) {
                            case 'PageDown':
                            case 'ArrowRight':
                                event.preventDefault();
                                playlistPdfViewer.next();
                                break;
                            case 'PageUp':
                            case 'ArrowLeft':
                                event.preventDefault();
                                playlistPdfViewer.prev();
                                break;
                            case 'Home':
                                event.preventDefault();
                                playlistPdfViewer.first();
                                break;
                            case 'End':
                                event.preventDefault();
                                playlistPdfViewer.last();
                                break;
                            case ' ':
                                event.preventDefault();
                                if (event.shiftKey) {
                                    playlistPdfViewer.prev();
                                } else {
                                    playlistPdfViewer.next();
                                }
                                break;
                            case 'f':
                            case 'F':
                                event.preventDefault();
                                playlistPdfViewer.setZoom('width');
                                break;
                        }
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
                    item.style.display = title.includes(keyword) ? 'flex' : 'none';
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
