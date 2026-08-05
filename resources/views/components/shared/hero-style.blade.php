@once
    @push('styles')
        <style>
.shared-learning-hero {
    --owl-primary-rgb: 5, 80, 125;
    --kp-hero-primary-rgb: 5, 80, 125;
    --kp-hero-dark-rgb: 5, 80, 125;
    
    /* REVISI WARNA: Biru Krakatau Posco (#05507d) dan Hijau Tua (#0f5132) */
    --kp-hero-light-rgb: 0, 82, 204;         /* Biru utama (On Going) */
    --kp-hero-completed-rgb: 34, 197, 94;    /* Hijau (Completed) */

    /* 1. Perluas jangkauan overlay ke kanan (dari 45% menjadi 55% - 60%) */
    --hero-overlay-width: 58%;

    /* 2. Buat gradasi memudar lebih lembut (smooth gradient stops) */
    --hero-overlay: linear-gradient(
        90deg,
        rgba(0, 27, 68, .96) 0%,
        rgba(var(--kp-hero-primary-rgb), .88) 50%,
        rgba(var(--kp-hero-light-rgb), .46) 80%,
        rgba(var(--kp-hero-light-rgb), 0) 100%
    );

    display: flex;
    align-items: center;
    isolation: isolate;
    position: relative;
    width: 100%;
    aspect-ratio: 1600 / 320;
    box-sizing: border-box;
    min-height: 238px;
    overflow: hidden;
    padding: 20px 34px;
    border-radius: 22px;
    background-image: var(--hero-bg-image);
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    box-shadow: 0 20px 45px rgba(15, 23, 42, .12);
}
            .shared-learning-hero::before {
                content: "";
                position: absolute;
                inset: 0 auto 0 0;
                z-index: 0;
                width: var(--hero-overlay-width);
                border-radius: inherit;
                background: var(--hero-overlay);
                pointer-events: none;
            }

            .shared-learning-hero::after {
                content: none;
            }

            .shared-learning-hero > .row {
                display: flex;
                flex: 1 1 auto;
                align-items: center;
                min-height: 0;
            }

            .shared-hero-toolbar {
                position: absolute;
                top: 14px;
                right: 16px;
                z-index: 3;
            }

            .shared-hero-upload-form {
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 0;
            }

            .shared-hero-upload,
            .shared-hero-save {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                height: 42px;
                min-width: 42px;
                margin: 0;
                border: 1px solid rgba(15, 23, 42, .06);
                border-radius: 12px;
                background: #fff;
                color: #1f2937;
                box-shadow: 0 10px 25px rgba(15, 23, 42, .12);
                font-size: 12px;
                font-weight: 600;
                line-height: 1;
                cursor: pointer;
                transition: background-color .25s ease, box-shadow .25s ease,
                    border-color .25s ease, color .25s ease, transform .25s ease;
            }

            .shared-hero-upload {
                width: 42px;
                padding: 0;
            }

            .shared-hero-save {
                width: 42px;
                padding: 0;
            }

            .shared-hero-upload:hover,
            .shared-hero-save:hover {
                color: #111827;
                background: #f8fafc;
                border-color: rgba(15, 23, 42, .1);
                box-shadow: 0 14px 30px rgba(15, 23, 42, .16);
                transform: translateY(-1px);
            }

            .shared-hero-upload:active,
            .shared-hero-save:active {
                background: #f1f5f9;
                box-shadow: 0 6px 16px rgba(15, 23, 42, .12);
                transform: translateY(0);
            }

            .shared-hero-upload:focus-visible,
            .shared-hero-save:focus-visible,
            .shared-hero-upload-form:focus-within .shared-hero-upload {
                outline: 3px solid rgba(var(--owl-primary-rgb), .38);
                outline-offset: 3px;
            }

            .shared-hero-upload i,
            .shared-hero-save i {
                font-size: 20px;
                line-height: 1;
            }

            .shared-hero-save:disabled {
                cursor: wait;
                opacity: .9;
            }

            .shared-hero-save .spinner-border {
                width: 18px;
                height: 18px;
                border-width: 2px;
            }

            .shared-hero-content {
                position: relative;
                z-index: 2;
            }

            .shared-hero-profile {
                display: flex;
                min-width: 0;
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
                justify-content: center;
            }

            .shared-hero-label {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                width: fit-content;
                margin-bottom: 10px;
                padding: 6px 11px;
                border-radius: 999px;
                background: rgba(255, 255, 255, .14);
                color: #fff;
                font-size: 11px;
                font-weight: 600;
                line-height: 1;
                text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
            }

            .shared-hero-title {
                margin: 0 0 3px;
                color: rgba(255, 255, 255, .85);
                font-size: 15px;
                font-weight: 400;
                line-height: 1.2;
                letter-spacing: 0;
                text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
            }

            .shared-hero-name {
                margin: 0;
                color: #fff;
                font-size: clamp(26px, 3.2vw, 38px);
                font-weight: 700;
                line-height: 1.04;
                letter-spacing: 0;
                overflow-wrap: anywhere;
                text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
            }

            .shared-hero-subtitle {
                max-width: 560px;
                margin: 12px 0 14px;
                color: rgba(255, 255, 255, .9);
                font-size: 14px;
                font-weight: 400;
                line-height: 1.55;
                letter-spacing: 0;
                text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
            }

            .shared-hero-pill {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
                padding: 8px 14px;
                border-radius: 999px;
                background: rgba(255, 255, 255, .16);
                color: #fff;
                font-size: 13px;
                font-weight: 600;
                line-height: 1.2;
                text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
            }

            .shared-hero-summary {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12px;
                width: max-content;
                max-width: 100%;
                min-height: 0;
                margin: 0 auto;
                align-self: center;
                padding: 10px 12px;
                border: 1px solid rgba(255, 255, 255, .14);
                border-radius: 18px;
                background: #0a2540;
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }

            .shared-hero-donut {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 118px;
                height: 118px;
                flex: 0 0 118px;
                border-radius: 50%;
                box-shadow: 0 14px 28px rgba(0, 0, 0, .18), inset 0 0 0 1px rgba(255, 255, 255, .08);
                transition: transform .25s;
            }

            .shared-hero-donut:hover {
                transform: scale(1.02);
            }

            .shared-hero-donut-hit {
                position: absolute;
                inset: 0;
                z-index: 1;
                border-radius: 50%;
                background: transparent;
                cursor: pointer;
                transition: background-color .2s;
            }

            .shared-hero-donut-hit-completed:hover {
                background: rgba(var(--kp-hero-completed-rgb), .16);
            }

            .shared-hero-donut-hit-ongoing:hover {
                background: rgba(0, 168, 255, .18);
            }

            .shared-hero-donut-inner {
                position: relative;
                z-index: 2;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 86px;
                height: 86px;
                flex-direction: column;
                border-radius: 50%;
                background: #ffffff;
                box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08);
            }

            .shared-hero-donut-inner strong {
                color: #0a2540;
                font-size: 22px;
                font-weight: 700;
                line-height: 1;
            }

            .shared-hero-donut-inner small {
                width: 66px;
                margin-top: 4px;
                color: #0a2540;
                font-size: 7px;
                font-weight: 600;
                line-height: 1.2;
                text-align: center;
                text-transform: uppercase;
                letter-spacing: .8px;
            }

            .shared-hero-summary-detail {
                display: flex;
                min-width: 150px;
                max-width: 190px;
                align-self: stretch;
                flex-direction: column;
                justify-content: center;
            }

            .shared-hero-summary-title {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 28px;
                margin: 0 0 6px;
                color: #ffffff;
                font-size: 13px;
                font-weight: 600;
                line-height: 1.25;
                letter-spacing: 0;
                text-align: center;
                text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
            }

            .shared-hero-summary-list {
                display: flex;
                width: 100%;
                flex-direction: column;
                gap: 6px;
            }

            .shared-hero-summary-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                min-height: 22px;
                gap: 12px;
            }

            .shared-hero-summary-meta {
                display: flex;
                align-items: center;
                min-width: 0;
                gap: 10px;
            }

            .shared-hero-summary-label {
                color: #ffffff;
                font-size: 12px;
                font-weight: 500;
                line-height: 1.2;
                text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
                text-decoration: none;
            }

            .shared-hero-summary-label:hover,
            .shared-hero-summary-label:focus-visible {
                color: #fff;
                text-decoration: underline;
            }

            .shared-hero-summary-value {
                color: #fff;
                font-size: 13px;
                font-weight: 700;
                line-height: 1.2;
                text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
            }

            .shared-hero-stat-dot {
                display: inline-block;
                width: 8px;
                height: 8px;
                flex: 0 0 8px;
                border-radius: 50%;
            }

            .shared-hero-stat-dot.total {
                background: #ececec;
                box-shadow: 0 0 6px rgba(255, 255, 255, .4);
            }

            .shared-hero-stat-dot.completed {
                background: rgb(var(--kp-hero-completed-rgb));
                box-shadow: 0 0 8px rgba(var(--kp-hero-completed-rgb), .5);
            }

            .shared-hero-stat-dot.ongoing {
                background: #00a8ff;
                box-shadow: 0 0 8px rgba(0, 168, 255, .5);
            }

            .shared-learning-hero .shared-hero-summary-row:has(.shared-hero-stat-dot.completed) .shared-hero-summary-label,
            .shared-learning-hero .shared-hero-summary-row:has(.shared-hero-stat-dot.completed) .shared-hero-summary-value {
                color: #ffffff;
            }

            [data-bs-theme="dark"] .shared-learning-hero,
            body.dark-mode .shared-learning-hero {
                box-shadow: 0 18px 42px rgba(0, 0, 0, .34);
            }

            [data-bs-theme="dark"] .shared-hero-summary,
            body.dark-mode .shared-hero-summary {
                border-color: rgba(255, 255, 255, .1);
                background: #0a2540;
            }

            [data-bs-theme="dark"] .shared-hero-donut-inner,
            body.dark-mode .shared-hero-donut-inner {
                background: #ffffff;
            }

            @media (min-width: 992px) {
                .shared-learning-hero > .row {
                    --bs-gutter-y: 0;

                    margin-top: 0;
                    margin-bottom: 0;
                }

                .shared-learning-hero--compact-summary .shared-hero-summary {
                    margin-right: 0;
                    margin-left: auto;
                    transform: translateX(-40px);
                }
            }

            @media (max-width: 991.98px) {
                .shared-learning-hero {
                    --hero-overlay-width: 72%;
                    aspect-ratio: auto;
                    min-height: auto;
                    padding: 28px 24px;
                    text-align: left;
                }

                .shared-learning-hero > .row {
                    min-height: 0;
                }

                .shared-hero-summary {
                    justify-content: flex-start;
                }
            }

            @media (min-width: 768px) and (max-width: 1199.98px) {
                .shared-learning-hero--compact-summary .shared-hero-summary {
                    margin-right: 0;
                    margin-left: auto;
                    transform: translateX(-30px);
                }
            }

            @media (max-width: 767.98px) {
                .shared-learning-hero {
                    --hero-overlay-width: 100%;
                    --hero-overlay: linear-gradient(
                        180deg,
                        rgba(0, 27, 68, .98) 0%,
                        rgba(var(--kp-hero-primary-rgb), .92) 38%,
                        rgba(var(--kp-hero-light-rgb), .68) 68%,
                        rgba(var(--kp-hero-light-rgb), .34) 84%,
                        rgba(var(--kp-hero-light-rgb), 0) 100%
                    );

                    padding: 54px 18px 20px;
                    border-radius: 18px;
                }

                .shared-learning-hero::before {
                    inset: 0;
                    width: 100%;
                    height: 100%;
                }

                .shared-hero-toolbar {
                    top: 12px;
                    right: 12px;
                }

                .shared-hero-upload {
                    width: 40px;
                    height: 40px;
                    min-width: 40px;
                    padding: 0;
                }

                .shared-hero-save {
                    width: 40px;
                    height: 40px;
                    min-width: 40px;
                }

                .shared-hero-name {
                    font-size: 24px;
                }

                .shared-hero-subtitle {
                    margin: 12px 0 14px;
                    font-size: 13px;
                }

                .shared-learning-hero .shared-hero-summary {
                    box-sizing: border-box;
                    width: 100%;
                    min-width: 0;
                    flex-direction: row !important;
                    align-items: center;
                    justify-content: flex-start;
                    gap: 14px;
                    padding: 12px;
                }

                .shared-learning-hero .shared-hero-donut {
                    width: 90px;
                    height: 90px;
                    flex: 0 0 90px;
                }

                .shared-learning-hero .shared-hero-donut-inner {
                    width: 66px;
                    height: 66px;
                }

                .shared-learning-hero .shared-hero-summary-detail {
                    width: auto;
                    min-width: 0;
                    max-width: none;
                    flex: 1 1 0;
                    align-self: center;
                    text-align: left;
                }

                .shared-learning-hero .shared-hero-summary-title {
                    justify-content: flex-start;
                    min-height: auto;
                    text-align: left;
                }
            }
        </style>
    @endpush
@endonce
