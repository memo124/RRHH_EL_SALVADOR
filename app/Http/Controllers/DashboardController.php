<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboard
    ) {}

    public function stats()
    {
        return response()->json($this->dashboard->getStats());
    }
}
