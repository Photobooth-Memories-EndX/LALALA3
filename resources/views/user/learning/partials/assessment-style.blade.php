@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
    .rounded-4 {
        border-radius: 1rem !important;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    #table-list-assessment,
    #MyTable {
        width: 100% !important;
    }

    #table-list-assessment th,
    #table-list-assessment td,
    #MyTable th,
    #MyTable td {
        vertical-align: middle;
        white-space: nowrap;
    }

    #table-list-assessment td:nth-child(2),
    #MyTable td:nth-child(3) {
        white-space: normal;
    }

    .dataTables_wrapper {
        padding: 1rem;
    }

    .dataTables_length,
    .dataTables_filter {
        margin-bottom: .75rem;
    }

    .dataTables_filter input {
        border-radius: .75rem;
        border: 1px solid #dbe4f0;
        padding: .4rem .75rem;
    }

    .dataTables_length select {
        border-radius: .75rem;
        border: 1px solid #dbe4f0;
        padding: .35rem 1.8rem .35rem .75rem;
    }
</style>
@endsection