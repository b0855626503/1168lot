<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Services\ArchiveChecksumService;
use PHPUnit\Framework\TestCase;

class ArchiveChecksumServiceTest extends TestCase
{
    private ArchiveChecksumService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ArchiveChecksumService;
    }

    public function test_compute_result_hash_is_deterministic(): void
    {
        $hash1 = $this->service->computeResultHash(['123', '456']);
        $hash2 = $this->service->computeResultHash(['123', '456']);

        $this->assertSame($hash1, $hash2);
    }

    public function test_compute_result_hash_is_order_independent(): void
    {
        $hashA = $this->service->computeResultHash(['456', '123', '789']);
        $hashB = $this->service->computeResultHash(['789', '123', '456']);

        $this->assertSame($hashA, $hashB);
    }

    public function test_compute_result_hash_different_inputs_produce_different_hash(): void
    {
        $hash1 = $this->service->computeResultHash(['123', '456']);
        $hash2 = $this->service->computeResultHash(['789', '012']);

        $this->assertNotSame($hash1, $hash2);
    }

    public function test_compute_result_hash_single_value(): void
    {
        $hash = $this->service->computeResultHash(['47']);

        $this->assertNotEmpty($hash);
        $this->assertSame(64, strlen($hash));
    }

    public function test_verify_integrity_matching_hash(): void
    {
        $resultSet = ['01', '007', '123'];
        $hash = $this->service->computeResultHash($resultSet);

        $this->assertTrue($this->service->verifyIntegrity($hash, $resultSet));
    }

    public function test_verify_integrity_mismatched_hash(): void
    {
        $hash = $this->service->computeResultHash(['123']);
        $different = ['456'];

        $this->assertFalse($this->service->verifyIntegrity($hash, $different));
    }

    public function test_compute_result_hash_preserves_leading_zeros_in_comparison(): void
    {
        $hashWithZero = $this->service->computeResultHash(['01', '007']);
        $hashWithoutZero = $this->service->computeResultHash(['1', '7']);

        $this->assertNotSame($hashWithZero, $hashWithoutZero);
    }
}
