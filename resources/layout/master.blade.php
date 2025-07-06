<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
    @include('layout.sidebar') <!-- optional -->

    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
