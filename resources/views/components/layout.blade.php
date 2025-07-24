<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $title }}</title>
        @vite(["resources/css/app.css", "resources/js/app.js"])
        @if($assets)
            @vite(["resources/css/pages/{$assets}.css", "resources/js/pages/{$assets}.js"])
        @endif
    </head>

    <body class="min-h-screen bg-gray-50">
        <div class="py-12">
            {{ $slot }}
        </div>
    </body>
</html>