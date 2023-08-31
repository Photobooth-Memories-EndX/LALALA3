@props(['disabled' => false])

@php
$class = $errors->has($attributes->get('name')) ? ' is-invalid' : '';
@endphp

<select {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => 'form-select form-control'.$class]) }}>
{{ $slot }}
</select>
@error($attributes->get('name'))
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
