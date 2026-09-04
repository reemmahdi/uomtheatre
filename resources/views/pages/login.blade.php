<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول — {{ config('theatre.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
    @livewireStyles
</head>
<body>
    <livewire:auth.login />
    @livewireScripts
</body>
</html>
