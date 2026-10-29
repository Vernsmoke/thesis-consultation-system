<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_liveness_returns_only_public_project_metadata(): void
    {
        $this->getJson('/api/health')->assertOk()->assertExactJson([
            'status' => 'ok',
            'project' => 'Thesis Consultation Reminder System',
            'week' => 13,
            'milestone' => 'foundation',
        ]);
    }

    public function test_configured_flutter_origin_can_read_health(): void
    {
        $this->withHeaders(['Origin' => 'http://127.0.0.1:8080'])
            ->getJson('/api/health')
            ->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:8080');
    }

    public function test_unlisted_origin_is_not_granted_browser_access(): void
    {
        $this->withHeaders(['Origin' => 'https://untrusted.example'])
            ->getJson('/api/health')
            // A fixed allowed origin still blocks a different requesting origin.
            ->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:8080');
    }
}
