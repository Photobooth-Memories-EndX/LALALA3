@props(['value'])

<label {{ $attributes->merge(['class' => 'font-weight-bold']) }}>
    {{ $value ?? $slot }}
</label>
