@php
    $user = auth()->user();

    $ongoingCourseVal = $ongoingCourse ?? 84;
    $completedCourseVal = $completedCourse ?? 58;

    // Total Course adalah penjumlahan dari Completed + On Going
    $totalCourseVal = $ongoingCourseVal + $completedCourseVal;

    // Nilai di dalam lingkaran jam belajar (Learning Hours)
    $learningHours = $learningHours ?? 20.8;

    $totalLearning = max($totalCourseVal, 1);
    $completedPercent = round(($completedCourseVal / $totalLearning) * 100, 2);

    $donutBlend = 1.2;
    $donutBlendStart = max($completedPercent - $donutBlend, 0);
    $donutBlendEnd = min($completedPercent + $donutBlend, 100);

    $donutSlicePolygon = function ($fromPct, $toPct) {
        $points = ['50% 50%'];
        $fromDeg = $fromPct * 3.6;
        $toDeg = $toPct * 3.6;
        $range = $toDeg - $fromDeg;
        $steps = max(2, (int) ceil(abs($range) / 12));

        for ($i = 0; $i <= $steps; $i++) {
            $deg = $fromDeg + ($range * $i / $steps);
            $rad = deg2rad($deg);

            $x = 50 + 50 * sin($rad);
            $y = 50 - 50 * cos($rad);

            $points[] = round($x, 2) . '%' . ' ' . round($y, 2) . '%';
        }

        return 'polygon(' . implode(', ', $points) . ')';
    };

    $completedSlicePath = $donutSlicePolygon(0, $completedPercent);
    $ongoingSlicePath = $donutSlicePolygon($completedPercent, 100);

    $defaultHeroImage = asset('img/owl-dashboard.png');
    $coverFiles = glob(public_path('storage/profile/cover_' . $user->id . '.*')) ?: [];
    $coverFile = count($coverFiles) ? basename($coverFiles[0]) : null;
    $heroImage = $coverFile ? asset('storage/profile/' . $coverFile) : $defaultHeroImage;

    $avatarFile = filled(data_get($user, 'avatar'))
        ? public_path('storage/profile/' . $user->avatar)
        : null;

    if ($avatarFile && file_exists($avatarFile)) {
        $avatarImage = asset('storage/profile/' . $user->avatar);
    } else {
        $avatarImage = asset('storage/profile/default.jpg');
    }
@endphp

