<?php

namespace App\Http\Controllers;

use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        // Логируем для отладки
        Log::info('OperationsController: Request', [
            'user_id' => $user->id,
            'ajax' => $request->ajax(),
            'wantsJson' => $request->wantsJson(),
            'page' => $page,
            'search' => $search,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'operations_total' => $operations->total(),
            'operations_count' => $operations->count(),
        ]);

        // Всегда возвращаем JSON - Vue компонент всегда делает AJAX запрос при монтировании
        // Если это прямой переход (без AJAX), все равно возвращаем JSON, 
        // так как Vue компонент сделает свой AJAX запрос
        $formatted = $this->balanceService->formatPaginatedOperationsForApi($operations);
        
        Log::info('OperationsController: Returning JSON', [
            'operations_count' => count($formatted['operations']),
            'pagination' => $formatted['pagination'],
        ]);
        
        // Если это не AJAX запрос, возвращаем HTML страницу
        // Но Vue компонент все равно сделает AJAX запрос при монтировании
        if (!$request->ajax() && !$request->wantsJson() && !$request->hasAny(['page', 'search', 'sort_by', 'sort_order'])) {
            Log::info('OperationsController: Returning HTML view (first load)');
            return view('balance.operations');
        }
        
        return response()->json($formatted);
    }
}
