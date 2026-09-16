<div class="col-sm-6 col-lg-4 col-xl-3 mb-3">

    <a href="{{ $url }}"
       class="text-decoration-none text-reset d-block h-100">

        <div class="card border-0 shadow-sm h-100 admin-menu-card">

            <div class="card-body p-4 d-flex flex-column">

                <div class="admin-card-icon mb-3">
                    <span class="avatar avatar-md">
                        <i class="{{ $icon }}"></i>
                    </span>
                </div>

                <h3 class="admin-card-title mb-1.5">
                    {{ $title }}
                </h3>

                <p class="admin-card-desc mb-3">
                    {{ $description }}
                </p>

                <div class="mt-auto pt-2">
                    <div class="admin-card-action d-inline-flex align-items-center">
                        <span>{{ $button }}</span>
                        <i class="ti ti-arrow-right ms-1.5"></i>
                    </div>
                </div>

            </div>

        </div>

    </a>

</div>
