<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Rsvp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'date' => ['nullable', 'date'],
            'category' => ['nullable', 'string', 'max:80'],
            'organizer_id' => ['nullable', 'integer', 'exists:organizers,id'],
            'sort' => ['nullable', 'in:soonest,newest'],
        ]);

        $query = Event::query()->publishedUpcoming()->with('organizer')->withActiveRsvpCount();
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%")
                    ->orWhereHas('organizer', fn ($o) => $o->where('name', 'like', "%{$search}%"));
            });
        }
        if (! empty($filters['date'])) {
            $query->whereDate('starts_at', $filters['date']);
        }
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (! empty($filters['organizer_id'])) {
            $query->where('organizer_id', $filters['organizer_id']);
        }

        match ($filters['sort'] ?? 'soonest') {
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderBy('starts_at'),
        };

        return response()->json($query->paginate(12)->withQueryString());
    }

    public function show(Event $event): JsonResponse
    {
        abort_unless($event->status === Event::STATUS_PUBLISHED && $event->starts_at >= now()->startOfDay(), 404);

        return response()->json($event
            ->load('organizer')
            ->loadCount(['rsvps as active_rsvps_count' => fn (Builder $query) => $query->where('status', Rsvp::STATUS_GOING)]));
    }

    public function toggleSave(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();
        $saved = $user->savedEvents()->where('events.id', $event->id)->exists();
        if ($saved) {
            $user->savedEvents()->detach($event->id);

            return response()->json(['saved' => false, 'message' => 'Event removed from saved events.']);
        }
        $user->savedEvents()->syncWithoutDetaching([$event->id]);

        return response()->json(['saved' => true, 'message' => 'Event saved successfully.']);
    }

    public function storeRsvp(Request $request, Event $event): JsonResponse
    {
        $rsvpResult = DB::transaction(function () use ($event, $request): ?array {
            $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->id);
            $rsvp = Rsvp::query()
                ->where('event_id', $lockedEvent->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($rsvp?->status === Rsvp::STATUS_GOING) {
                return [
                    'created' => false,
                    'seat_number' => $rsvp->seat_number,
                    'registered_count' => $lockedEvent->baseline_registered_count
                        + $lockedEvent->rsvps()->where('status', Rsvp::STATUS_GOING)->count(),
                ];
            }

            $activeRsvps = $lockedEvent->rsvps()
                ->where('status', Rsvp::STATUS_GOING)
                ->get(['seat_number']);
            $registeredCount = $lockedEvent->baseline_registered_count + $activeRsvps->count();

            if ($registeredCount >= $lockedEvent->capacity) {
                return null;
            }

            $occupiedSeats = $activeRsvps->pluck('seat_number')->all();
            $seatNumber = $lockedEvent->baseline_registered_count + 1;
            while ($seatNumber <= $lockedEvent->capacity && in_array($seatNumber, $occupiedSeats, true)) {
                $seatNumber++;
            }

            if ($seatNumber > $lockedEvent->capacity) {
                return null;
            }

            if ($rsvp) {
                $rsvp->update([
                    'status' => Rsvp::STATUS_GOING,
                    'seat_number' => $seatNumber,
                ]);

                return [
                    'created' => true,
                    'seat_number' => $seatNumber,
                    'registered_count' => $registeredCount + 1,
                ];
            }

            $lockedEvent->rsvps()->create([
                'user_id' => $request->user()->id,
                'status' => Rsvp::STATUS_GOING,
                'seat_number' => $seatNumber,
            ]);

            return [
                'created' => true,
                'seat_number' => $seatNumber,
                'registered_count' => $registeredCount + 1,
            ];
        });

        if ($rsvpResult === null) {
            return response()->json(['message' => 'This event is full.'], 409);
        }

        return response()->json([
            'rsvped' => true,
            'seat_number' => $rsvpResult['seat_number'],
            'registered_count' => $rsvpResult['registered_count'],
            'message' => $rsvpResult['created'] ? 'You are registered for this event.' : 'You are already registered for this event.',
        ], $rsvpResult['created'] ? 201 : 200);
    }

    public function cancelRsvp(Request $request, Event $event): JsonResponse
    {
        $event->rsvps()
            ->where('user_id', $request->user()->id)
            ->where('status', Rsvp::STATUS_GOING)
            ->update([
                'status' => Rsvp::STATUS_CANCELLED,
                'seat_number' => null,
            ]);

        $registeredCount = $event->baseline_registered_count + $event->rsvps()
            ->where('status', Rsvp::STATUS_GOING)
            ->count();

        return response()->json([
            'rsvped' => false,
            'registered_count' => $registeredCount,
            'message' => 'Your RSVP has been cancelled.',
        ]);
    }

    public function myRsvps(Request $request): JsonResponse
    {
        $events = Event::query()
            ->with('organizer')
            ->withCount(['rsvps as active_rsvps_count' => fn (Builder $query) => $query->where('status', Rsvp::STATUS_GOING)])
            ->whereHas('rsvps', fn (Builder $query) => $query
                ->where('user_id', $request->user()->id)
                ->where('status', Rsvp::STATUS_GOING))
            ->with(['rsvps' => fn (HasMany $query) => $query
                ->select('id', 'event_id', 'seat_number')
                ->where('user_id', $request->user()->id)
                ->where('status', Rsvp::STATUS_GOING)])
            ->orderBy('starts_at')
            ->get();

        $events->each(function (Event $event): void {
            $event->setAttribute('seat_number', $event->rsvps->first()?->seat_number);
            $event->unsetRelation('rsvps');
        });

        return response()->json(['data' => $events]);
    }
}
