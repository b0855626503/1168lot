<?php

namespace Gametech\Lotto\Contracts;

use Gametech\Lotto\Models\LottoResultArchive;

interface ArchiveWriteResult
{
    public function getStatus(): string;

    public function getArchive(): ?LottoResultArchive;

    public function getPreviousResultSet(): ?array;

    public function getLogId(): ?int;
}
