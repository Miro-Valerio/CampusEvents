<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', Rule::in(Event::CATEGORIES)],
            'date' => ['nullable', 'date'],
            'organization' => ['nullable', 'integer'],
            'sort' => ['nullable', Rule::in(['soonest', 'newest'])],
        ]);

        Gate::authorize('viewAny', Event::class);

        $events = Event::query()
            ->with('organizer')
            ->withActiveRsvpCount()
            ->whereHas('organizer', fn (Builder $query) => $query->where('user_id', $request->user()->id))
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

        return view('officer.events.index', [
            'events' => $events,
            'organizations' => $request->user()->organizations()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Event::class);

        return view('officer.events.form', [
            'event' => new Event,
            'organizations' => auth()->user()->organizations()->orderBy('name')->get(),
            'categories' => Event::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Event::class);
        $validated = $request->validate($this->rules());
        $this->assertOrganizationBelongsToOfficer($request, (int) $validated['organizer_id']);

        $event = Event::create($this->eventAttributes($validated, $request));

        return redirect()->route('officer.events.index')->with('status', "{$event->title} was published.");
    }

    public function show(Event $event): never
    {
        abort(404);
    }

    public function edit(Event $event): View
    {
        Gate::authorize('update', $event);

        return view('officer.events.form', [
            'event' => $event,
            'organizations' => auth()->user()->organizations()->orderBy('name')->get(),
            'categories' => Event::CATEGORIES,
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);
        $validated = $request->validate($this->rules());
        $this->assertOrganizationBelongsToOfficer($request, (int) $validated['organizer_id']);

        $oldPoster = $event->poster_url;
        $event->update($this->eventAttributes($validated, $request, $event));

        if ($event->poster_url !== $oldPoster) {
            $this->deletePosterWhenUnused($oldPoster);
        }

        return redirect()->route('officer.events.index')->with('status', "{$event->title} was updated.");
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);
        $event->update(['status' => Event::STATUS_CANCELLED]);

        return redirect()->route('officer.events.index')->with('status', "{$event->title} was cancelled.");
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:10000'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'venue' => ['required', 'string', 'max:190'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'category' => ['required', 'string', Rule::in(Event::CATEGORIES)],
            'organizer_id' => ['required', 'integer', 'exists:organizers,id'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /** @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function eventAttributes(array $validated, Request $request, ?Event $event = null): array
    {
        $attributes = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'venue' => $validated['venue'],
            'starts_at' => "{$validated['event_date']} {$validated['start_time']}",
            'ends_at' => "{$validated['event_date']} {$validated['end_time']}",
            'category' => $validated['category'],
            'organizer_id' => $validated['organizer_id'],
            'capacity' => $validated['capacity'],
        ];

        if ($request->hasFile('poster')) {
            $path = $request->file('poster')->store('event-posters', 'public');
            $attributes['poster_url'] = Storage::disk('public')->url($path);
        } elseif (! $event) {
            $attributes['poster_url'] = null;
        }

        if (! $event) {
            $attributes['status'] = Event::STATUS_PUBLISHED;
        }

        return $attributes;
    }

    private function assertOrganizationBelongsToOfficer(Request $request, int $organizationId): void
    {
        abort_unless($request->user()->organizations()->whereKey($organizationId)->exists(), 403);
    }

    private function deletePosterWhenUnused(?string $posterUrl): void
    {
        if (! $posterUrl) {
            return;
        }

        $path = parse_url($posterUrl, PHP_URL_PATH);
        if (! is_string($path) || ! str_starts_with($path, '/storage/event-posters/')) {
            return;
        }

        $storagePath = substr($path, strlen('/storage/'));
        if (! Event::query()->where('poster_url', $posterUrl)->exists()) {
            Storage::disk('public')->delete($storagePath);
        }
    }
}
