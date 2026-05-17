<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'طباعة')</title>

    {{-- خطوط عربية --}}
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800&family=Cairo:wght@400;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap للأزرار (لن يُطبع) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Tajawal', 'Cairo', Arial, sans-serif;
            background: #fff;
            margin: 0;
            direction: rtl;
        }
    </style>
</head>
<body>

@yield('content')

</body>
</html>
