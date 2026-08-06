{{--
    Live upload preview card.
    Include in the right column of create/edit content pages.
    Uses innerHTML replacement — no CSS display-toggle conflicts.
    Public API: window.OwlPreview.initThumb(dz), initFile(dz),
                setServerThumb(url), setServerVideoFile(url, name), setServerPdfFile(name)
--}}

@once
<style>
.owl-pv-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--tblr-muted, #6c757d);
    margin-bottom: 6px;
}
.owl-pv-label i { font-size: 14px; }

/* ── Shared 16:9 preview box ────────────────────────────────────────── */
.content-preview-box {
    width: 100%;
    aspect-ratio: 16 / 9;
    border: 1px solid var(--tblr-border-color, rgba(98,105,118,.2));
    border-radius: 10px;
    background: var(--tblr-bg-surface-secondary, #f8fafc);
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* ── Empty state ────────────────────────────────────────────────────── */
.content-preview-empty {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #64748b;
    text-align: center;
    padding: 12px;
}
.content-preview-empty i    { font-size: 32px; opacity: .4; }
.content-preview-empty span { font-size: 12px; }

/* ── Media elements (Fit 16:9) ──────────────────────────────────────── */
.content-preview-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    display: block;
}
.content-preview-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    background: #000;
}
.content-preview-pdf {
    width: 100%;
    height: 100%;
    display: block;
    border: none;
}

