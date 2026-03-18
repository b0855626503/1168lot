<?php

namespace Gametech\Auto\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;


class BatchUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    public $tries = 1;

    public $maxExceptions = 3;

    public $retryAfter = 3;

    protected $items;

    protected $game;

    public function __construct($game, $items)
    {
        $this->items = $items;
        $this->game = $game;
    }


    public function handle(): bool
    {
        $items = collect($this->items)->toArray();
        $games = $this->game;

        return DB::table('users_' . $games)->insert($items);

    }
}
