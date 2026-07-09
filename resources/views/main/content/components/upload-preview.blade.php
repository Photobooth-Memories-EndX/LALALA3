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
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--tblr-muted, #6c757d);
    margin-bottom: 8px;
}
.owl-pv-label i { font-size: 13px; }

/* ── Shared preview box ────────────────────────────────────────────── */
.content-preview-box {
    width: 100%;
    min-height: 160px;
    border: 1px solid rgba(98,105,118,.24);
    border-radius: 12px;
    background: #f8fafc;
    overflow: hidden;
}

/* ── Empty state ────────────────────────────────────────────────────── */
.content-preview-empty {
    min-height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #64748b;
    text-align: center;
    padding: 16px;
}
.content-preview-empty i    { font-size: 38px; opacity: .4; }
.content-preview-empty span { font-size: 12px; }

/* ── Media elements ─────────────────────────────────────────────────── */
.content-preview-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    display: block;
}
.content-preview-video {
    width: 100%;
    max-height: 280px;
    display: block;
    background: #000;
}
.content-preview-pdf {
    width: 100%;
    height: 300px;
    display: block;
    border: none;
}

/* ── Icon placeholder (PDF/other server files) ──────────────────────── */
.content-preview-icon {
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 20px 16px;
}
.content-preview-icon i       { font-size: 40px; color: var(--tblr-primary, #206bc4); }
.content-preview-icon.is-pdf i { color: #e53e3e; }
.content-preview-icon span    { font-size: 12px; font-weight: 500; color: #64748b; word-break: break-all; }

/* ── Meta bar ───────────────────────────────────────────────────────── */
.content-preview-meta {
    padding: 10px 12px;
    border-top: 1px solid rgba(98,105,118,.18);
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: 12px;
}
.content-preview-meta strong { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.content-preview-meta span   { color: #64748b; }

/* ── Dark mode ──────────────────────────────────────────────────────── */
[data-bs-theme="dark"] .content-preview-box,
body.dark-mode .content-preview-box {
    background: #1f2937;
    border-color: rgba(255,255,255,.14);
}
[data-bs-theme="dark"] .content-preview-empty,
body.dark-mode .content-preview-empty { color: #9ca3af; }

[data-bs-theme="dark"] .content-preview-meta,
body.dark-mode .content-preview-meta  { border-top-color: rgba(255,255,255,.12); }

[data-bs-theme="dark"] .content-preview-meta span,
body.dark-mode .content-preview-meta span { color: #9ca3af; }

[data-bs-theme="dark"] .content-preview-icon span,
body.dark-mode .content-preview-icon span { color: #9ca3af; }
</style>
@endonce

<div class="card owl-main-card mb-3">
    <div class="card-header">
        <div>
            <h3 class="card-title">Preview</h3>
            <p class="text-muted mb-0" style="font-size:12px;">Automatically updates when a file is selected.</p>
        </div>
        <span class="badge bg-blue-lt text-blue" style="font-size:10px;">Live</span>
    </div>
    <div class="card-body" style="padding:14px 14px 16px;">

        <div class="owl-pv-label">
            <i class="ti ti-photo"></i>
            <span>Thumbnail</span>
        </div>
        <div id="contentThumbnailPreview" class="content-preview-box mb-3">
            <div class="content-preview-empty">
                <i class="ti ti-photo"></i>
                <span>Belum ada thumbnail</span>
            </div>
        </div>

        <hr style="margin:12px 0; border-color:rgba(98,105,118,.18);">

        <div class="owl-pv-label">
            <i class="ti ti-file"></i>
            <span>File</span>
        </div>
        <div id="contentFilePreview" class="content-preview-box">
            <div class="content-preview-empty">
                <i class="ti ti-file"></i>
                <span>Belum ada file</span>
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
