<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

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

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
