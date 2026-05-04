<?php
$content = file_get_contents('app/Events/LottoTicketListChanged.php');

$old = <<<OLD
    private function buildMessage(string \$baseMessage): string
    {
        \$formatter = new LottoMarketDisplayFormatter;
        \$subject = \$formatter->formatDrawSubject((string) \$this->marketName, (string) \$this->drawDate, \$this->resultMode, \$this->roundNo, true);

        if (\$subject === '-' || \$subject === '- งวดวันที่ -') {
            return \$baseMessage;
        }

        \$message = \$baseMessage . ': ' . \$subject;
OLD;

$new = <<<NEW
    private function buildMessage(string \$baseMessage): string
    {
        \$formatter = new LottoMarketDisplayFormatter;
        \$subject = \$formatter->formatDrawSubject((string) \$this->marketName, (string) \$this->drawDate, \$this->resultMode, \$this->roundNo, true);

        if (\$subject === '-' || \$subject === '- งวดวันที่ -' || \$subject === '- งวดวันที่ ') {
            return \$baseMessage;
        }

        \$message = \$baseMessage . ': ' . \$subject;
NEW;

$content = str_replace($old, $new, $content);
file_put_contents('app/Events/LottoTicketListChanged.php', $content);
