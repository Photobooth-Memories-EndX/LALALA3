@php

    $type = $setting->certificate_type ?? '-';

    $typeBadge = [
        'content' => [
            'class' => 'bg-blue-lt text-blue',
            'icon' => 'ti ti-book'
        ],
        'playlist' => [
            'class' => 'bg-purple-lt text-purple',
            'icon' => 'ti ti-route'
        ],
        'global' => [
            'class' => 'bg-green-lt text-green',
            'icon' => 'ti ti-world'
        ],
    ];

    $badgeClass = $typeBadge[$type]['class'] ?? 'bg-secondary-lt text-secondary';
    $badgeIcon = $typeBadge[$type]['icon'] ?? 'ti ti-circle';

@endphp

<tr class="certificate-row">

    {{-- TYPE --}}
    <td>

        <span class="badge {{ $badgeClass }}">
            <i class="{{ $badgeIcon }} mr-1"></i>
            {{ ucfirst($type) }}
        </span>

    </td>

    {{-- NAME --}}
    <td style="min-width:300px;">

        @if($type === 'content')

            @if($setting->content)

                <a href="{{ route('content.edit', $setting->content->id) }}"
                   class="font-weight-bold text-primary">

                    {{ $setting->content->title }}

                </a>

            @else

                <span class="text-muted">
                    Content Not Found
                </span>

            @endif

        @elseif($type === 'playlist')

            @if($setting->trainingPlan)

                <a href="{{ route('trainingplan.edit', $setting->trainingPlan->id) }}"
                   class="font-weight-bold text-primary">

                    {{ $setting->trainingPlan->title }}

                </a>

            @else

                <span class="text-muted">
                    Playlist Not Found
                </span>

            @endif

        @elseif($type === 'global')

            <span class="font-weight-bold">
                Global Certificate
            </span>

        @endif

    </td>

    {{-- ASSESSMENT --}}
    <td>

        @if($setting->assessment)

            <a href="{{ route('assesment.edit', $setting->assessment->id) }}"
               class="text-primary">

                {{ $setting->assessment->title }}

            </a>

            @if($setting->is_assessment_required)

                <div class="small text-success mt-1">

                    <i class="ti ti-circle-check"></i>
                    Assessment Required

                </div>

            @endif

        @else

            <span class="badge bg-yellow-lt text-yellow">

                Optional

            </span>

        @endif

    </td>

    {{-- MIN PROGRESS --}}
    <td>

        <span class="badge bg-blue-lt text-blue">

            {{ $setting->minimum_progress }}%

        </span>

    </td>

    {{-- MIN SCORE --}}
    <td>

        @if($setting->is_assessment_required)

            <span class="badge bg-green-lt text-green">

                {{ $setting->minimum_score }}

            </span>

        @else

            <span class="text-muted">

                -

            </span>

        @endif

    </td>

    {{-- ISSUER --}}
    <td>

        <div class="d-flex align-items-center">

            @if($setting->issuer_logo)

                <img src="{{ asset($setting->issuer_logo) }}"
                     width="28"
                     height="28"
                     class="rounded mr-2">

            @endif

            <div>

                {{ $setting->issuer_name }}

            </div>

        </div>

    </td>

    {{-- STATUS --}}
    <td>

        @if($setting->is_active)

            <span class="badge bg-green-lt text-green">

                ON

            </span>

        @else

            <span class="badge bg-red-lt text-red">

                OFF

            </span>

        @endif

    </td>

    {{-- ACTION --}}
    <td>

        <div class="btn-list flex-nowrap">

            <button type="button"
                    class="btn btn-sm btn-outline-primary"
                    data-toggle="modal"
                    data-target="#editCertificateSettingModal{{ $setting->id }}">

                <i class="ti ti-edit"></i>

            </button>

            @if($setting->certificate_template)
                <a href="{{ route('certificate.setting.preview.template', $setting->id) }}"
                target="_blank"
                class="btn btn-sm btn-outline-info"
                title="Example Preview">
                    <i class="ti ti-file-search"></i>
                </a>
            @endif

            <a href="{{ route('certificate.setting.preview.generated', $setting->id) }}"
            target="_blank"
            class="btn btn-sm btn-outline-success"
            title="Preview Generated Certificate">
                <i class="ti ti-certificate"></i>
            </a>

            <form method="POST"
                  action="{{ route('certificate.setting.destroy', $setting->id) }}"
                  onsubmit="return confirm('Delete certificate setting?')">

                @csrf
                @method('DELETE')

                <button type="submit"
                        class="btn btn-sm btn-outline-danger">

                    <i class="ti ti-trash"></i>

                </button>

            </form>

        </div>

    </td>

</tr>