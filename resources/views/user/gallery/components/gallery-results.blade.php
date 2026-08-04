<div class="gallery-results-header">
    <p class="gallery-result-count mb-0"><strong>{{ number_format($eventphoto->total()) }}</strong> event galleries</p>
</div>
@include('user.gallery.components.gallery-grid')
