<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Аутентификация пользователя по логину
     */
    public function authenticateByLogin(string $login, string $password, bool $remember = false): bool
    {
        $user = User::where('login', $login)->first();
        
        if (!$user) {
            return false;
        }

        return Auth::attempt(['email' => $user->email, 'password' => $password], $remember);
    }

    /**
     * Валидация и аутентификация пользователя
     *
     * @throws ValidationException
     */
    public function login(array $credentials, bool $remember = false): void
    {
        if (!$this->authenticateByLogin($credentials['login'], $credentials['password'], $remember)) {
            throw ValidationException::withMessages([
                'login' => ['Неверный логин или пароль.'],
            ]);
        }
    }
}

