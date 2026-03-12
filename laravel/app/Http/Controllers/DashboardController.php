<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Revenue dashboard aggregates (stub until Task 2; then from Google Sheets).
     */
    public function revenue(): JsonResponse
    {
        return response()->json([
            'content_stats' => [],
            'revenue_data' => [],
            'traffic_data' => [],
            'agent_activity' => [],
            'top_posts' => [],
        ]);
    }
}
