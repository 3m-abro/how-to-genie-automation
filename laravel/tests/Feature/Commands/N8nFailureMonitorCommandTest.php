<?php

namespace Tests\Feature\Commands;

use App\Services\TelegramAlertService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class N8nFailureMonitorCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.n8n.base_url' => 'http://n8n.test']);
        config(['services.n8n.api_key' => 'test-api-key']);
        config(['services.telegram.bot_token' => 'test-token']);
        config(['services.telegram.chat_id' => '123']);
    }

    public function test_command_runs_successfully_when_n8n_returns_no_errors(): void
    {
        Http::fake([
            'http://n8n.test/api/v1/executions*' => Http::response([
                'data' => ['results' => []],
            ], 200),
        ]);

        $this->artisan('n8n:check-failures')
            ->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_command_sends_telegram_alert_for_each_failed_execution(): void
    {
        Http::fake([
            'http://n8n.test/api/v1/executions*' => Http::response([
                'data' => [
                    'results' => [
                        [
                            'id' => 'exec-1',
                            'workflowId' => 'wf-1',
                            'workflowData' => ['name' => 'Master Orchestrator'],
                            'message' => 'Node "Code" failed',
                            'startedAt' => '2026-03-12T08:00:00.000Z',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $telegram = $this->mock(TelegramAlertService::class);
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->with(\Mockery::on(function (string $text): bool {
                return str_contains($text, 'Master Orchestrator')
                    && (str_contains($text, 'Node "Code" failed') || str_contains($text, 'Node &quot;Code&quot; failed'))
                    && str_contains($text, '2026-03-12');
            }))
            ->andReturn(true);

        $this->artisan('n8n:check-failures')
            ->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_command_does_not_alert_twice_for_same_execution_within_24h(): void
    {
        Cache::put('n8n_failure_alerted:exec-1', true, 86400);

        Http::fake([
            'http://n8n.test/api/v1/executions*' => Http::response([
                'data' => [
                    'results' => [
                        [
                            'id' => 'exec-1',
                            'workflowData' => ['name' => 'Some Workflow'],
                            'message' => 'Error',
                            'startedAt' => '2026-03-12T08:00:00.000Z',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $telegram = $this->mock(TelegramAlertService::class);
        $telegram->shouldNotReceive('sendMessage');

        $this->artisan('n8n:check-failures')
            ->assertSuccessful();

        Http::assertSentCount(1);
    }
}
