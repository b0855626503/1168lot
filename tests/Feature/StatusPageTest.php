<?php

namespace Tests\Feature;

use Tests\TestCase;

class StatusPageTest extends TestCase
{
    public function test_admin_root_redirects_to_login(): void
    {
        $response = $this->get('http://admin.localhost/');

        $response->assertRedirect('http://admin.localhost/login');
    }

    public function test_status_page_renders_with_status_ping_url(): void
    {
        $response = $this->get('http://admin.localhost/status');

        $response
            ->assertOk()
            ->assertSee('http://admin.localhost/status/ping', false);
    }

    public function test_status_ping_returns_json(): void
    {
        $response = $this->getJson('http://admin.localhost/status/ping');

        $response
            ->assertOk()
            ->assertJsonPath('pong', true)
            ->assertJsonStructure(['pong', 'time']);
    }
}
