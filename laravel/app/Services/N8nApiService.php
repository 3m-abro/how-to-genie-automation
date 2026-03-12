<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class N8nApiService
{
    private function baseUrl(): string
    {
        return rtrim(config('services.n8n.base_url', 'http://localhost:5678'), '/');
    }

    private function client()
    {
        $client = Http::timeout(5)->withHeaders([
            'Accept' => 'application/json',
        ]);
        $apiKey = config('services.n8n.api_key');
        if ($apiKey) {
            $client = $client->withHeaders(['X-N8N-API-KEY' => $apiKey]);
        }
        return $client;
    }

    /**
     * GET /api/v1/workflows — list all workflows.
     *
     * @return array<int, array{id: string, name: string, active: bool, updatedAt: string, ...}>
     */
    public function getWorkflows(): array
    {
        try {
            $response = $this->client()->get($this->baseUrl() . '/api/v1/workflows');
            if (! $response->successful()) {
                return [];
            }
            return $response->json('data', []);
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    /**
     * GET /api/v1/executions — list executions, optionally filtered by workflow and status.
     *
     * @param  string|null  $workflowId
     * @param  string|null  $status  e.g. 'success', 'error', 'running', 'waiting'
     * @param  int  $limit
     * @return array<int, array{id: string, workflowId: string, status?: string, startedAt: string, stoppedAt?: string, finished?: bool, ...}>
     */
    public function getExecutions(?string $workflowId = null, ?string $status = null, int $limit = 50): array
    {
        $params = ['limit' => $limit];
        if ($workflowId !== null) {
            $params['workflowId'] = $workflowId;
        }
        if ($status !== null) {
            $params['status'] = $status;
        }
        try {
            $response = $this->client()->get($this->baseUrl() . '/api/v1/executions', $params);
            if (! $response->successful()) {
                return [];
            }
            return $response->json('data', []);
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }
}
