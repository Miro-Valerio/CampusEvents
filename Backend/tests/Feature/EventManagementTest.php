<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_listing_shows_only_published_upcoming_events(): void
    {
        $organizer = $this->createOrganizer();
        $published = $this->createEvent($organizer, ['title' => 'Published Campus Day']);
        $this->createEvent($organizer, ['title' => 'Cancelled Campus Day', 'status' => Event::STATUS_CANCELLED]);
        $this->createEvent($organizer, ['title' => 'Past Campus Day', 'starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(3)->addHours(2)]);

        $response = $this->get(route('events.index'));

        $response->assertOk()->assertSee($published->title)->assertDontSee('Cancelled Campus Day')->assertDontSee('Past Campus Day');
    }

    public function test_public_search_matches_title_venue_and_organization(): void
    {
        $organizer = $this->createOrganizer(null, 'Robotics Society');
        $this->createEvent($organizer, ['title' => 'Build Night', 'venue' => 'Innovation Lab']);
        $this->createEvent($this->createOrganizer(null, 'Arts Guild'), ['title' => 'Gallery Opening', 'venue' => 'Main Hall']);

        $this->get(route('events.index', ['search' => 'Build Night']))
            ->assertOk()->assertSee('Build Night')->assertDontSee('Gallery Opening');
        $this->get(route('events.index', ['search' => 'Innovation Lab']))
            ->assertOk()->assertSee('Build Night')->assertDontSee('Gallery Opening');
        $this->get(route('events.index', ['search' => 'Robotics Society']))
            ->assertOk()->assertSee('Build Night')->assertDontSee('Gallery Opening');
    }

    public function test_public_filters_and_sorting_use_validated_values(): void
    {
        $organizer = $this->createOrganizer(null, 'Coding Club');
        $eventDate = now()->addDays(8)->toDateString();
        $match = $this->createEvent($organizer, [
            'title' => 'Workshop Match',
            'category' => 'Workshop',
            'starts_at' => "{$eventDate} 10:00:00",
            'ends_at' => "{$eventDate} 12:00:00",
        ]);
        $newest = $this->createEvent($organizer, [
            'title' => 'Newest Workshop Match',
            'category' => 'Workshop',
            'starts_at' => "{$eventDate} 11:00:00",
            'ends_at' => "{$eventDate} 12:30:00",
        ]);
        $match->forceFill(['created_at' => now()->subDay()])->save();
        $this->createEvent($organizer, ['title' => 'Campus Category', 'category' => 'Campus']);

        $response = $this->get(route('events.index', [
            'category' => 'Workshop',
            'date' => $eventDate,
            'organization' => $organizer->id,
            'sort' => 'newest',
        ]));

        $response->assertOk()->assertSee($match->title)->assertDontSee('Campus Category');

        $this->getJson('/api/events?sort=newest&category=Workshop')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newest->id);

        $this->get('/api/events?sort=created_at')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort');
    }

    public function test_public_listing_paginates_results(): void
    {
        $organizer = $this->createOrganizer();
        for ($index = 1; $index <= 13; $index++) {
            $this->createEvent($organizer, ['title' => "Paged Event {$index}", 'starts_at' => now()->addDays($index)]);
        }

        $response = $this->get(route('events.index'));

        $response->assertOk()->assertSee('Paged Event 1')->assertDontSee('Paged Event 13')->assertSee('page=2');
    }

    public function test_event_details_render_without_a_poster(): void
    {
        $event = $this->createEvent($this->createOrganizer(), ['title' => 'Posterless Details']);

        $response = $this->get(route('events.show', $event));

        $response->assertOk()->assertSee('Posterless Details')->assertSee($event->venue)->assertSee('Published');
    }

    public function test_available_seats_subtract_active_rsvps_and_baseline_count(): void
    {
        $event = $this->createEvent($this->createOrganizer(), ['capacity' => 10]);
        Event::query()->whereKey($event)->update(['baseline_registered_count' => 2]);
        $event->rsvps()->create(['user_id' => User::factory()->create()->id, 'status' => 'going']);
        $event->rsvps()->create(['user_id' => User::factory()->create()->id, 'status' => 'cancelled']);

        $response = $this->get(route('events.index'));

        $response->assertOk()->assertSee('7 of 10 seats available');
    }

    public function test_officer_can_create_event_with_a_securely_stored_poster(): void
    {
        Storage::fake('public');
        $officer = $this->createOfficer();
        $organizer = $this->createOrganizer($officer);

        $response = $this->actingAs($officer)->post(route('officer.events.store'), $this->validPayload($organizer) + [
            'poster' => UploadedFile::fake()->createWithContent('campus-poster.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/nXcAAAAASUVORK5CYII=')),
        ]);

        $response->assertRedirect(route('officer.events.index'));
        $event = Event::query()->where('title', 'Officer Created Event')->firstOrFail();
        $this->assertSame(Event::STATUS_PUBLISHED, $event->status);
        $this->assertStringContainsString('event-posters/', $event->poster_url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', parse_url($event->poster_url, PHP_URL_PATH)));
    }

    public function test_officer_can_edit_and_cancel_owned_event_without_deleting_history(): void
    {
        $officer = $this->createOfficer();
        $organizer = $this->createOrganizer($officer);
        $event = $this->createEvent($organizer);
        $updatedPayload = $this->validPayload($organizer);
        $updatedPayload['title'] = 'Updated Officer Event';

        $this->actingAs($officer)
            ->get(route('officer.events.edit', $event))
            ->assertOk()
            ->assertSee('Edit event');

        $this->actingAs($officer)
            ->put(route('officer.events.update', $event), $updatedPayload)
            ->assertRedirect(route('officer.events.index'));
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Updated Officer Event']);

        $this->delete(route('officer.events.destroy', $event))
            ->assertRedirect(route('officer.events.index'));
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => Event::STATUS_CANCELLED]);
    }

    public function test_students_cannot_create_edit_or_cancel_events(): void
    {
        $student = User::factory()->create();
        $organizer = $this->createOrganizer($student);
        $event = $this->createEvent($organizer);

        $this->actingAs($student)
            ->post(route('officer.events.store'), $this->validPayload($organizer))
            ->assertForbidden();
        $this->put(route('officer.events.update', $event), $this->validPayload($organizer))->assertNotFound();
        $this->delete(route('officer.events.destroy', $event))->assertNotFound();
    }

    public function test_officer_cannot_manage_another_organizations_event(): void
    {
        $officer = $this->createOfficer();
        $this->createOrganizer($officer, 'Officer Organization');
        $event = $this->createEvent($this->createOrganizer(null, 'Separate Organization'));

        $this->actingAs($officer)
            ->put(route('officer.events.update', $event), $this->validPayload($event->organizer))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_from_officer_routes(): void
    {
        $this->get(route('officer.events.index'))->assertRedirect(route('login'));
    }

    public function test_officer_without_an_organization_sees_an_empty_management_list(): void
    {
        $this->actingAs($this->createOfficer())
            ->get(route('officer.events.index'))
            ->assertOk()
            ->assertSee('No events yet');
    }

    public function test_event_validation_rejects_empty_data_and_invalid_poster(): void
    {
        Storage::fake('public');
        $officer = $this->createOfficer();
        $organizer = $this->createOrganizer($officer);

        $this->actingAs($officer)
            ->post(route('officer.events.store'), [])
            ->assertSessionHasErrors(['title', 'description', 'venue', 'event_date', 'start_time', 'end_time', 'category', 'organizer_id', 'capacity']);

        $this->post(route('officer.events.store'), $this->validPayload($organizer) + [
            'poster' => UploadedFile::fake()->create('poster.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('poster');
        $this->assertDatabaseMissing('events', ['title' => 'Officer Created Event']);
    }

    private function createOrganizer(?User $owner = null, string $name = 'Campus Events Organization'): Organizer
    {
        return Organizer::create(['name' => $name, 'user_id' => $owner?->id]);
    }

    private function createOfficer(): User
    {
        $officer = User::factory()->create();
        $officer->role = 'officer';
        $officer->save();

        return $officer;
    }

    /** @param array<string, mixed> $attributes */
    private function createEvent(Organizer $organizer, array $attributes = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Campus Event',
            'description' => 'An event description for students.',
            'venue' => 'Main Hall',
            'starts_at' => now()->addDays(5)->setTime(9, 0),
            'ends_at' => now()->addDays(5)->setTime(11, 0),
            'category' => 'Campus',
            'capacity' => 25,
            'organizer_id' => $organizer->id,
            'status' => Event::STATUS_PUBLISHED,
        ], $attributes));
    }

    /** @return array<string, mixed> */
    private function validPayload(Organizer $organizer): array
    {
        return [
            'title' => 'Officer Created Event',
            'description' => 'A new event with a useful description.',
            'venue' => 'Student Center',
            'event_date' => now()->addDays(9)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:30',
            'category' => 'Workshop',
            'organizer_id' => $organizer->id,
            'capacity' => 40,
        ];
    }
}
