<style>
    .learning-view-page {
        background: #f5f7fb;
        min-height: 100vh;
    }

    .rounded-4 {
        border-radius: 18px !important;
    }

    .view-comment-wrapper {
        padding-left: 12px;
        padding-right: 12px;
        margin-top: 24px;
    }

    .view-comment-wrapper .card {
        padding-left: 18px;
        padding-right: 18px;
    }

    .view-hero-card {
        height: 260px;
    }

    .view-hero-bg {
        height: 260px;
        background-size: cover;
        background-position: center;
    }

    .view-hero-overlay {
        height: 100%;
        display: flex;
        align-items: flex-end;
        background: linear-gradient(
            180deg,
            rgba(0, 0, 0, 0.15) 0%,
            rgba(0, 0, 0, 0.75) 100%
        );
    }

    .learning-player video,
    .learning-player .plyr {
        width: 100%;
        height: auto;
        aspect-ratio: 16 / 9;
        background: #000;
    }

    .lesson-thumb {
        width: 58px;
        height: 42px;
        border-radius: 10px;
        background-size: cover;
        background-position: center;
        flex-shrink: 0;
    }

    .playlist-list .list-group-item {
        color: inherit;
        transition: .2s ease;
    }

    .playlist-list .list-group-item:hover {
        background: #f1f5ff;
    }

    .playlist-list .active-lesson {
        background: #eaf1ff;
        border: 1px solid #3b82f6 !important;
    }

    .chapter-description-box {
        background: #f8fafc;
        border: 1px solid #edf2f7;
        border-radius: 16px;
        padding: 18px;
    }

    .learn-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 13px;
        color: #334155;
    }

    .btn-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .view-ai-card {
        min-height: 520px;
    }

    .ai-message-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 12px;
        font-size: 13px;
        color: #475569;
    }

    .ai-suggestion-btn {
        border-radius: 12px;
        font-size: 13px;
        padding: 10px 12px;
    }

    .ai-input-group .form-control {
        border-radius: 12px 0 0 12px;
        font-size: 13px;
    }

    .ai-input-group .btn {
        border-radius: 0 12px 12px 0;
    }

    .comment-section-padding {
        padding: 0 20px 20px;
    }

    .comment-section-padding .card-header {
        padding-left: 24px !important;
        padding-right: 24px !important;
    }

    .comment-section-padding .card-body {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    @media (max-width: 991px) {
        .sticky-top {
            position: static !important;
        }

        .view-ai-card {
            min-height: auto;
        }

        .view-comment-wrapper {
            padding-left: 0;
            padding-right: 0;
        }

        .view-comment-wrapper .card {
            padding-left: 0;
            padding-right: 0;
        }
    }

    .view-playlist-card {
    max-height: calc(100vh - 110px);
}

.playlist-scroll-area {
    max-height: 640px;
    overflow-y: auto;
    padding-right: 4px;
}

.playlist-scroll-area::-webkit-scrollbar {
    width: 5px;
}

.playlist-scroll-area::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

.playlist-lesson-item {
    display: flex;
    gap: 10px;
    text-decoration: none;
    color: #0f172a;
    padding: 8px;
    border-radius: 14px;
    margin-bottom: 8px;
    border: 1px solid transparent;
    transition: .2s ease;
}

.playlist-lesson-item:hover {
    background: #f1f5ff;
    color: #0f172a;
}

.playlist-lesson-item.is-active {
    background: #eaf1ff;
    border-color: #3b82f6;
}

.lesson-thumb {
    width: 62px;
    height: 46px;
    border-radius: 12px;
    background-size: cover;
    background-position: center;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
}

.lesson-play-icon,
.lesson-done-icon {
    position: absolute;
    inset: 0;
    margin: auto;
    width: 24px;
    height: 24px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.lesson-play-icon {
    background: rgba(37, 99, 235, .92);
    color: #fff;
}

.lesson-done-icon {
    background: rgba(22, 163, 74, .92);
    color: #fff;
}

.lesson-info {
    min-width: 0;
    flex: 1;
}

.lesson-title {
    font-size: 12px;
    font-weight: 700;
    line-height: 1.25;
    color: #0f172a;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.lesson-meta {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
}

.lesson-progress {
    height: 4px;
    margin-top: 5px;
    background: #e5e7eb;
}

.chapter-description-box {
    background: #f8fafc;
    border: 1px solid #edf2f7;
    border-radius: 16px;
    padding: 18px;
}

.learn-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 13px;
    color: #334155;
}

.learn-item i {
    margin-top: 2px;
}

.view-comment-wrapper {
    width: 100%;
    padding: 0 16px 24px;
}

.view-comment-wrapper > .mt-3,
.view-comment-wrapper .card {
    width: 100%;
}

.view-ai-card,
.ai-card {
    display: block !important;
    width: 100%;
    min-height: 520px;
}

@media (max-width: 1199px) {
    .view-ai-card,
    .ai-card {
        margin-top: 16px;
        min-height: auto;
    }
}
</style>