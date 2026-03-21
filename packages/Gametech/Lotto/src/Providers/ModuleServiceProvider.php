<?php

namespace Gametech\Lotto\Providers;

use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoMarketBetSetting;
use Gametech\Lotto\Models\LottoRatePlan;
use Gametech\Lotto\Models\LottoRatePlanItem;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoDrawBetSetting;
use Gametech\Lotto\Models\LottoNumberExposure;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Gametech\Lotto\Models\MemberLottoMarketPolicy;
use Gametech\Lotto\Models\MemberLottoPermission;
use Gametech\Lotto\Models\MemberLottoSetting;
use Konekt\Concord\BaseModuleServiceProvider;

/**
 * Concord Module Service Provider
 * Registers all Lotto models as Proxy classes
 */
class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        LotteryGroup::class,
        LotteryMarket::class,
        LottoMarketBetSetting::class,
        LottoRatePlan::class,
        LottoRatePlanItem::class,
        LottoDraw::class,
        LottoDrawBetSetting::class,
        LottoNumberExposure::class,
        LottoNumberBlock::class,
        LottoTicket::class,
        LottoTicketItem::class,
        MemberLottoMarketPolicy::class,
        MemberLottoPermission::class,
        MemberLottoSetting::class,
    ];
}

