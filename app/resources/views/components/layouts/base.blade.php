@props(['titulo' => null, 'vite' => null])
<!doctype html>
<html lang="pt-BR" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f766e">
    <meta name="description" content="Diário Escolar Municipal: diário de classe, frequência e notas que funciona sem internet.">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Diário DEM">
    <title>{{ isset($titulo) ? $titulo.' · ' : '' }}Diário Escolar Municipal</title>
    <link rel="manifest" href="/build/manifest.webmanifest">
    <link rel="icon" href="/favicon.ico" sizes="48x48">
    <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon-180x180.png">
    @vite($vite ?? ['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    {{ $slot }}
</body>
</html>
