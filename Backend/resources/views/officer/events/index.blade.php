@extends('layouts.campus')

@section('title', 'Manage events | CampusEvents Hub')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-red-800">Officer workspace</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Manage events</h1>
            <p class="mt-2 text-slate-600">Create and maintain events for your organization.</p>
        </div>
        <a href="{{ route('officer.events.create') }}" class="rounded-md bg-red-900 px-4 py-2.5 font-semibold text-white hover:bg-red-800">Create event</a>
    </div>

    @if($events->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
            <h2 class="text-xl font-semibold">No events yet</h2>
            <p class="mt-2 text-slate-600">Create your first event for your organization.</p>
            <a href="{{ route('officer.events.create') }}" class="mt-5 inline-flex rounded-md bg-red-900 px-4 py-2 font-semibold text-white hover:bg-red-800">Create event</a>
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead class="bg-slate-100 text-xs uppercase tracking-wide text-slate-600">
                    <tr><th class="px-4 py-3">Event</th><th class="px-4 py-3">Organization</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Seats</th><th class="px-4 py-3">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($events as $event)
                        <tr>
                            <td class="px-4 py-4"><a href="{{ route('events.show', $event) }}" class="font-semibold text-red-900 hover:underline">{{ $event->title }}</a><p class="mt-1 text-slate-500">{{ $event->venue }}</p></td>
                            <td class="px-4 py-4">{{ $event->organizer->name }}</td>
                            <td class="px-4 py-4">{{ $event->starts_at->format('M j, Y · g:i A') }}</td>
                            <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $event->status === 'published' ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-200 text-slate-700' }}">{{ ucfirst($event->status) }}</span></td>
                            <td class="px-4 py-4">{{ $event->available_seats }} / {{ $event->capacity }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('officer.events.edit', $event) }}" class="font-semibold text-red-900 hover:underline">Edit</a>
                                    @if($event->status === 'published')
                                        <form method="POST" action="{{ route('officer.events.destroy', $event) }}" onsubmit="return confirm('Cancel this event? It will remain in your event history.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="font-semibold text-red-700 hover:underline">Cancel</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $events->links() }}</div>
    @endif
@endsection