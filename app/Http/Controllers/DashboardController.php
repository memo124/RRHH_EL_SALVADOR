<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboard
    ) {}

    public function stats(Request $request)
    {
        $empresaId = $request->filled('ID_EMPRESA')
            ? (int) $request->input('ID_EMPRESA')
            : null;

        return response()->json($this->dashboard->getStats($empresaId));
    }
}
