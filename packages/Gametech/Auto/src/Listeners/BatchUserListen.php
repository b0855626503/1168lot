<?php

namespace  Gametech\Auto\Listeners;

use Gametech\Auto\Events\BatchUser as BatchUsers;
use Gametech\Auto\Jobs\BatchUser as BatchUserJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class BatchUserListen implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'batch';


    public function handle(BatchUsers $event)
    {

        $data = $event->items;

        DB::table('temp_index')->select('id')->whereBetween('code',[$data['batch_start'],$data['batch_stop']])->orderBy('code')->chunk(5000, function ($values) use ($data) {
            $items = [];
            foreach ($values as $item) {
                $items[] = [
                    'batch_code' => $data['code'],
                    'user_name' => $data['prefix'].$item->id,
                    'freecredit' =>  $data['freecredit'],
                    'user_create' =>  $data['user_create'],
                    'user_update' =>  $data['user_update'],
                    'date_create' =>  $data['date_create'],
                    'date_update' =>  $data['date_update']
                ];
            }

            BatchUserJob::dispatch($data['game_id'],$items)->onQueue('batch');

        });
    }

}
