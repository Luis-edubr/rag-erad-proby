<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen flex flex-col items-center justify-center p-6 lg:p-8 bg-gray-50 text-gray-900 antialiased">
        <header class="w-full max-w-4xl mb-8 text-sm">
            @if (Route::has('login'))
                <nav class="flex justify-end gap-3">
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-1.5 rounded-md border border-gray-300 bg-white text-gray-800 hover:border-primary hover:text-primary transition"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 rounded-md border border-transparent text-gray-800 hover:text-primary transition"
                        >
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 rounded-md border border-gray-300 bg-white text-gray-800 hover:border-primary transition"
                            >
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>

        <main class="w-full max-w-4xl rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="grid lg:grid-cols-2 gap-0">
                <div class="p-8 lg:p-12 border-b lg:border-b-0 lg:border-r border-gray-200">
                    <h1 class="text-xl font-semibold text-gray-900 mb-2">{{ config('app.name', 'Laravel') }}</h1>
                    <p class="text-sm text-gray-600 mb-6">
                        Paleta Proby:
                        <span class="inline-flex items-center gap-1.5 mx-1"><span class="h-3 w-3 rounded-sm bg-primary ring-1 ring-gray-200" title="Primary"></span><span class="font-mono text-xs text-gray-800">#00EBA8</span></span>
                        ·
                        <span class="inline-flex items-center gap-1.5 mx-1"><span class="h-3 w-3 rounded-sm bg-secondary ring-1 ring-gray-300" title="Secondary"></span><span class="font-mono text-xs text-gray-800">#CEFF06</span></span>
                    </p>
                    <ul class="space-y-3 text-sm text-gray-700">
                        <li class="flex gap-3">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-primary" aria-hidden="true"></span>
                            <span>
                                Leia a
                                <a href="https://laravel.com/docs" target="_blank" class="font-medium text-primary underline underline-offset-2">documentação</a>.
                            </span>
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-secondary" aria-hidden="true"></span>
                            <span>
                                Tutoriais em
                                <a href="https://laracasts.com" target="_blank" class="font-medium text-primary underline underline-offset-2">Laracasts</a>.
                            </span>
                        </li>
                    </ul>
                    <p class="mt-8 text-xs text-gray-500">
                        Laravel v{{ app()->version() }}
                    </p>
                </div>
                <div class="p-8 lg:p-12 bg-gradient-to-br from-proby-dark to-primary flex flex-col items-center justify-center min-h-[200px]">
                    <svg class="w-40 h-auto text-secondary drop-shadow-sm" viewBox="0 0 62 65" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M61.8548 14.6253C61.8778 14.7107 61.8895 14.7978 61.8897 14.8858V28.5615C61.8898 28.737 61.8434 28.9095 61.7554 29.0614C61.6675 29.2132 61.5409 29.3392 61.3887 29.4265L49.9104 36.0351V49.1337C49.9104 49.4902 49.7209 49.8192 49.4118 49.9987L25.4519 63.7916C25.3971 63.8227 25.3372 63.8427 25.2774 63.8639C25.255 63.8714 25.2338 63.8851 25.2101 63.8913C25.0426 63.9357 24.8666 63.9357 24.6991 63.8913C24.6716 63.8838 24.6467 63.8689 24.6205 63.8589C24.5657 63.8389 24.5084 63.8215 24.456 63.7916L0.501061 49.9987C0.348882 49.9113 0.222437 49.7853 0.134469 49.6334C0.0465019 49.4816 0.000120578 49.3092 0 49.1337L0 8.10652C0 8.01678 0.0124642 7.92953 0.0348998 7.84477C0.0423783 7.8161 0.0598282 7.78993 0.0697995 7.76126C0.0884958 7.70891 0.105946 7.65531 0.133367 7.6067C0.152063 7.5743 0.179485 7.54812 0.20192 7.51821C0.318655 7.37359 0.474691 7.25859 0.656666 7.19033L0.663626 7.18729L12.9773 1.34739V1.34276L12.975 1.33943C13.0925 1.28432 13.2193 1.25086 13.3496 1.23646C13.4749 1.22206 13.6021 1.22769 13.7254 1.25202C13.7488 1.25665 13.7701 1.26821 13.7935 1.27484C13.9526 1.31791 14.0993 1.40054 14.222 1.51599L24.5639 10.863L35.974 1.51599C36.0967 1.40054 36.2434 1.31791 36.4025 1.27484C36.4259 1.26821 36.4472 1.25665 36.4706 1.25202C36.5939 1.22769 36.7211 1.22206 36.8464 1.23646C36.9767 1.25086 37.1035 1.28432 37.221 1.33943L37.222 1.34276L37.2243 1.34739L49.4882 7.19662C49.6702 7.26488 49.8262 7.37989 49.9429 7.5245C49.9654 7.55441 49.9928 7.58059 50.0115 7.61296C50.0389 7.66157 50.0564 7.71517 50.0751 7.76752C50.085 7.79619 50.1025 7.82236 50.11 7.85103C50.1325 7.93579 50.145 8.02305 50.145 8.11279V36.5668L61.3898 29.7615C61.5419 29.6742 61.6684 29.5482 61.7563 29.3964C61.8443 29.2446 61.8907 29.0721 61.8907 28.8967V14.8858C61.8902 14.7979 61.8783 14.7104 61.8548 14.6253Z" fill="currentColor"/>
                    </svg>
                </div>
            </div>
        </main>
    </body>
</html>
