@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    @endpush
@endonce
