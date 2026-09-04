@props([
    'type' => null,
    'label' => null,
])

@php
    $key = strtolower(trim((string) ($label ?? $type ?? '')));
    $displayLabel = $label ?? ($type ? ucwords(str_replace('_', ' ', (string) $type)) : '-');

    $class = match (true) {
        str_contains($key, 'playlist') || in_array($key, ['trainingplan', 'playlist'], true) => 'bg-teal-lt text-teal',
        str_contains($key, 'global') || $key === 'global'                                      => 'bg-indigo-lt text-indigo',
        str_contains($key, 'content') || $key === 'content'                                   => 'bg-blue-lt text-blue',
        in_array($key, ['video', 'youtube'], true)                                           => 'bg-yellow text-white',
        in_array($key, ['document', 'pdf'], true)                                            => 'bg-cyan',
        $key === 'remedial'                                                                  => 'bg-orange-lt text-orange',
        default                                                                              => 'bg-secondary-lt text-secondary',
    };
@endphp

<span class="badge {{ $class }} learning-record-badge assessment-type-badge text-nowrap">
    {{ $displayLabel }}
</span>
