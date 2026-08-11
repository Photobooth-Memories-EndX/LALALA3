@props([
    'type',
    'label',
])

@php
    $class = match (strtolower((string) $type)) {
        'trainingplan', 'playlist' => 'bg-green',
        'video', 'youtube'         => 'bg-yellow text-white',
        'document', 'pdf'          => 'bg-cyan',
        'content'                  => 'bg-purple-lt text-purple',
        'global'                   => 'bg-red',
        'remedial'                 => 'bg-orange-lt text-orange',
        default                    => 'bg-secondary-lt text-secondary',
    };
@endphp

<span class="badge {{ $class }} learning-record-badge assessment-type-badge">
    {{ $label }}
</span>
