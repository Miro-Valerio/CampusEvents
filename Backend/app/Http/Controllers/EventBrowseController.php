<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventBrowseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = Validator::make($request->query(), [
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', Rule::in(Event::CATEGORIES)],
            'date' => ['nullable', 'date'],
            'organization' => ['nullable', 'integer', 'exists:organizers,id'],
            'sort' => ['nullable', Rule::in(['soonest', 'newest'])],
        ])->validate();

        $events = Event::query()
            ->publishedUpcoming()
            ->with('organizer')
            ->withActiveRsvpCount()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('venue', 'like', "%{$search}%")
                        ->orWhereHas('organizer', fn (Builder $organizerQuery) => $organizerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('starts_at', $date))
            ->when($filters['organization'] ?? null, fn (Builder $query, int $organization) => $query->where('organizer_id', $organization))
            ->when(($filters['sort'] ?? 'soonest') === 'newest', fn (Builder $query) => $query->orderByDesc('created_at'), fn (Builder $query) => $query->orderBy('starts_at'))
            ->paginate(12)
            ->withQueryString();

        return view('events.index', [
            'events' => $events,
            'organizations' => Organizer::query()
                ->whereHas('events', fn (Builder $query) => $query->publishedUpcoming())
                ->orderBy('name')
                ->get(),
            'categories' => Event::CATEGORIES,
            'filters' => $filters,
        ]);
    }

    public function show(int $event): View
    {
        $event = Event::query()
            ->publishedUpcoming()
            ->with('organizer')
            ->withActiveRsvpCount()
            ->findOrFail($event);

        return view('events.show', ['event' => $event]);
    }
}
