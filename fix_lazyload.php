<?php
$content = file_get_contents('packages/Gametech/Lotto/src/Observers/LottoTicketRealtimeObserver.php');

$old = <<<OLD
    private function resolveDrawContext(LottoTicket \$ticket): array
    {
        \$ticket->loadMissing(['draw.market', 'draw.yeekeeRound']);

        \$marketName = trim((string) data_get(\$ticket, 'draw.market.name', ''));
        \$drawDate = \$ticket->draw?->draw_date?->format('Y-m-d');
        \$resultMode = (string) data_get(\$ticket, 'draw.market.result_mode', '');
OLD;

$new = <<<NEW
    private function resolveDrawContext(LottoTicket \$ticket): array
    {
        \$ticket->loadMissing(['draw.market']);

        \$marketName = trim((string) data_get(\$ticket, 'draw.market.name', ''));
        \$drawDate = \$ticket->draw?->draw_date?->format('Y-m-d');
        \$resultMode = (string) data_get(\$ticket, 'draw.market.result_mode', '');

        if (\$resultMode === \Gametech\Lotto\Models\LotteryMarket::RESULT_MODE_YEEKEE) {
            \$ticket->loadMissing(['draw.yeekeeRound']);
        }
NEW;

$content = str_replace($old, $new, $content);
file_put_contents('packages/Gametech/Lotto/src/Observers/LottoTicketRealtimeObserver.php', $content);
