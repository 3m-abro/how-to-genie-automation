<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;

class MissionControlApiTest extends TestCase
{
    public function test_mission_control_status_returns_200_and_modules(): void
    {
        $response = $this->getJson('/api/n8n/status');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('system_status', $data);
        $this->assertArrayHasKey('modules', $data);
    }
}
