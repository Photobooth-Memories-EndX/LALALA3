<div class="row g-3 mb-4">

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm admin-stat-card">
            <div class="card-body p-3 text-center">
                <div class="stat-label">Users</div>
                <div class="stat-number">{{ number_format($totalUsers) }}</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm admin-stat-card">
            <div class="card-body p-3 text-center">
                <div class="stat-label">Content</div>
                <div class="stat-number">{{ number_format($totalContent) }}</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm admin-stat-card">
            <div class="card-body p-3 text-center">
                <div class="stat-label">News</div>
                <div class="stat-number">{{ number_format($totalNews) }}</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm admin-stat-card">
            <div class="card-body p-3 text-center">
                <div class="stat-label">Category</div>
                <div class="stat-number">{{ number_format($totalCategories) }}</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm admin-stat-card">
            <div class="card-body p-3 text-center">
                <div class="stat-label">Assessment</div>
                <div class="stat-number">{{ number_format($totalAssessments) }}</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm admin-stat-card">
            <div class="card-body p-3 text-center">
                <div class="stat-label">Playlist</div>
                <div class="stat-number">{{ number_format($totalPlaylists) }}</div>
            </div>
        </div>
    </div>

</div>
