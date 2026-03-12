<?php

namespace App\Console\Commands;

use App\Services\TelegramAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nFailureMonitorCommand extends Command
{
    protected $signature = 'n8n:check-failures';

    protected $description = 'Check n8n executions for failures and send Telegram alert';

    private const ALERTED_CACHE_PREFIX = 'n8n_failure_alerted:';
    private const ALERTED_TTL_SECONDS = 86400; // 24h

    public function __construct(
        protected TelegramAlertService $telegram
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $baseUrl = rtrim(config('services.n8n.base_url', 'http://localhost:5678'), '/');
        $apiKey = config('services.n8n.api_key');

        try {
            $request = Http::timeout(15);
            if ($apiKey) {
                $request = $request->withHeaders(['X-N8N-API-KEY' => $apiKey]);
            }
            $response = $request->get($baseUrl . '/api/v1/executions', [
                'status' => 'error',
                'limit' => 50,
            ])->json();
        } catch (\Throwable $e) {
            Log::warning('N8nFailureMonitor: could not fetch n8n executions', ['message' => $e->getMessage()]);
            return self::SUCCESS;
        }

        $executions = $this->extractExecutions($response);
        if (empty($executions)) {
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($executions as $exec) {
            $id = $exec['id'] ?? null;
            if (! $id) {
                continue;
            }
            $cacheKey = self::ALERTED_CACHE_PREFIX . $id;
            if (Cache::has($cacheKey)) {
                continue;
            }

            $workflowName = $exec['workflow_name'] ?? 'Unknown workflow';
            $errorMessage = $exec['error_message'] ?? 'No message';
            $startedAt = $exec['started_at'] ?? 'Unknown time';

            $text = "<b>n8n workflow failed</b>\n"
                . "Workflow: " . $this->escapeHtml($workflowName) . "\n"
                . "Error: " . $this->escapeHtml($errorMessage) . "\n"
                . "Time: " . $this->escapeHtml($startedAt);

            if ($this->telegram->sendMessage($text)) {
                Cache::put($cacheKey, true, self::ALERTED_TTL_SECONDS);
                $sent++;
            }
        }

        if ($sent > 0) {
            $this->info("Sent {$sent} failure alert(s) to Telegram.");
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{id: string, workflow_name: string, error_message: string, started_at: string}>
     */
    private function extractExecutions(mixed $response): array
    {
        if (! is_array($response)) {
            return [];
        }
        $data = $response['data'] ?? $response;
        $results = $data['results'] ?? $data;
        if (! is_array($results)) {
            return [];
        }

        $out = [];
        foreach ($results as $item) {
            if (! is_array($item)) {
                continue;
            }
            $id = $item['id'] ?? null;
            if ($id === null) {
                continue;
            }
            $workflowData = $item['workflowData'] ?? [];
            $workflowName = is_array($workflowData) ? ($workflowData['name'] ?? null) : null;
            if ($workflowName === null) {
                $workflowName = $item['workflowId'] ?? 'Unknown';
            }
            $message = $item['message'] ?? $item['stoppedAt'] ?? '';
            if ($message === '' && isset($item['data']['resultData']['error'])) {
                $err = $item['data']['resultData']['error'];
                $message = is_string($err) ? $err : ($err['message'] ?? json_encode($err));
            }
            $startedAt = $item['startedAt'] ?? $item['createdAt'] ?? '';

            $out[] = [
                'id' => (string) $id,
                'workflow_name' => (string) $workflowName,
                'error_message' => (string) $message,
                'started_at' => (string) $startedAt,
            ];
        }

        return $out;
    }

    private function escapeHtml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
