<?php

namespace Gametech\Reward\Repositories;

use Gametech\Core\Eloquent\Repository;

class RewardRedemptionRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return \Gametech\Reward\Models\RewardRedemption::class;
    }
}
