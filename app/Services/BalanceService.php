<?php

namespace App\Services;

use App\Models\Operation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BalanceService
{
    /**
     * Получить последние операции пользователя
     */
    public function getRecentOperations(User $user, int $limit = 5): Collection
    {
        return $user->operations()
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Получить отформатированные операции для API
     */
    public function formatOperationsForApi(Collection $operations): array
    {
        return $operations->map(function ($operation) {
            return [
                'id' => $operation->id,
                'type' => $operation->type,
                'amount' => (float) $operation->amount,
                'description' => $operation->description,
                'created_at' => $operation->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * Получить баланс пользователя
     */
    public function getUserBalance(User $user): float
    {
        $balance = $user->balance;
        return $balance ? (float) $balance->amount : 0.0;
    }

    /**
     * Получить операции с фильтрацией и сортировкой
     */
    public function getFilteredOperations(
        User $user,
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $page = 1,
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = $user->operations();

        // Поиск по описанию
        if ($search) {
            $query->where('description', 'like', '%' . $search . '%');
        }

        // Валидация и применение сортировки
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';
        
        if (in_array($sortBy, ['created_at', 'amount', 'type', 'description'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Форматировать операции с пагинацией для API
     */
    public function formatPaginatedOperationsForApi(LengthAwarePaginator $operations): array
    {
        return [
            'operations' => collect($operations->items())->map(function ($operation) {
                return [
                    'id' => $operation->id,
                    'type' => $operation->type,
                    'amount' => (float) $operation->amount,
                    'description' => $operation->description,
                    'created_at' => $operation->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray(),
            'pagination' => [
                'current_page' => $operations->currentPage(),
                'last_page' => $operations->lastPage(),
                'per_page' => $operations->perPage(),
                'total' => $operations->total(),
            ],
        ];
    }
}

