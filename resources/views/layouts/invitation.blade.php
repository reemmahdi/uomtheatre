<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'دعوة حضور — مسرح جامعة الموصل' }}</title>

    
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">

    
    <meta property="og:title" content="دعوة حضور - مسرح جامعة الموصل">
    <meta property="og:description" content="دعوة كريمة لحضور فعالية على مسرح جامعة الموصل">
    <meta property="og:type" content="website">

    @livewireStyles
</head>
<body>
    {{ $slot }}

    @livewireScripts
</body>
</html>
