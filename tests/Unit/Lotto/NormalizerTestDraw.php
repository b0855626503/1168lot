<?php

namespace Tests\Unit\Lotto;

use Illuminate\Support\Carbon;

/**
 * Test double for LottoDraw used in Normalizer tests.
 * Plain class — does NOT extend Eloquent Model to avoid DB connection.
 */
class NormalizerTestDraw
{
    public int $id;
    public object $market;
    public Carbon $draw_date;
    public ?array $result_number;
    public object $betSettings;

    public function __construct(
        int $id,
        string $marketCode,
        string $drawDate,
        ?array $resultNumber,
        array $betTypes,
    ) {
        $this->id = $id;
        $this->market = new NormalizerTestMarket($marketCode);
        $this->draw_date = Carbon::parse($drawDate);
        $this->result_number = $resultNumber;
        $this->betSettings = collect($betTypes)->map(
            fn (string $type) => new NormalizerTestBetSetting($type)
        );
    }
}

class NormalizerTestMarket
{
    public string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }
}

class NormalizerTestBetSetting
{
    public string $bet_type;

    public function __construct(string $betType)
    {
        $this->bet_type = $betType;
    }
}
