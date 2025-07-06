<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'GenRev Admin')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css') {{-- or use asset() if not using Vite --}}
</head>
<body class="bg-gray-900 text-white min-h-screen">

    <div class="p-4">
        @yield('content')
    </div>

</body>
</html>
