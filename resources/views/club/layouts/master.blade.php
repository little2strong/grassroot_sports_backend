<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Club Panel</title>
    @include('club.layouts.style')
    @stack('style')
</head>
<body class="club-panel-body">
    @include('club.layouts.sidebar')

    <div class="club-main main-content">
        @include('club.layouts.header')
        @yield('content')
    </div>

    @include('club.layouts.script')
    @stack('script')
</body>
</html>
