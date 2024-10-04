@php
    $url_tabler = asset('Tabler/dist/js/tabler.min.js');
    $url_demo = asset('Tabler/dist/js/demo.min.js');
@endphp

<!-- Tabler -->
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src={{ $url_tabler }}></script>
<script src={{ $url_demo }}></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>