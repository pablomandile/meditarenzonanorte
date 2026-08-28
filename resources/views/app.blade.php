<!DOCTYPE html>
@php($fuente = \App\Support\Typography::chosen())
{{--
    La fuente de los títulos de sección, elegida desde Ajustes. Los títulos grandes
    —las bandas, el hero, las fichas— siguen en Anton, que es el rasgo más
    reconocible del sitio.

    La variable va en el propio <html> a propósito: un estilo en línea le gana a
    cualquier hoja de estilos, así que no importa si el CSS de Tailwind se carga
    antes o después (con el servidor de Vite se inyecta por JavaScript, al final de
    todo). Ver App\Support\Typography.
--}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @if ($fuente) style="--font-heading: {{ $fuente['stack'] }}" @endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{--
            Estas etiquetas se arman en el servidor a propósito: WhatsApp y las redes
            no ejecutan JavaScript, así que el <Head> de Inertia no les llega. Ver
            App\Support\SiteMeta.
        --}}
        @php($meta = \App\Support\SiteMeta::from($page ?? []))

        <title inertia>{{ $meta['title'] }}</title>

        @if ($meta['description'])
            <meta name="description" content="{{ $meta['description'] }}">
        @endif

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $meta['site'] }}">
        <meta property="og:title" content="{{ $meta['title'] }}">
        <meta property="og:url" content="{{ $meta['url'] }}">
        <meta property="og:locale" content="es_AR">
        @if ($meta['description'])
            <meta property="og:description" content="{{ $meta['description'] }}">
        @endif
        @if ($meta['image'])
            <meta property="og:image" content="{{ $meta['image'] }}">
            <meta name="twitter:card" content="summary_large_image">
        @else
            <meta name="twitter:card" content="summary">
        @endif

        {{-- rescue(): antes de migrar o sembrar, la tabla de ajustes todavía no existe. --}}
        @php($favicon = rescue(fn () => \App\Models\Setting::favicon(), null, false))
        @if ($favicon)
            @if ($favicon['type'])
                <link rel="icon" type="{{ $favicon['type'] }}" href="/storage/{{ $favicon['path'] }}">
            @else
                <link rel="icon" href="/storage/{{ $favicon['path'] }}">
            @endif
            <link rel="apple-touch-icon" href="/storage/{{ $favicon['path'] }}">
        @endif

        {{-- Si no hay fuente elegida no se emite nada y no se descarga nada. --}}
        @if ($fuente)
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link rel="stylesheet" href="{{ $fuente['url'] }}">
        @endif

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
