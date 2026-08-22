<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - Công dân</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('emblem-vietnam.svg') }}">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/citizen/main.jsx'])
</head>
<body>
    <div id="citizen-app"></div>
</body>
</html>
