<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class N8nWebhookController extends Controller
{
    private function n8nBaseUrl(): string
    {
        return config('services.n8n.base_url', 'http://localhost:5678');
    }

    public function trigger(Request $request, string $workflow)
    {
        try {
            $response = Http::post($this->n8nBaseUrl() . '/webhook/' . $workflow);
            return response()->json([
                'message' => 'Workflow triggered successfully',
                'status' => 'success',
                'workflow' => $workflow,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to trigger workflow',
                'status' => 'error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function status()
    {
        $mission = app(MissionControlController::class);
        $data = $mission->getMissionControlData();
        $data['modules'] = $data['system_status']['modules'] ?? [];
        return response()->json($data);
    }
}
