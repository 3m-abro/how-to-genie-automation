<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;

class WeeklySummaryCommandTest extends TestCase
{
    public function test_command_exists_and_runs_without_throwing(): void
    {
        $this->artisan('weekly:summary')
            ->assertSuccessful();
    }
}
