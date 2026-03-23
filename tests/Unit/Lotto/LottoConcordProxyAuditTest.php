<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

/**
 * Audits the Lotto module for correct Concord Proxy usage.
 *
 * Rules enforced:
 * 1. Service layer must NOT instantiate Eloquent models with `new ModelName()`.
 *    Models must be accessed via static Eloquent methods (::query(), ::create(), etc.)
 *    which Concord can intercept through its Proxy registration.
 * 2. Every core Lotto model must have a corresponding *Proxy.php sibling file.
 * 3. ModuleServiceProvider must register all core models so Concord proxies them.
 */
class LottoConcordProxyAuditTest extends TestCase
{
    private string $base;
    private string $servicePath;
    private string $modelPath;
    private string $providerPath;
    private string $apiControllerPath;
    private string $adminControllerPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base         = dirname(__DIR__, 3) . '/packages/Gametech/Lotto/src';
        $this->servicePath  = $this->base . '/Services';
        $this->modelPath    = $this->base . '/Models';
        $this->providerPath = $this->base . '/Providers/ModuleServiceProvider.php';
        $this->apiControllerPath = $this->base . '/Http/Controllers/Api';
        $this->adminControllerPath = $this->base . '/Http/Controllers/Admin';
    }

    // ------------------------------------------------------------------ //
    // 1. Service files must not use `new ModelName()` directly
    // ------------------------------------------------------------------ //

    /**
     * @dataProvider serviceFileProvider
     */
    public function test_service_does_not_directly_instantiate_eloquent_models(string $file): void
    {
        $content = file_get_contents($this->servicePath . '/' . $file);
        $this->assertNotFalse($content, "Service file {$file} not found");

        foreach ($this->protectedModels() as $model) {
            $this->assertDoesNotMatchRegularExpression(
                '/\\bnew\\s+' . preg_quote($model, '/') . '\\s*[(\\[;]/',
                $content,
                "Service {$file} directly instantiates '{$model}' with `new`; use ::query() or static methods instead"
            );
        }
    }

    public static function serviceFileProvider(): array
    {
        return [
            'BetService'               => ['BetService.php'],
            'DrawService'              => ['DrawService.php'],
            'ExposureService'          => ['ExposureService.php'],
            'SettlementService'        => ['SettlementService.php'],
            'MemberMarketPolicyService' => ['MemberMarketPolicyService.php'],
        ];
    }

    // ------------------------------------------------------------------ //
    // 2. Every core model must have a *Proxy.php sibling
    // ------------------------------------------------------------------ //

    /**
     * @dataProvider coreModelProvider
     */
    public function test_model_has_proxy_sibling(string $modelName): void
    {
        $this->assertFileExists(
            $this->modelPath . '/' . $modelName . '.php',
            "Model file {$modelName}.php is missing"
        );

        $this->assertFileExists(
            $this->modelPath . '/' . $modelName . 'Proxy.php',
            "Proxy file {$modelName}Proxy.php is missing for model {$modelName}"
        );
    }

    public static function coreModelProvider(): array
    {
        $models = [
            'LotteryGroup',
            'LotteryMarket',
            'LottoMarketBetSetting',
            'LottoDraw',
            'LottoDrawBetSetting',
            'LottoNumberExposure',
            'LottoNumberBlock',
            'LottoTicket',
            'LottoTicketItem',
            'MemberLottoMarketPolicy',
        ];

        return array_combine($models, array_map(fn ($m) => [$m], $models));
    }

    // ------------------------------------------------------------------ //
    // 3. ModuleServiceProvider must register all core models
    // ------------------------------------------------------------------ //

    public function test_module_service_provider_registers_all_core_models(): void
    {
        $content = file_get_contents($this->providerPath);
        $this->assertNotFalse($content, 'ModuleServiceProvider.php not found');

        $requiredRegistrations = [
            'LotteryGroup::class',
            'LotteryMarket::class',
            'LottoMarketBetSetting::class',
            'LottoDraw::class',
            'LottoDrawBetSetting::class',
            'LottoNumberExposure::class',
            'LottoNumberBlock::class',
            'LottoTicket::class',
            'LottoTicketItem::class',
            'MemberLottoMarketPolicy::class',
        ];

        foreach ($requiredRegistrations as $registration) {
            $this->assertStringContainsString(
                $registration,
                $content,
                "Model {$registration} is not registered in ModuleServiceProvider \$models array"
            );
        }
    }

    public function test_module_service_provider_extends_base_module_service_provider(): void
    {
        $content = file_get_contents($this->providerPath);
        $this->assertNotFalse($content, 'ModuleServiceProvider.php not found');

        $this->assertStringContainsString('BaseModuleServiceProvider', $content,
            'ModuleServiceProvider must extend BaseModuleServiceProvider for Concord proxy registration'
        );
    }

    // ------------------------------------------------------------------ //
    // 4. Proxy files must reference the concrete model class
    // ------------------------------------------------------------------ //

    /**
     * @dataProvider coreModelProvider
     */
    public function test_proxy_file_references_concrete_model(string $modelName): void
    {
        $proxyFile = $this->modelPath . '/' . $modelName . 'Proxy.php';

        if (! file_exists($proxyFile)) {
            $this->markTestSkipped("Proxy file for {$modelName} not found");
        }

        $content = file_get_contents($proxyFile);

        $this->assertStringContainsString(
            $modelName,
            $content,
            "Proxy file {$modelName}Proxy.php does not reference the concrete model class {$modelName}"
        );
    }

    // ------------------------------------------------------------------ //
    // 5. API and Admin controllers must not use `new ModelName()` directly
    // ------------------------------------------------------------------ //

    /**
     * @dataProvider apiControllerFileProvider
     */
    public function test_api_controller_does_not_directly_instantiate_lotto_models(string $file): void
    {
        $content = file_get_contents($this->apiControllerPath . '/' . $file);
        $this->assertNotFalse($content, "API controller file {$file} not found");

        foreach ($this->protectedModels() as $model) {
            $this->assertDoesNotMatchRegularExpression(
                '/\\bnew\\s+' . preg_quote($model, '/') . '\\s*[(\\[;]/',
                $content,
                "API controller {$file} directly instantiates '{$model}' with `new`; delegate to service/static query instead"
            );
        }
    }

    /**
     * @dataProvider adminControllerFileProvider
     */
    public function test_admin_controller_does_not_directly_instantiate_lotto_models(string $file): void
    {
        $content = file_get_contents($this->adminControllerPath . '/' . $file);
        $this->assertNotFalse($content, "Admin controller file {$file} not found");

        foreach ($this->protectedModels() as $model) {
            $this->assertDoesNotMatchRegularExpression(
                '/\\bnew\\s+' . preg_quote($model, '/') . '\\s*[(\\[;]/',
                $content,
                "Admin controller {$file} directly instantiates '{$model}' with `new`; delegate to service/static query instead"
            );
        }
    }

    public static function apiControllerFileProvider(): array
    {
        return [
            'DrawController' => ['DrawController.php'],
            'BetController' => ['BetController.php'],
            'TicketController' => ['TicketController.php'],
        ];
    }

    public static function adminControllerFileProvider(): array
    {
        return [
            'LotteryGroupController' => ['LotteryGroupController.php'],
            'LotteryMarketController' => ['LotteryMarketController.php'],
            'LottoMarketBetSettingController' => ['LottoMarketBetSettingController.php'],
            'LottoDrawController' => ['LottoDrawController.php'],
            'LottoNumberBlockController' => ['LottoNumberBlockController.php'],
            'LottoTicketController' => ['LottoTicketController.php'],
            'MemberLottoPermissionController' => ['MemberLottoPermissionController.php'],
            'LottoExposureReportController' => ['LottoExposureReportController.php'],
            'LottoRevenueReportController' => ['LottoRevenueReportController.php'],
        ];
    }

    private function protectedModels(): array
    {
        return [
            'LotteryGroup',
            'LotteryMarket',
            'LottoDraw',
            'LottoDrawBetSetting',
            'LottoMarketBetSetting',
            'LottoTicket',
            'LottoTicketItem',
            'LottoNumberExposure',
            'LottoNumberBlock',
            'MemberLottoMarketPolicy',
        ];
    }
}
