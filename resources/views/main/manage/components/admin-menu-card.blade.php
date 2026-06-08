<div class="col-sm-6 col-lg-4 col-xl-3 mb-3">

    <a href="{{ $url }}"
       class="text-decoration-none text-reset">

        <div class="card border-0 shadow-sm h-100 admin-menu-card">

            <div class="card-body p-4">

                <span class="avatar avatar-md bg-primary-lt text-primary mb-3">
                    <i class="{{ $icon }}"></i>
                </span>

                <h3 class="mb-1">
                    {{ $title }}
                </h3>

                <p class="text-muted small mb-3">
                    {{ $description }}
                </p>

                <div class="d-flex align-items-center text-primary fw-semibold small">
                    {{ $button }}
                    <i class="ti ti-arrow-right ml-1"></i>
                </div>

            </div>

        </div>

    </a>

</div>
