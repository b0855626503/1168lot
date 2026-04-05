<?php

namespace Tests\Unit\FrontendApi;

use Tests\TestCase;

class FrontendApiControllerDependencyTest extends TestCase
{
    private string $controllerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controllerPath = base_path('packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1');
    }

    public function test_frontend_api_controllers_do_not_import_other_package_controllers(): void
    {
        foreach (glob($this->controllerPath . '/*.php') as $file) {
            if (basename($file) === 'BaseController.php') {
                continue;
            }

            $contents = file_get_contents($file);

            $this->assertIsString($contents);
            $this->assertDoesNotMatchRegularExpression(
                '/^use\s+Gametech\\\\(?!FrontendApi\\\\)[^;]*\\\\Http\\\\Controllers\\\\/m',
                $contents,
                basename($file) . ' should not import controllers from other packages'
            );
        }
    }

    public function test_frontend_api_controllers_do_not_resolve_other_controllers_via_container(): void
    {
        foreach (glob($this->controllerPath . '/*.php') as $file) {
            if (basename($file) === 'BaseController.php') {
                continue;
            }

            $contents = file_get_contents($file);

            $this->assertIsString($contents);
            $this->assertDoesNotMatchRegularExpression(
                '/app\s*\(\s*[^)]*Controller::class\s*\)/',
                $contents,
                basename($file) . ' should not resolve controllers via app()'
            );
        }
    }
}
