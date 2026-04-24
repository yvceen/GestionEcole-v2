<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>MyEdu</title>

    <x-school-favicons />

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    {{-- Scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell-body font-sans antialiased text-slate-900 min-h-screen overflow-x-hidden m-0 p-0">
    <x-app-shell>
        <div class="ui-scope space-y-6">
            @isset($header)
                <section class="app-card px-6 py-5">
                    {{ $header }}
                </section>
            @endisset

            @if(session('success'))
                <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
            @endif

            <section class="app-card p-4 sm:p-6">
                {{ $slot }}
            </section>
        </div>
    </x-app-shell>
</body>
</html>
