@php
    $commentTotal = $eventphoto->comments_count ?? $eventphoto->total_comments_count ?? (optional($eventphoto)->relationLoaded('allComments') ? $eventphoto->allComments->count() : (method_exists($eventphoto, 'allComments') ? $eventphoto->allComments()->count() : (method_exists($comment, 'total') ? $comment->total() : count($comment))));
@endphp

<div class="modal fade content-comment-modal" id="modal-gallery-comments" tabindex="-1"
     aria-labelledby="modalGalleryCommentsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalGalleryCommentsLabel">
                    <i class="ti ti-message-circle me-2 text-primary"></i>
                    <span class="gallery-comment-total-count">{{ $commentTotal }}</span> Comments
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <div class="view-comment-wrapper p-3">
                    <x-comment :content="$eventphoto" :comment="$comment" type="eventphoto" />
                </div>
            </div>
        </div>
    </div>
</div>
