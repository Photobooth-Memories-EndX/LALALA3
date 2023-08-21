@props(['disabled' => false])

@php
$class = $errors->has($attributes->get('name')) ? ' is-invalid' : '';
@endphp

<input {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => 'form-control'.$class]) }}>
@error($attributes->get('name'))
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
