@php
    $commentTotal = method_exists($comment, 'total') ? $comment->total() : $comment->count();
@endphp

<div class="modal fade content-comment-modal" id="contentCommentModal" tabindex="-1"
     aria-labelledby="contentCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="contentCommentModalLabel">
                    <i class="ti ti-message-circle me-2"></i>
                    {{ $commentTotal }} Comments
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <div class="view-comment-wrapper">
                    <x-comment :content="$content" :comment="$comment" type="content" />
                </div>
            </div>
        </div>
    </div>
</div>
