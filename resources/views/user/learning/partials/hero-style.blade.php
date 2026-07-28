<style>
    /* 1. Hero Design Tokens */
    :root {
        --learning-hero-radius: 20px;
        --learning-stat-space-1: .25rem;
        --learning-stat-space-2: .5rem;
        --learning-stat-space-3: .75rem;
        --learning-stat-panel-width: 22.5rem;
        --learning-stat-panel-height: 8rem;
        --learning-stat-panel-radius: 1rem;
        --learning-stat-card-radius: .75rem;
    }

    /* 2. Base Hero and Default Theme */
    .learning-module-hero {
        --hero-background: linear-gradient(135deg, #05507D 0%, #6AAED6 100%);
        --hero-panel-background: rgba(5, 80, 125, .2);
        --hero-panel-border: rgba(255, 255, 255, .18);
        --hero-panel-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .08),
            0 8px 20px rgba(5, 80, 125, .14);
        --hero-panel-transform: none;
        --hero-card-border: rgba(255, 255, 255, .12);
        --hero-card-radius: var(--learning-stat-card-radius);
        --hero-card-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .08),
            0 5px 12px rgba(5, 80, 125, .1);
        --hero-card-hover-shadow: var(--hero-card-shadow);
        --hero-card-hover-transform: none;
        --hero-card-transition: none;
        --hero-label-color: rgba(255, 255, 255, .86);
        --hero-label-letter-spacing: .04em;
        --hero-large-label-size: .75rem;
        --hero-small-label-size: .6875rem;
        --hero-large-value-size: 2rem;
        --hero-small-value-size: 1.25rem;
        --hero-blue: linear-gradient(135deg, rgba(5, 80, 125, .78), rgba(106, 174, 214, .34));
        --hero-dark-blue: linear-gradient(135deg, rgba(3, 44, 70, .86), rgba(5, 80, 125, .58));
        --hero-green: linear-gradient(135deg, rgba(22, 163, 74, .68), rgba(74, 222, 128, .24));
        --hero-cyan: linear-gradient(135deg, rgba(8, 145, 178, .68), rgba(103, 232, 249, .22));
        --hero-orange: linear-gradient(135deg, rgba(217, 119, 6, .72), rgba(251, 191, 36, .28));
        --hero-red: linear-gradient(135deg, rgba(220, 38, 38, .7), rgba(248, 113, 113, .24));
        border-radius: var(--learning-hero-radius) !important;
        background: var(--hero-background);
        color: #fff;
    }

    /* 3. Theme Variants: variables only */
    .learning-module-hero--ongoing {
        --hero-panel-transform: translateX(calc(-1 * var(--learning-stat-space-2)));
        --hero-blue: linear-gradient(135deg, rgba(5, 80, 125, .82), rgba(30, 64, 175, .55));
    }

    .learning-module-hero--assessment {
        --hero-card-border: transparent;
        --hero-card-radius: 14px;
        --hero-card-shadow: none;
        --hero-card-hover-shadow: none;
        --hero-card-hover-transform: none;
        --hero-card-transition: none;
        --hero-label-color: rgba(255, 255, 255, .9);
        --hero-label-letter-spacing: .03em;
        --hero-blue: #1D4ED8;
        --hero-green: #16A34A;
        --hero-cyan: #FBBF24;
        --hero-red: #EF4444;
    }

    .learning-module-hero--assessment .learning-stat-card {
        border: none;
        filter: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    /* 4. Hero Content */
    .learning-hero-record-content {
        padding-top: .0625rem;
        padding-bottom: .0625rem;
    }

    /* 5. Hero Stats Panel */
    .learning-hero-stats-panel {
        box-sizing: border-box;
        width: 100%;
        max-width: var(--learning-stat-panel-width);
        height: var(--learning-stat-panel-height);
        margin-left: auto;
        padding: var(--learning-stat-space-2);
        border: 1px solid var(--hero-panel-border);
        border-radius: var(--learning-stat-panel-radius);
        background: var(--hero-panel-background);
        box-shadow: var(--hero-panel-shadow);
        transform: var(--hero-panel-transform);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    /* 6. Layout Variants */
    .learning-hero-stats-assessment {
        display: grid;
        height: 100%;
        gap: var(--learning-stat-space-1);
        align-items: stretch;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: repeat(2, minmax(0, 1fr));
    }

    .learning-hero-stats-panel--ongoing {
        display: flex;
        align-items: stretch;
    }

    .learning-hero-stat-feature {
        width: 100%;
        height: 100%;
    }

    /* 7. Record and Certificate Stat Widget */
    .learning-stat-widget {
        --widget-gap: var(--learning-stat-space-2);
        --widget-card-radius: 14px;
        --widget-card-border: rgba(255, 255, 255, .14);
        --widget-card-shadow: none;
        display: grid;
        box-sizing: border-box;
        width: 100%;
        max-width: var(--learning-stat-panel-width);
        height: var(--learning-stat-panel-height);
        margin-left: auto;
        grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
        gap: var(--widget-gap);
        padding: var(--learning-stat-space-2);
        border: 1px solid rgba(255, 255, 255, .14);
        border-radius: var(--learning-stat-panel-radius);
        background: rgba(5, 80, 125, .2);
        box-shadow: none;
        filter: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    .learning-stat-widget-stack {
        display: grid;
        min-width: 0;
        min-height: 0;
        grid-template-rows: repeat(2, minmax(0, 1fr));
        gap: var(--widget-gap);
    }

    .learning-stat-widget-card {
        display: flex;
        container-type: inline-size;
        box-sizing: border-box;
        min-width: 0;
        min-height: 0;
        overflow: visible;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--learning-stat-space-1);
        padding: var(--learning-stat-space-2) var(--learning-stat-space-3);
        border: 1px solid var(--widget-card-border);
        border-radius: var(--widget-card-radius);
        color: #fff;
        box-shadow: none;
        filter: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        transition: none;
    }

    .learning-stat-widget-card:hover {
        box-shadow: none;
        transform: none;
    }

    .learning-stat-widget-feature {
        flex-direction: column;
        justify-content: center;
    }

    .learning-stat-widget-label {
        display: flex;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, .92);
        font-size: clamp(9px, 0.7vw, 11px);
        font-weight: 700;
        line-height: 1.1;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: .03em;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .learning-stat-widget-value {
        display: block;
        width: 100%;
        max-width: 100%;
        flex: 0 1 auto;
        overflow: visible;
        color: #fff;
        font-size: clamp(22px, 18cqw, 42px);
        font-weight: 800;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        word-break: normal;
    }

    .learning-stat-widget-value--large {
        font-size: clamp(24px, 14cqw, 42px);
    }

    .learning-stat-widget-description {
        color: rgba(255, 255, 255, .72);
        font-size: 10px;
        font-weight: 600;
        line-height: 1.2;
        text-align: center;
    }

    .learning-stat-widget-card--dark-blue {
        background: #184C73;
    }

    .learning-stat-widget-card--blue {
        background: #4B90C5;
    }

    .learning-stat-widget-card--green {
        background: #46C98A;
    }

    /* 8. Shared Legacy Hero Cards */
    .learning-stat-card {
        display: flex;
        box-sizing: border-box;
        width: 100%;
        height: 100%;
        min-width: 0;
        min-height: 0;
        overflow: visible;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--learning-stat-space-1);
        padding: var(--learning-stat-space-2) var(--learning-stat-space-3);
        border: 1px solid var(--hero-card-border);
        border-radius: var(--hero-card-radius);
        color: #fff;
        text-align: center;
        box-shadow: var(--hero-card-shadow);
        transition: var(--hero-card-transition);
    }

    .learning-stat-card:hover {
        box-shadow: var(--hero-card-hover-shadow);
        transform: var(--hero-card-hover-transform);
    }

    /* 9. Shared Legacy Hero Typography */
    .learning-stat-label,
    .learning-stat-large-label,
    .learning-stat-small-label {
        max-width: 100%;
        overflow: visible;
        color: var(--hero-label-color);
        font-size: var(--hero-small-label-size);
        font-weight: 700;
        line-height: 1.2;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: var(--hero-label-letter-spacing);
        overflow-wrap: anywhere;
        white-space: normal;
    }

    .learning-stat-large-label {
        font-size: var(--hero-large-label-size);
    }

    .learning-stat-value,
    .learning-stat-large-value,
    .learning-stat-small-value {
        max-width: 100%;
        overflow: visible;
        margin: 0;
        color: #fff;
        font-weight: 800;
        line-height: 1;
        text-align: center;
        overflow-wrap: anywhere;
        white-space: normal;
    }

    .learning-stat-large-value {
        font-size: var(--hero-large-value-size);
    }

    .learning-stat-small-value {
        font-size: var(--hero-small-value-size);
    }

    .learning-stat-large-description {
        max-width: 100%;
        color: rgba(255, 255, 255, .72);
        font-size: .625rem;
        font-weight: 600;
        line-height: 1.2;
        text-align: center;
        overflow-wrap: anywhere;
    }

    /* 10. Shared Legacy Hero Color Variants */
    .learning-stat--blue {
        background: var(--hero-blue);
    }

    .learning-stat--dark-blue {
        background: var(--hero-dark-blue);
    }

    .learning-stat--green {
        background: var(--hero-green);
    }

    .learning-stat--cyan {
        background: var(--hero-cyan);
    }

    .learning-stat--orange {
        background: var(--hero-orange);
    }

    .learning-stat--red {
        background: var(--hero-red);
    }

    /* 11. My Learning Hero Design System */
    .learning-hero-ds {
        --hero-surface: #05507D;
        --hero-primary: #2563EB;
        --hero-success: #22C55E;
        --hero-warning: #FDBA3B;
        --hero-danger: #EF4444;
        --learning-hero-border: 1px solid rgba(255, 255, 255, .18);
        --learning-hero-radius: 22px;
        background: var(--hero-surface) !important;
        border: var(--learning-hero-border) !important;
        border-radius: var(--learning-hero-radius) !important;
        box-shadow: none !important;
        filter: none !important;
        font-family: var(--tblr-font-sans-serif, Inter, sans-serif);
    }

    .learning-hero-ds::before,
    .learning-hero-ds::after {
        display: none !important;
    }

    .learning-hero-ds-badge {
        padding: 6px 10px;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.2;
        letter-spacing: 0;
    }

    .learning-hero-ds-title {
        font-size: 20px;
        font-weight: 600;
        line-height: 1.2;
        letter-spacing: -.01em;
    }

    .learning-hero-ds-subtitle,
    .learning-hero-ds-description {
        font-size: 13px;
        font-weight: 400;
        line-height: 1.4;
        letter-spacing: 0;
    }

    .learning-hero-ds-description {
        opacity: .9;
    }

    .learning-hero-ds > .card-body {
        display: flex;
        box-sizing: border-box;
        min-height: 170px;
        padding: 15px 34px !important;
        align-items: center;
    }

    .learning-hero-ds > .card-body > .row {
        width: 100%;
        min-height: 140px;
    }

    .learning-hero-ds-panel {
        display: grid;
        box-sizing: border-box;
        width: 100%;
        max-width: 360px;
        height: 140px;
        margin-left: auto;
        gap: 8px;
        padding: 8px;
        overflow: hidden;
        border: var(--learning-hero-border);
        border-radius: var(--learning-hero-radius);
        background: transparent;
        box-shadow: none;
        filter: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    .learning-hero-ds-panel--split {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .learning-hero-ds-panel--grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: repeat(2, minmax(0, 1fr));
    }

    .learning-hero-ds-panel--single {
        grid-template-columns: minmax(0, 1fr);
    }

    .learning-hero-ds-stack {
        display: grid;
        min-width: 0;
        min-height: 0;
        gap: 8px;
        grid-template-rows: repeat(2, minmax(0, 1fr));
    }

    .learning-hero-ds-card {
        display: flex;
        container-type: inline-size;
        box-sizing: border-box;
        width: 100%;
        min-width: 0;
        min-height: 0;
        padding: 4px 8px;
        overflow: hidden;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        border: var(--learning-hero-border);
        border-radius: var(--learning-hero-radius);
        color: #fff;
        text-align: center;
        box-shadow: none;
        filter: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        transition: none;
    }

    .learning-hero-ds-card:hover {
        box-shadow: none;
        transform: none;
    }

    .learning-hero-ds-card--primary,
    .learning-hero-ds-card--blue {
        background: var(--hero-primary);
    }

    .learning-hero-ds-card--secondary,
    .learning-hero-ds-card--success,
    .learning-hero-ds-card--green {
        background: var(--hero-success);
    }

    .learning-hero-ds-card--warning {
        background: var(--hero-warning);
        color: #fff;
    }

    .learning-hero-ds-card--danger {
        background: var(--hero-danger);
    }

    .learning-hero-ds-label {
        display: flex;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: inherit;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.15;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: .02em;
        overflow-wrap: normal;
        word-break: normal;
        text-wrap: balance;
        white-space: normal;
    }

    .learning-hero-ds-value {
        display: block;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
        color: inherit;
        font-size: 24px;
        font-size: clamp(20px, 18cqw, 24px);
        font-weight: 700;
        line-height: 1;
        text-align: center;
        letter-spacing: -.01em;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .learning-hero-ds-value--large {
        font-size: 32px;
        font-size: clamp(28px, 22cqw, 32px);
    }

    /* 12. Responsive */
    @media (max-width: 767.98px) {
        .learning-hero-ds > .card-body,
        .learning-hero-ds > .card-body > .row {
            min-height: auto;
        }

        .learning-hero-ds-panel {
            margin-left: 0;
        }
    }

    /* Hero statistic visibility follows the existing Bootstrap responsive classes. */
</style>
