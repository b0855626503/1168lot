<?php

namespace Gametech\Lotto\Support;

use Gametech\Lotto\Models\LotteryMarket;

class LottoMarketDisplayFormatter
{
    public function formatPlain(string $marketName, ?string $resultMode = null, ?int $roundNo = null): string
    {
        $name = trim($marketName) !== '' ? trim($marketName) : '-';

        if (! $this->shouldShowRoundBadge($resultMode, $roundNo)) {
            return $name;
        }

        return sprintf('%s (รอบ %d)', $name, (int) $roundNo);
    }

    public function formatHtml(
        string $marketName,
        ?string $logo = null,
        ?string $icon = null,
        ?string $resultMode = null,
        ?int $roundNo = null
    ): string {
        $rawName = trim($marketName) !== '' ? trim($marketName) : '-';
        $label = e($rawName);
        $image = trim((string) $logo) !== '' ? trim((string) $logo) : trim((string) $icon);
        $roundBadge = $this->buildRoundBadge($resultMode, $roundNo);
        $nameWithRound = '<span>'.$label.'</span>'.$roundBadge;

        if ($image === '') {
            return $nameWithRound;
        }

        return '<span class="d-inline-flex align-items-center">'
            .'<img src="'.e($image).'" alt="" style="width:18px;height:18px;object-fit:contain;margin-right:6px;" />'
            .$nameWithRound
            .'</span>';
    }

    private function buildRoundBadge(?string $resultMode, ?int $roundNo): string
    {
        if (! $this->shouldShowRoundBadge($resultMode, $roundNo)) {
            return '';
        }

        return '<span class="badge badge-info ml-1" style="font-size:10px;line-height:1.1;">รอบ '.(int) $roundNo.'</span>';
    }

    private function shouldShowRoundBadge(?string $resultMode, ?int $roundNo): bool
    {
        if ((string) $resultMode !== LotteryMarket::RESULT_MODE_YEEKEE) {
            return false;
        }

        return (int) $roundNo > 0;
    }
}
