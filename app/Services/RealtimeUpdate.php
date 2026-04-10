<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RealtimeUpdate
{
    public function getUpdate()
    {
        $id = auth()->guard('admin')->id();
        $counts = app(RealtimeCounterService::class)->getCounts();

        $announce = [
            'content' => '',
            'updated_at' => now()->toDateTimeString(),
        ];

        $announce_new = 'N';

        $response = Http::get('https://announce.168csn.com/api/announce');

        if ($response->successful()) {
            $response = $response->json();
            //            dd($response);
            $announce = $response['data'];
        }

        //        dd($announce);

        if (! Cache::has($id.'announce_start')) {
            Cache::add($id.'announce_stop', $announce['updated_at']);
        }
        if (! Cache::has($id.'announce_stop')) {
            Cache::add($id.'announce_stop', $announce['updated_at']);
        } else {
            Cache::put($id.'announce_stop', $announce['updated_at']);
        }

        $start = Cache::get($id.'announce_start');
        $stop = Cache::get($id.'announce_stop');
        if ($start != $stop) {
            $announce_new = 'Y';
            Cache::put($this->id().'announce_start', $stop);
        }

        $result = $counts;
        $result['announce'] = $announce['content'];
        $result['announce_new'] = $announce_new;

        return $result;
    }
}
