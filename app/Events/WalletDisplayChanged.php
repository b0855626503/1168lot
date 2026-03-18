<?php
// app/Events/WalletDisplayChanged.php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class WalletDisplayChanged implements ShouldBroadcast
{
    use SerializesModels;

    public int $account_code;
    public string $display_wallet; // 'Y' หรือ 'N'
    public ?string $by;

    public function __construct(int $account_code, string $display_wallet, ?string $by = null)
    {
        $this->account_code = $account_code;
        $this->display_wallet = $display_wallet;
        $this->by = $by;
    }

    public function broadcastOn()
    {
// ✅ ช่องเฉพาะสมาชิกที่ล็อกอิน
        return new PrivateChannel(config('app.name') . '_members');
    }

    public function broadcastAs()
    {
        return 'WalletDisplayChanged';
    }
}
