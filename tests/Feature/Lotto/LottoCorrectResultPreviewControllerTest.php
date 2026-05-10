<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Http\Controllers\Admin\LottoDrawController;
use Gametech\Lotto\Services\ResultCorrectionPreviewService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoCorrectResultPreviewControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'resulted'])->default('draft');
            $table->json('result_number')->nullable();
            $table->timestamps();
        });

        DB::table('lotto_draws')->insert([
            'id' => 11,
            'status' => 'resulted',
            'result_number' => json_encode(['top_3' => '123', 'top_2' => '23', 'bottom_2' => '45']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_draws');
        parent::tearDown();
    }

    public function test_preview_requires_permission_manual_mode_and_reason(): void
    {
        $controller = app(LottoDrawController::class);
        app()->instance('bouncer', new class
        {
            public function hasPermission(string $permission): bool
            {
                return false;
            }
        });

        $response403 = $controller->correctResultPreview(Request::create('/admin/lotto/draws/correct-result-preview', 'POST', [
            'id' => 11,
            'mode' => 'manual',
            'reason' => 'x',
            'result_number' => ['top_3' => '999', 'top_2' => '99', 'bottom_2' => '45'],
        ]), app(ResultCorrectionPreviewService::class));
        $this->assertSame(403, $response403->getStatusCode());

        $controllerSource = file_get_contents(dirname(__DIR__, 3).'/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoDrawController.php');
        $this->assertIsString($controllerSource);
        $this->assertStringContainsString("'mode' => ['required', Rule::in(['manual'])]", $controllerSource);
        $this->assertStringContainsString("'reason' => ['required', 'string', 'max:1000']", $controllerSource);
    }
}
