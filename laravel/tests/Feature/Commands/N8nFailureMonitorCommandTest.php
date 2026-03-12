<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;

class N8nFailureMonitorCommandTest extends TestCase
{
    public function test_command_exists_and_runs_without_throwing(): void
    {
        $this->artisan('n8n:check-failures')
            ->assertSuccessful();
    }
}
