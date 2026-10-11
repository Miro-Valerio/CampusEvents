@extends('layouts.campus')

@section('title', 'Upcoming events | CampusEvents Hub')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-red-800">Campus calendar</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Upcoming events</h1>
            <p class="mt-2 max-w-2xl text-slate-600">Find workshops, campus programs, and student activities.</p>
        </div>
        <p class="text-sm text-slate-500">{{ $events->total() }} {{ Str::plural('event', $events->total()) }}</p>
    </div>

    <form method="GET" action="{{ route('events.index') }}" class="mb-8 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6">
        <label class="sm:col-span-2">
            <span class="mb-1 block text-sm font-medium">Search</span>
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Title, venue, or organization" class="w-full rounded-md border border-slate-300 px-3 py-2">
        </label>
        <label>
            <span class="mb-1 block text-sm font-medium">Category</span>
            <select name="category" class="w-full rounded-md border border-slate-300 px-3 py-2">
                <option value="">All categories</option>
                @foreach($categories as $category)<option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>@endforeach
            </select>
        </label>
        <label>
            <span class="mb-1 block text-sm font-medium">Date</span>
            <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
        </label>
        <label>
            <span class="mb-1 block text-sm font-medium">Organization</span>
            <select name="organization" class="w-full rounded-md border border-slate-300 px-3 py-2">
                <option value="">All organizations</option>
                @foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected((string) ($filters['organization'] ?? '') === (string) $organization->id)>{{ $organization->name }}</option>@endforeach
            </select>
        </label>
        <div class="flex items-end gap-2 lg:col-span-6">
            <label class="w-full max-w-56">
                <span class="mb-1 block text-sm font-medium">Sort by</span>
                <select name="sort" class="w-full rounded-md border border-slate-300 px-3 py-2">
                    <option value="soonest" @selected(($filters['sort'] ?? 'soonest') === 'soonest')>Soonest</option>
                    <option value="newest" @selected(($filters['sort'] ?? '') === 'newest')>Newest</option>
                </select>
            </label>
            <button class="rounded-md bg-red-900 px-4 py-2 font-semibold text-white hover:bg-red-800">Apply filters</button>
            <a href="{{ route('events.index') }}" class="rounded-md border border-slate-300 px-4 py-2 font-semibold hover:bg-slate-50">Clear</a>
        </div>
    </form>

    @if($events->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
            <h2 class="text-xl font-semibold">No upcoming events found</h2>
            <p class="mt-2 text-slate-600">Try changing your search or clearing the filters.</p>
            <a href="{{ route('events.index') }}" class="mt-5 inline-flex rounded-md bg-red-900 px-4 py-2 font-semibold text-white hover:bg-red-800">Clear filters</a>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($events as $event)
                <article class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                    @if($event->poster_url)
                        <img src="{{ $event->poster_url }}" alt="Poster for {{ $event->title }}" class="aspect-[16/9] w-full object-cover" loading="lazy">
                    @else
                        <div class="flex aspect-[16/9] items-end bg-red-950 p-5 text-white">
                            <span class="text-sm font-semibold uppercase tracking-wide text-red-200">{{ $event->category }}</span>
                        </div>
                    @endif
                    <div class="p-5">
                        <p class="text-sm font-semibold text-red-800">{{ $event->organizer->name }}</p>
                        <h2 class="mt-1 text-xl font-bold leading-snug">{{ $event->title }}</h2>
                        <p class="mt-3 text-sm text-slate-600">{{ $event->starts_at->format('D, M j, Y') }} · {{ $event->starts_at->format('g:i A') }}–{{ $event->ends_at?->format('g:i A') }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $event->venue }}</p>
                        <p class="mt-3 text-sm text-slate-700">{{ $event->available_seats }} of {{ $event->capacity }} seats available</p>
                        <a href="{{ route('events.show', $event) }}" class="mt-4 inline-flex font-semibold text-red-900 underline decoration-red-300 underline-offset-4">Event details</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $events->links() }}</div>
    @endif
@endsection