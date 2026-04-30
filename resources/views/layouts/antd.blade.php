<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- favicon --}}
    <link rel="icon" href="{{ asset('images/simaris_favicon.webp') }}" type="image/x-icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <title>{{ config('app.name', 'UBT SIMARIS') }} | {{ $title ?? '' }}</title>

    <script>
        window._USER = @json($authUser);
        window.ASSET_URL = @json(asset('/'));
        window.APP_NAME = @json(config('app.name', 'UBT SIMARIS'));
        window._HOST = @json(url('/'));
    </script>

    @vite(['resources/js/app.js'])
</head>

<body>
    <main id="vue-app">
        <app-entry :menu="{{ json_encode(menu_list(auth()->user()), JSON_HEX_APOS) }}">
            {!! $vue ?? '' !!}
            @yield('content')
        </app-entry>
    </main>
</body>

</html>
