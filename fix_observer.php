<?php
$content = file_get_contents('packages/Gametech/Lotto/src/Observers/LottoTicketRealtimeObserver.php');

// Add import
if (strpos($content, "use Gametech\Lotto\Support\LottoMarketDisplayFormatter;") === false) {
    $content = str_replace(
        "use Illuminate\Support\Carbon;",
        "use Gametech\Lotto\Support\LottoMarketDisplayFormatter;\nuse Illuminate\Support\Carbon;",
        $content
    );
}

// Update buildPublicActivityMessage
$old_build = <<<OLD
    private function buildPublicActivityMessage(
        string \$action,
        ?string \$marketName,
        ?string \$drawDate,
        ?string \$ownerId,
        ?string \$actorId,
        ?float \$amount,
        ?string \$resultMode = null,
        ?int \$roundNo = null
    ): string {
        \$formatter = new \Gametech\Lotto\Support\LottoMarketDisplayFormatter;
        \$subject = \$formatter->formatDrawSubject((string) \$marketName, (string) \$drawDate, \$resultMode, \$roundNo);
OLD;

$new_build = <<<NEW
    private function buildPublicActivityMessage(
        string \$action,
        ?string \$marketName,
        ?string \$drawDate,
        ?string \$ownerId,
        ?string \$actorId,
        ?float \$amount,
        ?string \$resultMode = null,
        ?int \$roundNo = null
    ): string {
        \$formatter = new LottoMarketDisplayFormatter;
        \$subject = \$formatter->formatDrawSubject((string) \$marketName, (string) \$drawDate, \$resultMode, \$roundNo, true);
NEW;

$content = str_replace($old_build, $new_build, $content);

// Also formatStatusMessage needs to be fullDate? Wait, the resulted message:
$old_result = <<<OLD
        if (\$action === 'resulted') {
            return \$formatter->formatStatusMessage((string) \$marketName, (string) \$drawDate, 'อัปเดตรายการโพยหลังออกผลแล้ว', \$resultMode, \$roundNo);
        }
OLD;

$new_result = <<<NEW
        if (\$action === 'resulted') {
            return \$formatter->formatStatusMessage((string) \$marketName, (string) \$drawDate, 'อัปเดตรายการโพยหลังออกผลแล้ว', \$resultMode, \$roundNo, true);
        }
NEW;

$content = str_replace($old_result, $new_result, $content);

// Now resolveDrawContext: remove Schema check
$old_context = <<<OLD
    private function resolveDrawContext(LottoTicket \$ticket): array
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('yeekee_rounds')) {
            \$ticket->loadMissing(['draw.market', 'draw.yeekeeRound']);
        } else {
            \$ticket->loadMissing(['draw.market']);
        }

        \$marketName = trim((string) data_get(\$ticket, 'draw.market.name', ''));
OLD;

$new_context = <<<NEW
    private function resolveDrawContext(LottoTicket \$ticket): array
    {
        \$ticket->loadMissing(['draw.market', 'draw.yeekeeRound']);

        \$marketName = trim((string) data_get(\$ticket, 'draw.market.name', ''));
NEW;

$content = str_replace($old_context, $new_context, $content);

file_put_contents('packages/Gametech/Lotto/src/Observers/LottoTicketRealtimeObserver.php', $content);
