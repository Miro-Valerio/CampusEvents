<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOfficer();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool|Response
    {
        return $this->ownsEvent($user, $event) ? true : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isOfficer() && $user->organizations()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool|Response
    {
        return $this->ownsEvent($user, $event) ? true : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool|Response
    {
        return $this->update($user, $event);
    }

    public function restore(User $user, Event $event): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return false;
    }

    private function ownsEvent(User $user, Event $event): bool
    {
        return $user->isOfficer() && $event->organizer()->where('user_id', $user->id)->exists();
    }
}
