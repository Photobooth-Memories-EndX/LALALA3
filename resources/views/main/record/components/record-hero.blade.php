<section class="manage-record-hero mb-4" aria-labelledby="manage-record-hero-title">
    <div class="manage-record-hero__content">
        <div class="manage-record-hero__badge">
            <i class="ti ti-folders" aria-hidden="true"></i>
            <span>MANAGE RECORD</span>
        </div>

        <h1 id="manage-record-hero-title" class="manage-record-hero__title">
            Learning Record Management
        </h1>

        <p class="manage-record-hero__description mb-0">
           Manage KP OWL and Non KP OWL learning records, including online and offline training data, participant progress, classifications, and record exports.
        </p>
    </div>
</section>

@once
    @push('styles')
        <style>
            .manage-record-hero {
                display: flex;
                align-items: center;
                position: relative;
                isolation: isolate;
                min-height: 160px;
                overflow: hidden;
                padding: clamp(24px, 3vw, 36px);
                border: 1px solid rgba(255, 255, 255, .18);
                border-radius: 20px;
                background:
                    radial-gradient(circle at 86% 16%, rgba(255, 255, 255, .18) 0%, transparent 28%),
                    linear-gradient(
                        135deg,
                        #001b44 0%,
                        #002b66 22%,
                        #004b99 50%,
                        #2a7bd1 76%,
                        #6aaed6 100%
                    );
                color: #fff;
                box-shadow: 0 14px 32px rgba(0, 27, 68, .16);
            }

            .manage-record-hero__content {
                min-width: 0;
                max-width: 800px;
            }

            .manage-record-hero__badge {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 12px;
                padding: 6px 12px;
                border: 1px solid rgba(255, 255, 255, .22);
                border-radius: 999px;
                background: rgba(255, 255, 255, .14);
                color: #fff;
                font-size: 11px;
                font-weight: 700;
                line-height: 1;
                letter-spacing: .06em;
                text-transform: uppercase;
            }

            .manage-record-hero__title {
                margin: 0 0 10px;
                color: #fff;
                font-size: clamp(22px, 2.3vw, 30px);
                font-weight: 800;
                line-height: 1.2;
                letter-spacing: -.02em;
            }

            .manage-record-hero__description {
                color: rgba(255, 255, 255, .88);
                font-size: clamp(13px, 1.1vw, 15px);
                line-height: 1.5;
            }

            [data-bs-theme="dark"] .manage-record-hero,
            body.dark-mode .manage-record-hero,
            body.theme-dark .manage-record-hero {
                border-color: rgba(255, 255, 255, .1);
                background:
                    radial-gradient(circle at 86% 16%, rgba(255, 255, 255, .08) 0%, transparent 28%),
                    linear-gradient(
                        135deg,
                        #020b18 0%,
                        #0a192f 25%,
                        #0f2e54 60%,
                        #173d6d 100%
                    );
                box-shadow: 0 14px 32px rgba(0, 0, 0, .4);
            }
        </style>
    @endpush
@endonce
