<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#006632">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|cormorant-garamond:600,700&display=swap" rel="stylesheet">
{{-- CSS hasil build Tailwind (lokal, tidak bergantung CDN). Build ulang: npm install && npm run build:css --}}
<link rel="stylesheet" href="{{ asset('css/sentra.css') }}?v={{ @filemtime(public_path('css/sentra.css')) }}">
