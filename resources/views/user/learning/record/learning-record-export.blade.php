<template data-learning-record-hero>
    <x-shared.hero-learning-record
        :stats="[
            ['label' => 'Course Completed', 'value' => number_format($recordStats['courseCompleted'] ?? 0)],
            ['label' => 'Playlist Completed', 'value' => number_format($recordStats['playlistCompleted'] ?? 0)],
            ['label' => 'Learning Hours', 'value' => number_format($recordStats['totalLearningHours'] ?? 0, 2)],
        ]"
    />
</template>

<div class="learning-record-shell">
    {{-- Quick Filter Card (Mode Switch) --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3 my-task-filter-card">
        <div class="card-body p-3 p-md-4">
            <div class="mb-3">
                <h2 class="fw-bold mb-1 my-task-filter-title">Learning Record</h2>
                <p class="text-muted small mb-0 my-task-filter-subtitle">Export your verified training records in Excel spreadsheet format (.xlsx).</p>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2 my-task-pills-row">
                <button type="button" class="my-task-category-pill record-mode-btn" data-mode="online" title="KP OWL">KP OWL</button>
                <button type="button" class="my-task-category-pill record-mode-btn" data-mode="offline" title="Non KP OWL">Non KP OWL</button>
                <button type="button" class="my-task-category-pill record-mode-btn" data-mode="all" title="All Record">All Record</button>
                <button type="button" class="my-task-category-pill record-mode-btn active" data-mode="export" title="Export Center">Export Center</button>
            </div>
        </div>
    </div>

    {{-- Export Hub Header --}}
    <div class="card border-0 shadow-sm p-4 mb-4 rounded-4 learning-record-card bg-white">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width: 52px; height: 52px; background-color: #dcfce7; color: #15803d; border-radius: 12px;">
                <i class="ti ti-download fs-1"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-1 text-dark">Learning Record Export Center</h3>
                <p class="text-muted small mb-0">Download your verified training records in Excel spreadsheet format (.xlsx) for personal archiving or reporting.</p>
            </div>
        </div>
    </div>

    {{-- Export Cards Row --}}
    <div class="row g-3">
        {{-- Card 1: Online Record --}}
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 learning-record-card bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="avatar avatar-md rounded-3" style="background-color: #e0f2fe; color: #0284c7; width: 44px; height: 44px;">
                                <i class="ti ti-device-laptop fs-2"></i>
                            </span>
                            <span class="badge bg-blue-lt text-blue rounded-pill px-2.5 py-1 fw-semibold">KP OWL</span>
                        </div>
                        <h4 class="card-title fw-bold mb-2 text-dark">Online Learning Record</h4>
                        <p class="text-muted small mb-3" style="line-height: 1.5;">
                            Export your completed digital courses, videos, documents, and playlists taken on the OWL Learning Hub platform.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a href="{{ route('user.learning.record.export', ['source' => 'online']) }}"
                           class="btn btn-primary w-100 rounded-3 d-flex align-items-center justify-content-center gap-2"
                           title="Download Online Record (.xlsx)">
                            <i class="ti ti-download"></i>
                            <span>Export Online (.xlsx)</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Offline Record --}}
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 learning-record-card bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="avatar avatar-md rounded-3" style="background-color: #fef3c7; color: #d97706; width: 44px; height: 44px;">
                                <i class="ti ti-certificate fs-2"></i>
                            </span>
                            <span class="badge bg-amber-lt text-amber rounded-pill px-2.5 py-1 fw-semibold">Non KP OWL</span>
                        </div>
                        <h4 class="card-title fw-bold mb-2 text-dark">Offline Training Record</h4>
                        <p class="text-muted small mb-3" style="line-height: 1.5;">
                            Export your in-house training sessions, external certifications, seminars, and offline classroom programs.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a href="{{ route('user.learning.record.export', ['source' => 'offline']) }}"
                           class="btn btn-outline-primary w-100 rounded-3 d-flex align-items-center justify-content-center gap-2"
                           title="Download Offline Record (.xlsx)">
                            <i class="ti ti-download"></i>
                            <span>Export Offline (.xlsx)</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: All Combined Record --}}
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 learning-record-card bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="avatar avatar-md rounded-3" style="background-color: #ede9fe; color: #7c3aed; width: 44px; height: 44px;">
                                <i class="ti ti-stack-2 fs-2"></i>
                            </span>
                            <span class="badge bg-purple-lt text-purple rounded-pill px-2.5 py-1 fw-semibold">All Sources</span>
                        </div>
                        <h4 class="card-title fw-bold mb-2 text-dark">Combined Learning Record</h4>
                        <p class="text-muted small mb-3" style="line-height: 1.5;">
                            Export a consolidated report combining all Online and Offline learning histories with source identifiers and calculated hours.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a href="{{ route('user.learning.record.export', ['source' => 'all']) }}"
                           class="btn btn-outline-primary w-100 rounded-3 d-flex align-items-center justify-content-center gap-2"
                           title="Download Combined Record (.xlsx)">
                            <i class="ti ti-download"></i>
                            <span>Export All (.xlsx)</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
