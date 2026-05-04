<?php
$content = file_get_contents('app/Events/LottoTicketListChanged.php');

$old_import = <<<OLD
namespace App\Events;

use Illuminate\Broadcasting\Channel;
OLD;

$new_import = <<<NEW
namespace App\Events;

use Gametech\Lotto\Support\LottoMarketDisplayFormatter;
use Illuminate\Broadcasting\Channel;
NEW;

$content = str_replace($old_import, $new_import, $content);

$old_msg = <<<OLD
    private function buildMessage(string \$baseMessage): string
    {
        \$formatter = new \Gametech\Lotto\Support\LottoMarketDisplayFormatter;
        \$subject = \$formatter->formatDrawSubject((string) \$this->marketName, (string) \$this->drawDate, \$this->resultMode, \$this->roundNo);
OLD;

$new_msg = <<<NEW
    private function buildMessage(string \$baseMessage): string
    {
        \$formatter = new LottoMarketDisplayFormatter;
        \$subject = \$formatter->formatDrawSubject((string) \$this->marketName, (string) \$this->drawDate, \$this->resultMode, \$this->roundNo, true);
NEW;

$content = str_replace($old_msg, $new_msg, $content);

file_put_contents('app/Events/LottoTicketListChanged.php', $content);
