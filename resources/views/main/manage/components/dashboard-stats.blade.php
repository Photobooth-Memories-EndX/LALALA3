<div class="row g-3 mb-4">

    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">Users</div>
                <h2>{{ number_format($totalUsers) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">Content</div>
                <h2>{{ number_format($totalContent) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">News</div>
                <h2>{{ number_format($totalNews) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">Category</div>
                <h2>{{ number_format($totalCategories) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">Assessment</div>
                <h2>{{ number_format($totalAssessments) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">Playlist</div>
                <h2>{{ number_format($totalPlaylists) }}</h2>
            </div>
        </div>
    </div>

</div>
