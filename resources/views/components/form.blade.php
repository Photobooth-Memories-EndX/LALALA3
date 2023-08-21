@props(['method' => 'GET'])

@php
$spoofMethod = '';
$method = strtoupper($method);
if ($method == 'PUT' || $method == 'PATCH' || $method == 'DELETE'){
    $spoofMethod = $method;
    $method = 'POST';
}
@endphp

<form {{ 'method=' . $method }} {{ $attributes }}>
    @csrf
    @if ($spoofMethod)
        @method($spoofMethod)
    @endif

    {{ $slot }}

</form>

