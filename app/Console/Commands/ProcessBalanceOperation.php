<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBalanceOperationJob;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class ProcessBalanceOperation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance:operation 
                            {--login= : Логин пользователя}
                            {--type= : Тип операции (deposit/withdrawal)}
                            {--amount= : Сумма операции}
                            {--description= : Описание операции}
                            {--queue : Использовать очередь для обработки}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Провести операцию по балансу пользователя (начисление/списание)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $login = $this->option('login') ?: $this->ask('Введите логин пользователя');
        $type = $this->option('type') ?: $this->choice('Выберите тип операции', ['deposit', 'withdrawal'], 0);
        $amount = $this->option('amount') ?: $this->ask('Введите сумму операции');
        $description = $this->option('description') ?: $this->ask('Введите описание операции');
        $useQueue = $this->option('queue');

        $validator = Validator::make([
            'login' => $login,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
        ], [
            'login' => 'required|string|exists:users,login',
            'type' => 'required|in:deposit,withdrawal',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            $this->error('Ошибки валидации:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  - ' . $error);
            }
            return 1;
        }

        $user = User::where('login', $login)->first();

        if (!$user) {
            $this->error("Пользователь с логином '{$login}' не найден.");
            return 1;
        }

        try {
            if ($useQueue) {
                // Использовать очередь
                ProcessBalanceOperationJob::dispatch($user->id, $type, $amount, $description);
                $this->info("Операция добавлена в очередь для обработки.");
            } else {
                // Обработать сразу
                $this->processOperation($user, $type, $amount, $description);
                $this->info("Операция успешно выполнена!");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Ошибка при выполнении операции: ' . $e->getMessage());
            return 1;
        }
    }

    private function processOperation($user, $type, $amount, $description)
    {
        $balance = $user->balance;

        if (!$balance) {
            $balance = \App\Models\Balance::create([
                'user_id' => $user->id,
                'amount' => 0,
            ]);
        }

        // Проверка на отрицательный баланс при списании
        if ($type === 'withdrawal' && $balance->amount < $amount) {
            throw new \Exception('Недостаточно средств на балансе. Текущий баланс: ' . $balance->amount);
        }

        // Обновление баланса
        if ($type === 'deposit') {
            $balance->amount += $amount;
        } else {
            $balance->amount -= $amount;
        }
        $balance->save();

        // Создание записи об операции
        \App\Models\Operation::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
        ]);

        $this->info("Новый баланс: {$balance->amount}");
    }
}
