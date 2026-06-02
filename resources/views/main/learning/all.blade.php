@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">
        @include('main.learning.components.learning-header')
        @include('main.learning.components.learning-filter')
        @include('main.learning.components.learning-table')
    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
    #learning-table {
        font-size: 13px;
    }

    #learning-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    #learning-table tbody td {
        vertical-align: middle;
        padding-top: 14px;
        padding-bottom: 14px;
    }
</style>
@endsection

@section('script')
    @include('main.learning.components.learning-table-script')
@endsection