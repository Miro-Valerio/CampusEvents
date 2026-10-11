@extends('layouts.campus')

@section('title', 'Log in | CampusEvents Hub')

@section('content')
    <div class="mx-auto max-w-md rounded-lg border border-slate-200 bg-white p-6 sm:p-8">
        <p class="text-sm font-semibold uppercase tracking-wide text-red-800">CampusEvents Hub</p>
        <h1 class="mt-2 text-2xl font-bold">Log in</h1>
        <p class="mt-2 text-sm text-slate-600">Use the account you already created for CampusEvents Hub.</p>
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Email address</span>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="w-full rounded-md border border-slate-300 px-3 py-2">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Password</span>
                <input type="password" name="password" required autocomplete="current-password" class="w-full rounded-md border border-slate-300 px-3 py-2">
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                Remember me
            </label>
            <button class="w-full rounded-md bg-red-900 px-4 py-3 font-semibold text-white hover:bg-red-800">Log in</button>
        </form>
    </div>
@endsection