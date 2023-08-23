@props(['disabled' => false, 'value'])

@php
$class = $errors->has($attributes->get('name')) ? ' is-invalid' : '';
@endphp

<textarea {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => 'form-control'.$class]) }}>{{ $value ?? $slot }}</textarea>
@error($attributes->get('name'))
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
