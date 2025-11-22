<?php

namespace App\Http\Controllers;

use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private BalanceService $balanceService
    ) {
    }

    public function index()
    {
        $user = Auth::user();
        
        return inertia('Dashboard', [
            'balance' => $this->balanceService->getUserBalance($user),
            'operations' => $this->balanceService->formatOperationsForApi(
                $this->balanceService->getRecentOperations($user)
            ),
        ]);
    }

    public function getData()
    {
        $user = Auth::user();
        
        return response()->json([
            'balance' => $this->balanceService->getUserBalance($user),
            'operations' => $this->balanceService->formatOperationsForApi(
                $this->balanceService->getRecentOperations($user)
            ),
        ]);
    }
}
