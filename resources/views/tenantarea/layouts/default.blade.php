<!doctype html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', config('app.name'))</title>

    <!-- Meta Data -->
    @include('cortex/foundation::common.partials.meta')

    <!-- Styles -->
    <link href="{{ mix('css/vendor.css') }}" rel="stylesheet">
    <link href="{{ mix('css/theme.css') }}" rel="stylesheet">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    @stack('styles')

    <!-- Scripts -->
    <script>
        window.Laravel = <?php echo json_encode(['csrfToken' => csrf_token()]); ?>
    </script>
</head>
<body class="hold-transition skin-green fixed sidebar-mini">
    <!-- Main Content -->
    <div class="wrapper">
        @include('cortex/foundation::tenantarea.partials.header')
        @include('cortex/foundation::tenantarea.partials.sidebar')

        @yield('content')

        @include('cortex/foundation::tenantarea.partials.footer')
    </div>

    <!-- JavaScripts -->
    <script src="{{ mix('js/manifest.js') }}" type="text/javascript"></script>
    <script src="{{ mix('js/vendor.js') }}" type="text/javascript"></script>
    @stack('scripts-vendor')
    <script src="{{ mix('js/app.js') }}" type="text/javascript"></script>
    @stack('scripts')

    <!-- Alerts -->
    @alerts('default')
</body>
</html>
