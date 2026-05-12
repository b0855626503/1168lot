<?php

namespace Gametech\Lotto\Services;

class ArchiveChecksumService
{
    /**
     * Compute deterministic SHA-256 hash of a result set.
     * result_set is treated as an unordered set: sorted before hashing.
     *
     * @param  array<string>  $resultSet
     */
    public function computeResultHash(array $resultSet): string
    {
        $sorted = $resultSet;
        sort($sorted, SORT_STRING);

        return hash('sha256', implode('|', $sorted));
    }

    public function verifyIntegrity(string $storedHash, array $resultSet): bool
    {
        return hash_equals($storedHash, $this->computeResultHash($resultSet));
    }
}
