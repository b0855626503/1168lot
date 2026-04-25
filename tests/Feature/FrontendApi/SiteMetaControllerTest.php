<?php

namespace Tests\Feature\FrontendApi;

use Gametech\Core\Core;
use Gametech\FrontendApi\Http\Controllers\Api\V1\SiteMetaController;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class SiteMetaControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_site_meta_expands_logo_filename_to_full_url(): void
    {
        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')->once()->andReturn((object) [
            'logo' => 'tkTXUbWIoi.png',
            'title' => 'Title',
            'sitename' => 'Galaxy',
            'name_th' => null,
            'description' => 'Desc',
            'header_code' => '<script>window.analytics=true;</script>',
        ]);

        $this->app->instance(Core::class, $core);

        $response = TestResponse::fromBaseResponse(app(SiteMetaController::class)->info());

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('header_code', '<script>window.analytics=true;</script>');
        $this->assertStringStartsWith((string) url('/storage/img/tkTXUbWIoi.png'), (string) $response->json('logo'));
    }

    public function test_site_meta_keeps_absolute_logo_url(): void
    {
        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')->once()->andReturn((object) [
            'logo' => 'https://cdn.example.com/logo.png',
            'title' => 'Title',
            'sitename' => 'Galaxy',
            'name_th' => null,
            'description' => 'Desc',
            'header_code' => null,
        ]);

        $this->app->instance(Core::class, $core);

        $response = TestResponse::fromBaseResponse(app(SiteMetaController::class)->info());

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertStringStartsWith('https://cdn.example.com/logo.png', (string) $response->json('logo'));
    }
}
