<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $student = User::updateOrCreate(
            ['email' => 'student@spc.edu.ph'],
            ['name' => 'SPC Student', 'password' => Hash::make('password')],
        );

        $studentAffairs = Organizer::updateOrCreate(
            ['name' => 'SPC Student Affairs'],
            ['description' => 'Official student affairs announcements and campus activities.'],
        );
        $itOrganization = Organizer::updateOrCreate(
            ['name' => 'IT Student Organization'],
            ['description' => 'Technology talks, workshops, and student-led events.'],
        );

        $events = [
            [
                'title' => 'SPC Foundation Week Opening',
                'description' => 'Join the opening program and celebrate SPC campus life.',
                'venue' => 'SPC Main Quadrangle',
                'starts_at' => now()->addDays(4)->setTime(9, 0),
                'ends_at' => now()->addDays(4)->setTime(12, 0),
                'category' => 'Campus',
                'rules' => 'Bring your student ID. Follow event staff instructions.',
                'capacity' => 250,
                'baseline_registered_count' => 168,
                'organizer_id' => $studentAffairs->id,
            ],
            [
                'title' => 'Web Development Workshop',
                'description' => 'A beginner-friendly workshop on modern web development.',
                'venue' => 'Computer Laboratory 2',
                'starts_at' => now()->addDays(7)->setTime(13, 0),
                'ends_at' => now()->addDays(7)->setTime(16, 0),
                'category' => 'Workshop',
                'rules' => 'Bring a laptop if available. Register with your student email.',
                'capacity' => 50,
                'baseline_registered_count' => 42,
                'organizer_id' => $itOrganization->id,
            ],
            [
                'title' => 'Student Organization Fair',
                'description' => 'Meet student organizations and discover ways to get involved.',
                'venue' => 'Student Center',
                'starts_at' => now()->addDays(10)->setTime(10, 0),
                'ends_at' => now()->addDays(10)->setTime(15, 0),
                'category' => 'Student Life',
                'rules' => 'Keep walkways clear and respect all booths.',
                'capacity' => 150,
                'baseline_registered_count' => 67,
                'organizer_id' => $studentAffairs->id,
            ],
        ];

        foreach ($events as $eventData) {
            $event = Event::updateOrCreate(['title' => $eventData['title']], $eventData);

            if ($eventData['title'] === 'SPC Foundation Week Opening') {
                $student->rsvps()->updateOrCreate(
                    ['event_id' => $event->id],
                    [
                        'status' => 'going',
                        'seat_number' => $event->baseline_registered_count + 1,
                    ],
                );
            }
        }
    }
}
