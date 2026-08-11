<template data-learning-ongoing-hero>
    <x-shared.hero-learning-ongoing
        mode="mooc"
        :count="0"
    />
</template>

<section class="learning-section-wrapper mooc-section-wrapper">
    <div class="learning-section-padding py-4 px-2 px-md-3 learning-dashboard-container">
        <div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card ongoing-filter-card">
            <div class="card-body p-4">
                <div class="ongoing-filter-heading">
                    <div class="ongoing-filter-heading-copy">
                        <h3 class="card-title mb-1">MOOC</h3>
                        <div class="text-muted small">Massive Open Online Course</div>
                    </div>
                    <x-learning.ongoing-mode-switch mode="mooc" />
                </div>
            </div>
        </div>

        <article class="card border-0 shadow-sm rounded-4 mooc-coming-soon-card">
            <div class="card-body text-center">
                <div class="mooc-illustration" aria-hidden="true">
                    <span class="mooc-illustration-orbit"></span>
                    <span class="mooc-illustration-orbit mooc-illustration-orbit-two"></span>
                    <span class="mooc-illustration-icon"><i class="ti ti-world"></i></span>
                </div>

                <span class="badge mooc-status-badge">
                    <i class="ti ti-progress me-1" aria-hidden="true"></i>
                    On Going Development
                </span>

                <h2 class="mooc-coming-soon-title">MOOC (Massive Open Online Course) is coming soon</h2>
                <p class="mooc-coming-soon-lead">
                    This feature is currently under development. We are preparing an integrated learning experience
                    with external learning platforms.
                </p>
                <p class="text-muted mb-0">Please check back in the next release.</p>
            </div>
        </article>
    </div>
</section>