<div class="home-modern-hero compact-home-hero" style="--hero-bg-image: url('{{ $heroImage }}');">
    <div class="compact-hero-bg-preview" aria-hidden="true"></div>

    <div class="compact-hero-toolbar">
        <form action="{{ route('user.updateprofile') }}"
              method="POST"
              enctype="multipart/form-data"
              class="compact-hero-upload-form"
              id="homeHeroCoverForm">
            @csrf
            <input type="hidden" name="source" value="home">
            <label class="compact-hero-upload" for="homeHeroCoverInput">
                <i class="ti ti-photo-up"></i>
                <span>Change cover</span>
            </label>
            <input type="file"
                   name="cover"
                   id="homeHeroCoverInput"
                   class="d-none"
                   accept="image/png,image/jpeg,image/jpg,image/webp">
        </form>
    </div>

    <div class="row align-items-center g-3 compact-hero-content">
        <div class="col-lg-7">
            <div class="compact-hero-profile">
                <div class="compact-hero-avatar-wrap">
                    <form action="{{ route('user.updateprofile') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          id="homeHeroAvatarForm">
                        @csrf
                        <input type="hidden" name="source" value="home">
                        <input type="file"
                               name="avatar"
                               id="homeHeroAvatarInput"
                               class="d-none"
                               accept="image/png,image/jpeg,image/jpg,image/webp">
                        <label class="compact-hero-avatar"
                               for="homeHeroAvatarInput"
                               style="background-image: url('{{ $avatarImage }}');"
                               title="Change profile photo"></label>
                    </form>
                </div>
                <div class="compact-hero-user-info">
                    <span class="home-modern-hero-label compact-hero-label">
                        <i class="ti ti-school"></i>
                        Learning Management System
                    </span>
                    <h1 class="home-modern-hero-greeting compact-hero-greeting">Welcome back,</h1>
                    <h2 class="home-modern-hero-name compact-hero-name">{{ $user->name ?? 'ESTIHONO HERMAWAN' }}</h2>
                </div>
            </div>
            <div class="compact-avatar-message" id="homeHeroAvatarMessage" aria-live="polite"></div>

                    <p class="home-modern-hero-subtitle compact-hero-subtitle">
                        Continuing journey as <strong>{{ $user->team ?? 'Training Team' }}</strong>
                    </p>

            <div class="home-modern-hero-actions">
                <span class="home-modern-hero-stat-pill compact-hero-pill">
                    <i class="ti ti-flame me-1"></i>
                    <strong>Keep Learning</strong>
                </span>
            </div>
        </div>

        <div class="col-lg-5 mt-3 mt-lg-0">
            <div class="compact-summary-panel">
                <div class="home-modern-donut-outer compact-donut"
                     style="background: conic-gradient(#22c55e 0%, #22c55e {{ $donutBlendStart }}%, #38bdf8 {{ $donutBlendEnd }}%, #38bdf8 100%);">

                    <div class="home-modern-donut-hit home-modern-donut-hit-completed"
                         style="clip-path:{{ $completedSlicePath }}"
                         data-bs-toggle="tooltip"
                         data-bs-placement="top"
                         title="Completed {{ $completedCourseVal }} Learning">
                    </div>

                    <div class="home-modern-donut-hit home-modern-donut-hit-ongoing"
                         style="clip-path:{{ $ongoingSlicePath }}"
                         data-bs-toggle="tooltip"
                         data-bs-placement="top"
                         title="On Going {{ $ongoingCourseVal }} Learning">
                    </div>

                    <div class="home-modern-donut-inner compact-donut-inner">
                        <strong>{{ $learningHours }}</strong>
                        <small>Learning Hours</small>
                    </div>
                </div>

                <div class="compact-summary-detail">
                    <div class="home-modern-summary-title compact-summary-title">
                        Your Learning Summary
                    </div>

                    <div class="tabler-clean-list-container compact-summary-list">
                        <div class="tabler-clean-row compact-summary-row">
                            <div class="tabler-clean-meta">
                                <span class="home-modern-stat-dot total"></span>
                                <span class="clean-label">Total Course</span>
                            </div>
                            <div class="clean-value">{{ $totalCourseVal }}</div>
                        </div>

                        <div class="tabler-clean-row compact-summary-row">
                            <div class="tabler-clean-meta">
                                <span class="home-modern-stat-dot completed"></span>
                                <span class="clean-label">Completed</span>
                            </div>
                            <div class="clean-value">{{ $completedCourseVal }}</div>
                        </div>

                        <div class="tabler-clean-row compact-summary-row">
                            <div class="tabler-clean-meta">
                                <span class="home-modern-stat-dot ongoing"></span>
                                <span class="clean-label">On Going</span>
                            </div>
                            <div class="clean-value">{{ $ongoingCourseVal }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .compact-home-hero {
        isolation: isolate;
        min-height: 238px;
        padding: 28px 34px;
        border-radius: 22px;
        background-image:
            linear-gradient(90deg, rgba(7, 23, 57, .9) 0%, rgba(18, 72, 167, .82) 48%, rgba(42, 114, 202, .66) 100%),
            var(--hero-bg-image);
        background-size: cover;
        background-position: center;
        box-shadow: 0 18px 44px rgba(15, 23, 42, .16);
    }

    .compact-home-hero::before {
        width: 300px;
        height: 300px;
        top: -126px;
        right: -96px;
        background: rgba(255, 255, 255, .08);
        z-index: -1;
    }

    .compact-home-hero::after {
        display: none;
    }

    .compact-home-hero .row {
        min-height: 182px;
    }

    .compact-hero-bg-preview {
        position: absolute;
        inset: 0;
        z-index: -2;
        background-image: var(--hero-bg-image);
        background-size: cover;
        background-position: center;
        transform: scale(1.02);
    }

    .compact-hero-bg-preview::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 82% 20%, rgba(255, 255, 255, .16), transparent 32%),
            linear-gradient(90deg, rgba(6, 22, 52, .9), rgba(20, 72, 156, .76));
    }

    .compact-hero-toolbar {
        position: absolute;
        top: 14px;
        right: 16px;
        z-index: 3;
    }

    .compact-hero-upload {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        padding: 7px 11px;
        border: 1px solid rgba(255, 255, 255, .2);
        border-radius: 999px;
        background: rgba(15, 23, 42, .34);
        color: rgba(255, 255, 255, .9);
        font-size: 12px;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
        backdrop-filter: blur(10px);
        transition: background-color .2s, border-color .2s, transform .2s;
    }

    .compact-hero-upload:hover {
        color: #fff;
        background: rgba(37, 99, 235, .55);
        border-color: rgba(255, 255, 255, .38);
        transform: translateY(-1px);
    }

    .compact-hero-upload-form {
        margin: 0;
    }

    .compact-hero-content {
        position: relative;
        z-index: 2;
    }

    .compact-hero-profile {
        display: flex;
        align-items: center;
        gap: 18px;
        min-width: 0;
        width: 100%;
    }

    .compact-hero-avatar-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        flex: 0 0 64px;
        min-width: 64px;
    }

    .compact-hero-avatar-wrap form {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        margin: 0;
    }

    .compact-hero-user-info {
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
    }

    .compact-hero-avatar {
        display: block;
        width: 64px;
        height: 64px;
        flex: 0 0 64px;
        min-width: 64px;
        max-width: 64px;
        border-radius: 18px;
        background-size: cover;
        background-position: center center;
        background-repeat: no-repeat;
        border: 2px solid rgba(255, 255, 255, .72);
        box-shadow: 0 12px 28px rgba(15, 23, 42, .22);
        cursor: pointer;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .compact-hero-avatar:hover {
        border-color: rgba(255, 255, 255, .95);
        box-shadow: 0 14px 30px rgba(15, 23, 42, .28);
    }

    .compact-hero-avatar.is-uploading {
        cursor: wait;
        opacity: .7;
    }

    .compact-avatar-message {
        width: 100%;
        min-height: 14px;
        margin: 8px 0 0 82px;
        color: rgba(255, 255, 255, .78);
        font-size: 10px;
        font-weight: 600;
        line-height: 1.3;
        text-align: left;
    }

    .compact-avatar-message.is-success {
        color: #bbf7d0;
    }

    .compact-avatar-message.is-error {
        color: #fecaca;
    }

    .compact-hero-label {
        gap: 6px;
        padding: 6px 11px;
        margin-bottom: 8px;
        font-size: 11px;
        background: rgba(255, 255, 255, .14);
        backdrop-filter: blur(8px);
    }

    .compact-hero-greeting {
        margin: 0 0 3px;
        font-size: 15px;
    }

    .compact-hero-name {
        margin: 0;
        font-size: clamp(26px, 3.2vw, 38px);
        line-height: 1.04;
        letter-spacing: 0;
        overflow-wrap: anywhere;
    }

    .compact-hero-subtitle {
        max-width: 560px;
        margin: 13px 0 16px;
        font-size: 14px;
        line-height: 1.55;
    }

    .compact-hero-pill {
        gap: 4px;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 13px;
        background: rgba(255, 255, 255, .16);
        backdrop-filter: blur(8px);
    }

    .compact-summary-panel {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: max-content;
        max-width: 100%;
        min-height: 0;
        padding: 10px 12px;
        margin: 0 auto;
        border: 1px solid rgba(255, 255, 255, .14);
        border-radius: 18px;
        background: rgba(15, 23, 42, .2);
        backdrop-filter: blur(12px);
    }

    .compact-donut {
        width: 118px;
        height: 118px;
        flex: 0 0 118px;
        box-shadow: 0 14px 28px rgba(0, 0, 0, .18), inset 0 0 0 1px rgba(255, 255, 255, .08);
    }

    .compact-donut-inner {
        width: 86px;
        height: 86px;
        background: rgba(12, 37, 83, .92);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08);
    }

    .compact-donut-inner strong {
        font-size: 22px;
    }

    .compact-donut-inner small {
        width: 66px;
        margin-top: 4px;
        font-size: 7px;
        line-height: 1.2;
        text-align: center;
    }

    .compact-summary-detail {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 150px;
        max-width: 190px;
        align-self: stretch;
    }

    .compact-summary-title {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        margin: 0 0 6px;
        font-size: 13px;
        line-height: 1.25;
        letter-spacing: 0;
    }

    .compact-summary-list {
        gap: 6px;
        padding-top: 0;
    }

    .compact-summary-row {
        gap: 12px;
        min-height: 22px;
    }

    .compact-summary-row .clean-label {
        font-size: 12px;
    }

    .compact-summary-row .clean-value {
        font-size: 13px;
    }

    @media (max-width: 991.98px) {
        .compact-home-hero {
            min-height: auto;
            padding: 28px 24px;
            text-align: left;
        }

        .compact-home-hero .row {
            min-height: 0;
        }

        .compact-home-hero .col-lg-8 {
            align-items: flex-start;
            margin-bottom: 0;
        }

        .compact-summary-panel {
            justify-content: flex-start;
            width: max-content;
            max-width: 100%;
        }
    }

    @media (max-width: 767.98px) {
        .compact-home-hero {
            padding: 54px 18px 20px;
            border-radius: 18px;
        }

        .compact-hero-toolbar {
            top: 12px;
            right: 12px;
        }

        .compact-hero-upload span {
            display: none;
        }

        .compact-hero-upload {
            width: 34px;
            height: 34px;
            justify-content: center;
            padding: 0;
        }

        .compact-hero-profile {
            gap: 12px;
            align-items: center;
        }

        .compact-hero-avatar-wrap {
            width: 54px;
            height: 54px;
            flex-basis: 54px;
            min-width: 54px;
        }

        .compact-hero-avatar-wrap form {
            width: 54px;
            height: 54px;
        }

        .compact-hero-avatar {
            width: 54px;
            height: 54px;
            flex-basis: 54px;
            min-width: 54px;
            max-width: 54px;
            border-radius: 15px;
        }

        .compact-avatar-message {
            margin-left: 66px;
        }

        .compact-hero-name {
            font-size: 24px;
            overflow-wrap: anywhere;
        }

        .compact-hero-subtitle {
            font-size: 13px;
            margin: 12px 0 14px;
        }

        .compact-summary-panel {
            flex-direction: column;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 12px;
        }

        .compact-donut {
            width: 112px;
            height: 112px;
            flex-basis: 112px;
        }

        .compact-donut-inner {
            width: 82px;
            height: 82px;
        }

        .compact-summary-detail {
            width: 100%;
            max-width: 260px;
            min-width: 0;
        }

        .compact-summary-title {
            min-height: auto;
        }
    }

    [data-bs-theme="dark"] .compact-home-hero {
        background-image:
            linear-gradient(90deg, rgba(3, 10, 24, .94) 0%, rgba(11, 31, 68, .86) 54%, rgba(20, 74, 122, .7) 100%),
            var(--hero-bg-image);
        box-shadow: 0 18px 42px rgba(0, 0, 0, .34);
    }

    [data-bs-theme="dark"] .compact-hero-bg-preview::after {
        background:
            radial-gradient(circle at 82% 20%, rgba(96, 165, 250, .13), transparent 32%),
            linear-gradient(90deg, rgba(2, 8, 20, .94), rgba(10, 30, 64, .84));
    }

    [data-bs-theme="dark"] .compact-summary-panel {
        background: rgba(2, 8, 20, .38);
        border-color: rgba(255, 255, 255, .1);
    }

    [data-bs-theme="dark"] .compact-donut-inner {
        background: rgba(5, 18, 39, .94);
    }