/* ── Icon placeholder (PDF/other server files) ──────────────────────── */
.content-preview-icon {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px 12px;
}
.content-preview-icon i       { font-size: 36px; color: var(--tblr-primary, #206bc4); }
.content-preview-icon.is-pdf i { color: #e53e3e; }
.content-preview-icon span    { font-size: 12px; font-weight: 500; color: #64748b; word-break: break-all; }

/* ── Meta bar ───────────────────────────────────────────────────────── */
.content-preview-meta {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 6px 10px;
    background: rgba(15, 23, 42, .75);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    color: #fff;
    display: flex;
    flex-direction: column;
    gap: 1px;
    font-size: 11px;
    z-index: 10;
}
.content-preview-meta strong { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #fff; }
.content-preview-meta span   { color: rgba(255, 255, 255, .75); font-size: 10px; }

/* ── Dark mode ──────────────────────────────────────────────────────── */
[data-bs-theme="dark"] .content-preview-box,
body.dark-mode .content-preview-box,
body.theme-dark .content-preview-box {
    background: #1f2937;
    border-color: rgba(255,255,255,.14);
}
[data-bs-theme="dark"] .content-preview-empty,
body.dark-mode .content-preview-empty,
body.theme-dark .content-preview-empty { color: #9ca3af; }

[data-bs-theme="dark"] .content-preview-icon span,
body.dark-mode .content-preview-icon span,
body.theme-dark .content-preview-icon span { color: #9ca3af; }
</style>
@endonce

<div class="card owl-main-card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            @if(isset($stepNumber))
                <span class="badge bg-primary-lt text-primary rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">{{ $stepNumber }}</span>
            @endif
            <div>
                <h3 class="card-title mb-0">Live Preview</h3>
                <p class="text-muted mb-0" style="font-size:12px;">Automatically updates when files are attached.</p>
            </div>
        </div>
        <span class="badge bg-blue-lt text-blue ms-auto" style="font-size:10px;">Live</span>
    </div>
    <div class="card-body" style="padding: 14px;">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="owl-pv-label">
                    <i class="ti ti-photo"></i>
                    <span>Thumbnail Preview (16:9)</span>
                </div>
                <div id="contentThumbnailPreview" class="content-preview-box">
                    <div class="content-preview-empty">
                        <i class="ti ti-photo"></i>
                        <span>Belum ada thumbnail</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="owl-pv-label">
                    <i class="ti ti-file"></i>
                    <span>File Preview (16:9)</span>
                </div>
                <div id="contentFilePreview" class="content-preview-box">
                    <div class="content-preview-empty">
                        <i class="ti ti-file"></i>
                        <span>Belum ada file</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
<script>
(function () {
    'use strict';

    var _thumbBlob = null;   // current blob URL for thumb
    var _fileBlob  = null;   // current blob URL for file
    var _fbThumb   = null;   // server fallback: thumbnail URL
    var _fbFileFn  = null;   // server fallback: file render function

    var HTML_EMPTY_THUMB = '<div class="content-preview-empty"><i class="ti ti-photo"></i><span>Belum ada thumbnail</span></div>';
    var HTML_EMPTY_FILE  = '<div class="content-preview-empty"><i class="ti ti-file"></i><span>Belum ada file</span></div>';

    function boxThumb() { return document.getElementById('contentThumbnailPreview'); }
    function boxFile()  { return document.getElementById('contentFilePreview'); }

    function fmtSize(b) {
        if (!b) return '';
        if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
        if (b >= 1024)    return Math.round(b / 1024) + ' KB';
        return b + ' B';
    }

    function metaBar(name, extra) {
        return '<div class="content-preview-meta">'
             + '<strong>' + (name || '') + '</strong>'
             + (extra ? '<span>' + extra + '</span>' : '')
             + '</div>';
    }

    /* ── Thumbnail ──────────────────────────────────────────────────── */
    function thumbSet(src) {
        var b = boxThumb();
        if (!b) return;
        b.innerHTML = '<img src="' + src + '" class="content-preview-image" alt="Thumbnail preview">';
    }
    function thumbReset() {
        if (_thumbBlob) { URL.revokeObjectURL(_thumbBlob); _thumbBlob = null; }
        if (_fbThumb)   { thumbSet(_fbThumb); return; }
        var b = boxThumb();
        if (b) b.innerHTML = HTML_EMPTY_THUMB;
    }

    /* ── File ───────────────────────────────────────────────────────── */
    function fileSet(html) {
        var b = boxFile();
        if (b) b.innerHTML = html;
    }

    function renderVideo(src, name, size, typeLabel) {
        fileSet('<video src="' + src + '" controls class="content-preview-video"></video>'
              + metaBar(name, (size ? fmtSize(size) + ' · ' : '') + (typeLabel || 'Video')));
    }
    function renderPdfEmbed(src, name, size) {
        fileSet('<embed src="' + src + '" type="application/pdf" class="content-preview-pdf">'
              + metaBar(name, (size ? fmtSize(size) + ' · ' : '') + 'PDF Document'));
    }
    function renderPdfIcon(name) {
        fileSet('<div class="content-preview-icon is-pdf">'
              + '<i class="ti ti-file-type-pdf"></i>'
              + '<span>' + (name || 'PDF Document') + '</span>'
              + '</div>'
              + metaBar(name, 'PDF Document'));
    }
    function renderOther(name, size, typeLabel) {
        fileSet('<div class="content-preview-icon">'
              + '<i class="ti ti-file"></i>'
              + '<span>' + (name || '') + '</span>'
              + '</div>'
              + metaBar(name, (size ? fmtSize(size) : '') + (typeLabel ? ' · ' + typeLabel : '')));
    }
    function fileReset() {
        if (_fileBlob) { URL.revokeObjectURL(_fileBlob); _fileBlob = null; }
        if (_fbFileFn) { _fbFileFn(); return; }
        var b = boxFile();
        if (b) b.innerHTML = HTML_EMPTY_FILE;
    }

    /* ── Dropzone binding ───────────────────────────────────────────── */
    function bindThumb(dz) {
        dz.on('addedfile', function (file) {
            if (_thumbBlob) URL.revokeObjectURL(_thumbBlob);
            _thumbBlob = URL.createObjectURL(file);
            thumbSet(_thumbBlob);
        });
        dz.on('removedfile', thumbReset);
    }

    function bindFile(dz) {
        dz.on('addedfile', function (file) {
            if (_fileBlob) URL.revokeObjectURL(_fileBlob);
            _fileBlob = URL.createObjectURL(file);
            var ext  = (file.name || '').split('.').pop().toLowerCase();
            var mime = (file.type || '');
            if (ext === 'mp4' || ext === 'avi' || ext === 'mov' || mime.startsWith('video/')) {
                renderVideo(_fileBlob, file.name, file.size, file.type || 'Video');
            } else if (ext === 'pdf' || mime === 'application/pdf') {
                renderPdfEmbed(_fileBlob, file.name, file.size);
            } else {
                renderOther(file.name, file.size, file.type);
            }
        });
        dz.on('removedfile', fileReset);
    }

    /* ── Public API ─────────────────────────────────────────────────── */
    window.OwlPreview = {
        /** Set existing server thumbnail (called from edit page). */
        setServerThumb: function (url) {
            _fbThumb = url || null;
            if (url) thumbSet(url);
        },
        /** Set existing server video file (called from edit page). */
        setServerVideoFile: function (url, name) {
            _fbFileFn = function () { renderVideo(url, name, null, 'Video'); };
            _fbFileFn();
        },
        /** Set existing server PDF file icon (called from edit page). */
        setServerPdfFile: function (name) {
            _fbFileFn = function () { renderPdfIcon(name); };
            _fbFileFn();
        },
        /** Bind thumbnail Dropzone instance. Call after Dropzone init. */
        initThumb: function (dz) { bindThumb(dz); },
        /** Bind file/document Dropzone instance. Call after Dropzone init. */
        initFile:  function (dz) { bindFile(dz); }
    };

    console.log('Content preview script loaded', {
        thumbnailPreview: !!document.getElementById('contentThumbnailPreview'),
        filePreview:      !!document.getElementById('contentFilePreview')
    });
}());
</script>
@endonce
