<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CampusEventsApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_events_are_returned_as_paginated_json(): void
    {
        $this->seed();

        $response = $this->getJson('/api/events');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.title', 'SPC Foundation Week Opening')
            ->assertJsonPath('total', 3);
    }

    public function test_registration_returns_a_token_and_persists_the_user(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Campus Student',
            'email' => 'campus.student@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'campus.student@example.com')
            ->assertJsonMissingPath('user.password')
            ->assertJsonPath('token', fn (string $token): bool => $token !== '');

        $this->assertDatabaseHas('users', [
            'email' => 'campus.student@example.com',
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $response->json('user.id'),
            'name' => 'campus-events-web',
        ]);
    }

    public function test_saving_an_event_returns_401_without_a_token(): void
    {
        $this->seed();
        $event = Event::query()->firstOrFail();

        $response = $this->postJson("/api/events/{$event->id}/save");

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_save_an_event_with_a_bearer_token(): void
    {
        $this->seed();
        $event = Event::query()->firstOrFail();
        $user = User::factory()->create();
        $token = $user->createToken('feature-test')->plainTextToken;

        $response = $this->withToken($token)->postJson("/api/events/{$event->id}/save");

        $response
            ->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('message', 'Event saved successfully.');
        $this->assertDatabaseHas('event_user_saves', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

    }

    public function test_event_feed_counts_active_rsvps_instead_of_demo_counters(): void
    {
        $this->seed();

        $response = $this->getJson('/api/events');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.title', 'SPC Foundation Week Opening')
            ->assertJsonPath('data.0.registered_count', 169);
    }

    public function test_authenticated_user_can_rsvp_once_and_cancel(): void
    {
        $this->seed();
        $event = Event::query()->firstOrFail();
        $user = User::factory()->create();
        $token = $user->createToken('feature-test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/events/{$event->id}/rsvp")
            ->assertCreated()
            ->assertJsonPath('rsvped', true)
            ->assertJsonPath('registered_count', 170)
            ->assertJsonPath('seat_number', 170);

        $this->withToken($token)
            ->postJson("/api/events/{$event->id}/rsvp")
            ->assertOk()
            ->assertJsonPath('registered_count', 170)
            ->assertJsonPath('seat_number', 170);

        $this->withToken($token)
            ->deleteJson("/api/events/{$event->id}/rsvp")
            ->assertOk()
            ->assertJsonPath('rsvped', false)
            ->assertJsonPath('registered_count', 169);

        $this->assertDatabaseHas('rsvps', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        $this->withToken($token)
            ->getJson('/api/my-rsvps')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_rsvp_requires_authentication(): void
    {
        $this->seed();
        $event = Event::query()->firstOrFail();

        $this->postJson("/api/events/{$event->id}/rsvp")
            ->assertUnauthorized();
    }

    public function test_rsvp_seat_is_reused_after_cancellation(): void
    {
        $this->seed();
        $event = Event::query()->firstOrFail();
        $user = User::factory()->create();
        $token = $user->createToken('feature-test')->plainTextToken;

        $this->withToken($token)->postJson("/api/events/{$event->id}/rsvp");
        $this->withToken($token)->deleteJson("/api/events/{$event->id}/rsvp");

        $this->withToken($token)
            ->postJson("/api/events/{$event->id}/rsvp")
            ->assertCreated()
            ->assertJsonPath('seat_number', 170);
    }

    public function test_my_rsvps_returns_event_details_and_assigned_seat_number(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'student@spc.edu.ph')->firstOrFail();
        $token = $user->createToken('feature-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/my-rsvps')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'SPC Foundation Week Opening')
            ->assertJsonPath('data.0.organizer.name', 'SPC Student Affairs')
            ->assertJsonPath('data.0.seat_number', 169)
            ->assertJsonPath('data.0.registered_count', 169);
    }

    public function test_full_event_rejects_additional_rsvps(): void
    {
        $this->seed();
        $event = Event::query()->firstOrFail();
        $event->update(['capacity' => 1, 'baseline_registered_count' => 0]);
        $event->rsvps()->delete();
        $event->rsvps()->create([
            'user_id' => User::factory()->create()->id,
            'status' => 'going',
            'seat_number' => 1,
        ]);
        $user = User::factory()->create();
        $token = $user->createToken('feature-test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/events/{$event->id}/rsvp")
            ->assertConflict()
            ->assertJsonPath('message', 'This event is full.');

        $this->assertDatabaseMissing('rsvps', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }
}
