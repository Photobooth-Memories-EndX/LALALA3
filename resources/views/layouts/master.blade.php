<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head-meta')
    @include('partials.head-style')
    @yield('style')
	@stack('styles')
</head>

<body>
    <script src="{{ asset('Tabler/dist/js/demo-theme.min.js') }}"></script>
    <div class="page">
        @include('partials.header')
        <!-- Content Wrapper. Contains page content -->
        <div class="page-wrapper">
            @yield('content')
            @include('partials.footer')
        </div>
        <!-- Content Wrapper. -->
    </div>
    <!-- ./wrapper -->
    @include('partials.foot-script')
    @yield('script')
    @stack('scripts')
</body>

</html>
