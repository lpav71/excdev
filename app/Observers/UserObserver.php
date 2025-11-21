<?php

namespace App\Observers;

use App\Models\Balance;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        // Автоматически устанавливаем login из email, если login не указан
        if (empty($user->login)) {
            $user->login = $user->email;
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Автоматически создаем баланс для нового пользователя
        Balance::firstOrCreate(
            ['user_id' => $user->id],
            ['amount' => 0]
        );
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
