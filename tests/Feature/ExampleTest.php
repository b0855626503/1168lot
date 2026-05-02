<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_root_entrypoint_returns_not_found()
    {
        $response = $this->get('/');

        $response->assertNotFound();
    }
}
