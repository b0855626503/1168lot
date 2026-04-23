<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\DataTables\LottoNavbarDataTable;
use Gametech\Lotto\Models\LottoNavbar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoNavbarDataTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('lotto_navbars');

        Schema::create('lotto_navbars', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 64);
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('published_version')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_navbars');

        parent::tearDown();
    }

    public function test_query_returns_only_active_rows(): void
    {
        DB::table('lotto_navbars')->insert([
            [
                'id' => 1,
                'code' => 'mobile_bottom_nav',
                'name' => 'Active published',
                'is_active' => 1,
                'is_published' => 1,
                'published_version' => 2,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'code' => 'mobile_bottom_nav',
                'name' => 'Inactive published',
                'is_active' => 0,
                'is_published' => 1,
                'published_version' => 1,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $dataTable = new LottoNavbarDataTable;
        $rows = $dataTable->query(new LottoNavbar)->get();

        $this->assertCount(1, $rows);
        $this->assertSame(1, (int) $rows->first()->id);
    }
}
