@php
    $stepNumber = $stepNumber ?? 4;
@endphp

@include('main.content.components.upload-preview', ['stepNumber' => $stepNumber])
