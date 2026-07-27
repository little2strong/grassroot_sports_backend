<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    @include('club.layouts.style')
    @stack('style')
</head>
<body class="bg-light">
    <div class="container py-4">
        @yield('content')
    </div>
    @include('club.layouts.script')
    @stack('script')
</body>
</html>