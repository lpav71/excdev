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
            ->select(['id', 'type', 'amount', 'description', 'created_at'])
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Форматировать операцию для API
     */
    private function formatOperationForApi($operation): array
    {
        return [
            'id' => $operation->id,
            'type' => $operation->type,
            'amount' => (float) $operation->amount,
            'description' => $operation->description,
            'created_at' => $operation->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Получить отформатированные операции для API
     */
    public function formatOperationsForApi(Collection $operations): array
    {
        return $operations->map(fn($operation) => $this->formatOperationForApi($operation))->toArray();
    }

    /**
     * Получить баланс пользователя с кэшированием
     */
    public function getUserBalance(User $user): float
    {
        return cache()->remember(
            "user_balance_{$user->id}",
            now()->addMinutes(5),
            fn() => (float) $user->operations()->sum('amount')
        );
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
        $query = $user->operations()->select(['id', 'type', 'amount', 'description', 'created_at']);

        // Поиск по описанию
        if ($search) {
            $query->where('description', 'like', '%' . addcslashes($search, '%_') . '%');
        }

        // Валидация и применение сортировки
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';
        $allowedSortFields = ['created_at', 'amount', 'type', 'description'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'created_at';

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Форматировать операции с пагинацией для API
     */
    public function formatPaginatedOperationsForApi(LengthAwarePaginator $operations): array
    {
        return [
            'operations' => collect($operations->items())->map(fn($operation) => $this->formatOperationForApi($operation))->toArray(),
            'pagination' => [
                'current_page' => $operations->currentPage(),
                'last_page' => $operations->lastPage(),
                'per_page' => $operations->perPage(),
                'total' => $operations->total(),
            ],
        ];
    }
}

