<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    public const CATEGORIES = ['Campus', 'Workshop', 'Student Life', 'Sports', 'Arts & Culture', 'Other'];

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELLED = 'cancelled';

    protected $appends = ['registered_count', 'available_seats'];

    protected $fillable = [
        'title', 'description', 'poster_url', 'venue', 'starts_at', 'ends_at',
        'category', 'rules', 'capacity', 'organizer_id', 'status',
    ];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    protected function registeredCount(): Attribute
    {
        return Attribute::get(fn (mixed $value, array $attributes): int => (int) ($attributes['baseline_registered_count'] ?? 0)
            + (int) ($attributes['active_rsvps_count'] ?? 0));
    }

    protected function availableSeats(): Attribute
    {
        return Attribute::get(fn (mixed $value, array $attributes): int => max(
            0,
            (int) ($attributes['capacity'] ?? 0)
                - (int) ($attributes['baseline_registered_count'] ?? 0)
                - (int) ($attributes['active_rsvps_count'] ?? 0),
        ));
    }

    public function scopePublishedUpcoming(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where('starts_at', '>=', now()->startOfDay());
    }

    public function scopeWithActiveRsvpCount(Builder $query): Builder
    {
        return $query->withCount([
            'rsvps as active_rsvps_count' => fn (Builder $rsvpQuery) => $rsvpQuery->where('status', Rsvp::STATUS_GOING),
        ]);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user_saves')->withTimestamps();
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }
}
