<?php
$content = file_get_contents('tests/Unit/Lotto/LottoRealtimeObserverTest.php');

$old1 = <<<OLD
        \$event = new LottoTicketListChanged('created', 7, 'หวยมาเลเซีย', '2026-04-05', null, '0855626503', 200.0);
        \$this->assertSame('มีรายการโพยหวยใหม่: หวยมาเลเซีย งวดวันที่ 5 โดย 0855626503 จำนวน 200', \$event->message);
OLD;

$new1 = <<<NEW
        \$event = new LottoTicketListChanged('created', 7, 'หวยมาเลเซีย', '2026-04-05', null, '0855626503', 200.0);
        \$this->assertSame('มีรายการโพยหวยใหม่: หวยมาเลเซีย งวดวันที่ 2026-04-05 โดย 0855626503 จำนวน 200', \$event->message);
NEW;

$content = str_replace($old1, $new1, $content);

$old2 = <<<OLD
        \$this->assertSame('มีการคืนโพยหวย: หวย ธกส. งวดวันที่ 16 ของ xxx โดย xxxx', \$event->message);
OLD;

$new2 = <<<NEW
        \$this->assertSame('มีการคืนโพยหวย: หวย ธกส. งวดวันที่ 2026-04-16 ของ xxx โดย xxxx', \$event->message);
NEW;

$content = str_replace($old2, $new2, $content);

file_put_contents('tests/Unit/Lotto/LottoRealtimeObserverTest.php', $content);
