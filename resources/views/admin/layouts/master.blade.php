<!DOCTYPE html>
<html lang="en">

{{-- @php
    $setting = getSetting();
@endphp --}}

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Loyality CashBack</title>
    <link rel="icon" href="{{ asset('assets/images/favicon-32x32.png') }}"
        type="image/x-icon">
    @include('admin.layouts.style')
    @stack('style')
</head>

<body>
    @include('admin.layouts.sidebar')
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        @include('admin.layouts.header')

        <!-- Dynamic content -->
        @yield('content')
    </div>

    @include('admin.layouts.script')

    @stack('script')
</body>

</html>
