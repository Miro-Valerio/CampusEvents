@extends('layouts.campus')

@section('title', $event->title.' | CampusEvents Hub')

@section('content')
    <a href="{{ route('events.index') }}" class="text-sm font-semibold text-red-900 underline underline-offset-4">Back to events</a>
    <article class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white">
        @if($event->poster_url)
            <img src="{{ $event->poster_url }}" alt="Poster for {{ $event->title }}" class="max-h-[34rem] w-full bg-slate-100 object-cover">
        @endif
        <div class="grid gap-8 p-5 sm:p-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-red-800">{{ $event->category }} · {{ $event->organizer->name }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ $event->title }}</h1>
                <div class="mt-6 grid gap-4 border-y border-slate-200 py-5 sm:grid-cols-2">
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Date and time</p><p class="mt-1 font-medium">{{ $event->starts_at->format('l, F j, Y') }}</p><p class="text-slate-600">{{ $event->starts_at->format('g:i A') }}–{{ $event->ends_at?->format('g:i A') }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Venue</p><p class="mt-1 font-medium">{{ $event->venue }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Organization</p><p class="mt-1 font-medium">{{ $event->organizer->name }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Organizer</p><p class="mt-1 font-medium">{{ $event->organizer->user?->name ?? $event->organizer->name }}</p></div>
                </div>
                <h2 class="mt-7 text-lg font-bold">About this event</h2>
                <div class="mt-2 whitespace-pre-line leading-7 text-slate-700">{{ $event->description }}</div>
            </div>
            <aside class="h-fit rounded-md bg-slate-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Availability</p>
                <p class="mt-2 text-3xl font-bold">{{ $event->available_seats }}</p>
                <p class="text-sm text-slate-600">seats available · {{ $event->capacity }} total</p>
                <p class="mt-5 border-t border-slate-200 pt-4 text-sm"><span class="font-semibold">Status</span><span class="float-right">{{ ucfirst($event->status) }}</span></p>
            </aside>
        </div>
    </article>
@endsection