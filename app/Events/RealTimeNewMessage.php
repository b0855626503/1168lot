<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RealTimeNewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** ข้อความหลัก */
    public string $message;

    /** ui = 'toast' หรือ 'swal' */
    public string $ui;

    /** ตัวเลือก Toastify */
    public array $toast;

    /** ตัวเลือก SweetAlert2 (Swal.fire) */
    public array $swal;

    /** ชื่ออีเวนต์ (เช่น RealTime.Message.All) */
    public string $as;

    /** ชื่อ channel (เช่น AppName_events) */
    public string $channel;

    /** key เสียงเช่น 'withdraw', 'deposit' */
    public ?string $sound = null;

    /**
     * @param string $message ข้อความหลัก
     * @param array $options กำหนด ui/as/channel/toast/swal/sound
     * ตัวอย่าง:
     *   new RealTimeNewMessage('ข้อความ', [
     *       'ui' => 'toast', // หรือ 'swal'
     *       'as' => 'RealTime.Message.All',
     *       'channel' => config('app.name').'_events',
     *       'sound' => 'withdraw', // 👈 ใช้คู่กับ handleRT
     *       'toast' => ['duration'=>15000,'gravity'=>'bottom','position'=>'left'],
     *       'swal'  => ['title'=>'สำเร็จ','icon'=>'success','timer'=>5000,'showConfirmButton'=>false],
     *   ])
     */
    public function __construct(string $message, array $options = [])
    {
        $this->message = $message;

        $defaults = [
            'ui' => 'toast',
            'as' => 'RealTime.Message.All',
            'channel' => config('app.name') . '_events',
            'sound' => null,
            'toast' => [
                'duration' => 20000,
                'gravity' => 'top',   // 'top' | 'bottom'
                'position' => 'right', // 'left' | 'center' | 'right'
                'newWindow' => true,
                'close' => true,
                'stopOnFocus' => true,
            ],
            'swal' => [
                'title' => 'แจ้งเตือน',
                'text' => $message,
                'icon' => 'info',   // 'success' | 'error' | 'warning' | 'info' | 'question'
                'timer' => null,     // ใส่ ms ถ้าอยาก auto close
                'showConfirmButton' => true,
            ],
        ];

        $opt = array_replace_recursive($defaults, $options);

        // ถ้าไม่ได้ส่ง text ใน swal ให้ใช้ message เป็นค่าเริ่มต้น
        if (empty($opt['swal']['text'])) {
            $opt['swal']['text'] = $message;
        }

        $this->ui      = $opt['ui'];
        $this->as      = $opt['as'];
        $this->channel = $opt['channel'];
        $this->toast   = $opt['toast'];
        $this->swal    = $opt['swal'];
        $this->sound   = $opt['sound'] ?? null;
    }

    public function broadcastOn(): array
    {
        return [new Channel($this->channel)];
    }

    public function broadcastAs(): string
    {
        return $this->as; // เช่น RealTime.Message.All
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'ui'      => $this->ui,   // 'toast' | 'swal'
            'sound'   => $this->sound,
            'toast'   => $this->toast,
            'swal'    => $this->swal,
        ];
    }
}
