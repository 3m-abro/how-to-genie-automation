<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;

class RevenueDashboardApiTest extends TestCase
{
    public function test_revenue_endpoint_returns_200_and_expected_keys(): void
    {
        $response = $this->getJson('/api/dashboard/revenue');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue(
            isset($data['content_stats']) || isset($data['revenue_data']),
            'Response must contain content_stats or revenue_data'
        );
    }
}
