@php
    $commentTotal = method_exists($comment, 'total') ? $comment->total() : (is_countable($comment) ? count($comment) : 0);
@endphp

<div class="modal fade content-comment-modal" id="modal-news-comments" tabindex="-1"
     aria-labelledby="modalNewsCommentsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalNewsCommentsLabel">
                    <i class="ti ti-message-circle me-2 text-primary"></i>
                    {{ $commentTotal }} Comments
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <div class="view-comment-wrapper p-3">
                    <x-comment :content="$news" :comment="$comment" type="news" />
                </div>
            </div>
        </div>
    </div>
</div>
