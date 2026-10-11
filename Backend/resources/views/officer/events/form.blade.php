@extends('layouts.campus')

@section('title', ($event->exists ? 'Edit event' : 'Create event').' | CampusEvents Hub')

@section('content')
    <a href="{{ route('officer.events.index') }}" class="text-sm font-semibold text-red-900 underline underline-offset-4">Back to your events</a>
    <div class="mx-auto mt-5 max-w-3xl rounded-lg border border-slate-200 bg-white p-5 sm:p-8">
        <p class="text-sm font-semibold uppercase tracking-wide text-red-800">Officer workspace</p>
        <h1 class="mt-2 text-2xl font-bold">{{ $event->exists ? 'Edit event' : 'Create an event' }}</h1>

        @if($organizations->isEmpty())
            <p class="mt-5 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Your account is not linked to an organization yet. Ask an administrator to connect your officer account before creating events.</p>
        @else
            <form method="POST" action="{{ $event->exists ? route('officer.events.update', $event) : route('officer.events.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-5 sm:grid-cols-2">
                @csrf
                @if($event->exists) @method('PUT') @endif
                <label class="sm:col-span-2">
                    <span class="mb-1 block text-sm font-medium">Title</span>
                    <input name="title" required maxlength="160" value="{{ old('title', $event->title) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </label>
                <label class="sm:col-span-2">
                    <span class="mb-1 block text-sm font-medium">Description</span>
                    <textarea name="description" rows="5" required maxlength="10000" class="w-full rounded-md border border-slate-300 px-3 py-2">{{ old('description', $event->description) }}</textarea>
                </label>
                <label class="sm:col-span-2">
                    <span class="mb-1 block text-sm font-medium">Poster image <span class="font-normal text-slate-500">(JPG, PNG, or WebP; up to 5 MB)</span></span>
                    <input type="file" name="poster" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @if($event->poster_url)<img src="{{ $event->poster_url }}" alt="Current poster for {{ $event->title }}" class="mt-3 max-h-48 rounded-md object-cover">@endif
                </label>
                <label>
                    <span class="mb-1 block text-sm font-medium">Venue</span>
                    <input name="venue" required maxlength="190" value="{{ old('venue', $event->venue) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </label>
                <label>
                    <span class="mb-1 block text-sm font-medium">Organization</span>
                    <select name="organizer_id" required class="w-full rounded-md border border-slate-300 px-3 py-2">
                        @foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected((string) old('organizer_id', $event->organizer_id) === (string) $organization->id)>{{ $organization->name }}</option>@endforeach
                    </select>
                </label>
                <label>
                    <span class="mb-1 block text-sm font-medium">Event date</span>
                    <input type="date" name="event_date" required min="{{ now()->toDateString() }}" value="{{ old('event_date', $event->starts_at?->format('Y-m-d')) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </label>
                <label>
                    <span class="mb-1 block text-sm font-medium">Category</span>
                    <select name="category" required class="w-full rounded-md border border-slate-300 px-3 py-2">
                        @foreach($categories as $category)<option value="{{ $category }}" @selected(old('category', $event->category) === $category)>{{ $category }}</option>@endforeach
                    </select>
                </label>
                <label>
                    <span class="mb-1 block text-sm font-medium">Start time</span>
                    <input type="time" name="start_time" required value="{{ old('start_time', $event->starts_at?->format('H:i')) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </label>
                <label>
                    <span class="mb-1 block text-sm font-medium">End time</span>
                    <input type="time" name="end_time" required value="{{ old('end_time', $event->ends_at?->format('H:i')) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </label>
                <label>
                    <span class="mb-1 block text-sm font-medium">Seat limit</span>
                    <input type="number" name="capacity" min="1" max="100000" required value="{{ old('capacity', $event->capacity ?: 100) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </label>
                <div class="flex flex-wrap gap-3 sm:col-span-2">
                    <button class="rounded-md bg-red-900 px-5 py-2.5 font-semibold text-white hover:bg-red-800">{{ $event->exists ? 'Save changes' : 'Publish event' }}</button>
                    <a href="{{ route('officer.events.index') }}" class="rounded-md border border-slate-300 px-5 py-2.5 font-semibold hover:bg-slate-50">Back</a>
                </div>
            </form>
        @endif
    </div>
@endsection