</style>

<script>
    $(function () {
        $('#homeHeroCoverInput').on('change', function () {
            const file = this.files && this.files[0];

            if (!file) {
                return;
            }

            const previewUrl = URL.createObjectURL(file);
            $('.compact-home-hero').css('--hero-bg-image', `url("${previewUrl}")`);
            $('#homeHeroCoverForm').trigger('submit');
        });

        const avatarMaxSize = 2 * 1024 * 1024;
        const avatarAllowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        function setAvatarMessage(message, type) {
            $('#homeHeroAvatarMessage')
                .text(message || '')
                .removeClass('is-success is-error')
                .addClass(type ? 'is-' + type : '');
        }

        $('#homeHeroAvatarInput').on('change', function () {
            const input = this;
            const file = this.files && this.files[0];
            const $avatar = $('.compact-hero-avatar');

            if (!file) {
                return;
            }

            if (!avatarAllowedTypes.includes(file.type)) {
                setAvatarMessage('Use JPG, PNG, or WEBP.', 'error');
                input.value = '';
                return;
            }

            if (file.size > avatarMaxSize) {
                setAvatarMessage('Max file size is 2 MB.', 'error');
                input.value = '';
                return;
            }

            const previewUrl = URL.createObjectURL(file);
            $('.compact-hero-avatar').css('background-image', `url("${previewUrl}")`);
            setAvatarMessage('Uploading...', '');
            $avatar.addClass('is-uploading');

            const formData = new FormData($('#homeHeroAvatarForm')[0]);

            $.ajax({
                url: $('#homeHeroAvatarForm').attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'Accept': 'application/json'
                },
                success: function (response) {
                    if (response.avatar_url) {
                        $('.compact-hero-avatar').css('background-image', `url("${response.avatar_url}")`);
                    }
                    setAvatarMessage(response.message || 'Photo updated.', 'success');
                },
                error: function (xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Upload failed.';
                    setAvatarMessage(message, 'error');
                },
                complete: function () {
                    $avatar.removeClass('is-uploading');
                    input.value = '';
                    URL.revokeObjectURL(previewUrl);
                }
            });
        });
    });
</script>
