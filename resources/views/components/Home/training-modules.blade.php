<div class="home-modern-card">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Training Modules</h2>
    </div>

    {{-- Tab Nav --}}
    <div class="px-4 pb-0 border-bottom">
        <ul class="nav home-modern-nav-tabs" id="trainingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-video-btn"
                        data-bs-toggle="tab" data-bs-target="#pane-video"
                        type="button" role="tab" aria-selected="true">
                    <i class="ti ti-video me-1"></i>Video
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-document-btn"
                        data-bs-toggle="tab" data-bs-target="#pane-document"
                        type="button" role="tab" aria-selected="false">
                    <i class="ti ti-file-text me-1"></i>Document
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-playlist-btn"
                        data-bs-toggle="tab" data-bs-target="#pane-playlist"
                        type="button" role="tab" aria-selected="false">
                    <i class="ti ti-playlist me-1"></i>Playlist
                </button>
            </li>
        </ul>
    </div>

    <div class="home-modern-card-body">

        <div class="tab-content" id="trainingTabContent">

            {{-- ── VIDEO TAB ── --}}
            <div class="tab-pane fade show active" id="pane-video" role="tabpanel">

                @if($content_video && $content_video->count())
                <div class="home-modern-hscroll py-2">
                    @foreach($content_video->take(10) as $item)
                    <div class="home-modern-hscroll-item">
                        <a href="{{ route('content.user.view', $item->slug) }}" class="home-modern-video-card">

                            <div class="home-modern-video-thumb">
                                @if($item->type === 'YouTube')
                                    <img src="https://img.youtube.com/vi/{{ $item->link }}/mqdefault.jpg"
                                         alt="{{ $item->title }}"
                                         onerror="this.src='{{ asset('default-video.jpg') }}'">
                                @else
                                    @php
                                        $tmSrc = 'storage/files/content/' . $item->id . '/thumb.jpg';
                                        $tmUrl = (\File::exists(public_path($tmSrc)) && !is_dir(public_path($tmSrc)))
                                            ? asset($tmSrc) : asset('default-video.jpg');
                                    @endphp
                                    <img src="{{ $tmUrl }}"
                                         alt="{{ $item->title }}"
                                         onerror="this.src='{{ asset('default-video.jpg') }}'">
                                @endif
                                <div class="home-modern-video-play">
                                    <i class="ti ti-player-play-filled"></i>
                                </div>
                                @if($item->duration)
                                <span class="home-modern-duration-badge">{{ $item->duration }}</span>
                                @endif
                            </div>

                            <div class="home-modern-video-body">
                                <h4 class="home-modern-video-title">{{ Str::limit($item->title, 55) }}</h4>
                            </div>

                        </a>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="home-modern-empty">
                    <i class="ti ti-video-off"></i>
                    <p>No videos available</p>
                </div>
                @endif

            </div>

            {{-- ── DOCUMENT TAB ── --}}
            <div class="tab-pane fade" id="pane-document" role="tabpanel">

                @if($content_pdf && $content_pdf->count())
                <div class="home-modern-hscroll py-2">
                    @foreach($content_pdf->take(10) as $pdf)
                    <div class="home-modern-hscroll-item">
                        <a href="{{ route('content.user.view', $pdf->slug) }}" class="home-modern-video-card">

                            <div class="home-modern-video-thumb">
                                @php
                                    $docSrc = 'storage/files/content/' . $pdf->id . '/thumb.jpg';
                                    $docUrl = (\File::exists(public_path($docSrc)) && !is_dir(public_path($docSrc)))
                                        ? asset($docSrc) : asset('pdf.png');
                                @endphp
                                <img src="{{ $docUrl }}"
                                     alt="{{ $pdf->title }}"
                                     onerror="this.src='{{ asset('pdf.png') }}'"
                                     style="object-fit:contain; background:#f8fafc;">
                            </div>

                            <div class="home-modern-video-body">
                                <span class="home-modern-category-badge home-modern-category-green">Document</span>
                                <h4 class="home-modern-video-title">{{ Str::limit($pdf->title, 55) }}</h4>
                            </div>

                        </a>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="home-modern-empty">
                    <i class="ti ti-file-off"></i>
                    <p>No documents available</p>
                </div>
                @endif

            </div>

            {{-- ── PLAYLIST TAB ── --}}
            <div class="tab-pane fade" id="pane-playlist" role="tabpanel">

                @forelse($playlist->take(8) as $item)
                    @php $firstVideo = $item->trainingplandetail->first(); @endphp
                    @if($firstVideo && $firstVideo->content)
                    <a href="{{ route('playlist.user.view', ['slug' => $item->slug, 'vidslug' => $firstVideo->content->slug]) }}"
                       class="home-modern-module-row">

                        <div class="home-modern-module-left">
                            <div class="home-modern-module-icon">
                                <i class="ti ti-playlist"></i>
                            </div>
                            <div class="home-modern-module-text">
                                <div class="home-modern-module-title">{{ Str::limit($item->title, 55) }}</div>
                                <div class="home-modern-module-sub">
                                    {{ $item->trainingplandetail->count() }} modules
                                </div>
                            </div>
                        </div>

                        <i class="ti ti-chevron-right home-modern-module-arrow"></i>

                    </a>
                    @endif
                @empty
                <div class="home-modern-empty">
                    <i class="ti ti-playlist-x"></i>
                    <p>No playlists available</p>
                </div>
                @endforelse

            </div>

        </div>

    </div>

</div>
