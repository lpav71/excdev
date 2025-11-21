<?php

namespace App\Console\Commands;

use App\Models\Balance;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AddUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:add 
                            {--name= : Имя пользователя}
                            {--email= : Email пользователя}
                            {--login= : Логин пользователя}
                            {--password= : Пароль пользователя}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Добавить нового пользователя';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name') ?: $this->ask('Введите имя пользователя');
        $email = $this->option('email') ?: $this->ask('Введите email');
        $login = $this->option('login') ?: $this->ask('Введите логин');
        $password = $this->option('password') ?: $this->secret('Введите пароль');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'login' => $login,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'login' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Ошибки валидации:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  - ' . $error);
            }
            return 1;
        }

        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'login' => $login,
                'password' => Hash::make($password),
            ]);

            // Создаем баланс для пользователя (если еще не создан observer'ом)
            Balance::firstOrCreate(
                ['user_id' => $user->id],
                ['amount' => 0]
            );

            $this->info("Пользователь успешно создан!");
            $this->info("ID: {$user->id}");
            $this->info("Логин: {$user->login}");
            $this->info("Email: {$user->email}");

            return 0;
        } catch (\Exception $e) {
            $this->error('Ошибка при создании пользователя: ' . $e->getMessage());
            return 1;
        }
    }
}
