<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'CampusEvents Hub')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Main navigation">
            <a href="{{ route('events.index') }}" class="text-lg font-bold tracking-tight text-red-900">CampusEvents Hub</a>
            <div class="flex items-center gap-3 text-sm font-medium">
                <a href="{{ route('events.index') }}" class="text-slate-700 hover:text-red-800">Events</a>
                @auth
                    @if(auth()->user()->isOfficer() && auth()->user()->organizations()->exists())
                        <a href="{{ route('officer.events.index') }}" class="text-slate-700 hover:text-red-800">Manage events</a>
                    @endif
                    <span class="hidden text-slate-500 sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-md border border-slate-300 px-3 py-2 hover:bg-slate-50" type="submit">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-md bg-red-900 px-3 py-2 text-white hover:bg-red-800">Log in</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('status'))
            <div role="status" class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                <ul class="list-inside list-disc">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>