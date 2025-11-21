<?php

namespace App\Jobs;

use App\Models\Balance;
use App\Models\Operation;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBalanceOperationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public string $type,
        public float $amount,
        public string $description
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $user = User::findOrFail($this->userId);
                $balance = $user->balance;

                if (!$balance) {
                    $balance = Balance::create([
                        'user_id' => $user->id,
                        'amount' => 0,
                    ]);
                }

                // Проверка на отрицательный баланс при списании
                if ($this->type === 'withdrawal' && $balance->amount < $this->amount) {
                    throw new \Exception(
                        "Недостаточно средств на балансе. Текущий баланс: {$balance->amount}, запрошено: {$this->amount}"
                    );
                }

                // Обновление баланса
                if ($this->type === 'deposit') {
                    $balance->amount += $this->amount;
                } else {
                    $balance->amount -= $this->amount;
                }
                $balance->save();

                // Создание записи об операции
                Operation::create([
                    'user_id' => $user->id,
                    'type' => $this->type,
                    'amount' => $this->amount,
                    'description' => $this->description,
                ]);

                Log::info("Balance operation processed", [
                    'user_id' => $user->id,
                    'type' => $this->type,
                    'amount' => $this->amount,
                    'new_balance' => $balance->amount,
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Failed to process balance operation", [
                'user_id' => $this->userId,
                'type' => $this->type,
                'amount' => $this->amount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
