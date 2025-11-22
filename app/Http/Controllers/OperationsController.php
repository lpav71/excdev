<?php

namespace App\Http\Controllers;

use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OperationsController extends Controller
{
    public function __construct(
        private BalanceService $balanceService
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Получаем параметры запроса
        $page = (int) $request->get('page', 1);
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $operations = $this->balanceService->getFilteredOperations(
            $user,
            $search,
            $sortBy,
            $sortOrder,
            $page
        );

        $formatted = $this->balanceService->formatPaginatedOperationsForApi($operations);
        
        return Inertia::render('Operations', [
            'operations' => $formatted['operations'],
            'pagination' => $formatted['pagination'],
            'filters' => [
                'search' => $search ?? '',
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
            ]
        ]);
    }
}
