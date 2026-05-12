<?php

namespace Gametech\Lotto\Providers;

use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoDrawBetSetting;
use Gametech\Lotto\Models\LottoFrontendThemeSetting;
use Gametech\Lotto\Models\LottoGroupPackage;
use Gametech\Lotto\Models\LottoGroupPackageBetSetting;
use Gametech\Lotto\Models\LottoMarketBetSetting;
use Gametech\Lotto\Models\LottoNavbar;
use Gametech\Lotto\Models\LottoNavbarItem;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LottoNumberExposure;
use Gametech\Lotto\Models\LottoResultCorrection;
use Gametech\Lotto\Models\LottoResultCorrectionItem;
use Gametech\Lotto\Models\LottoResultFetchLog;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Gametech\Lotto\Models\LottoWinning;
use Gametech\Lotto\Models\MemberLottoMarketPolicy;
use Gametech\Lotto\Models\SettlementBatch;
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
        LottoDraw::class,
        LottoDrawBetSetting::class,
        LottoNumberExposure::class,
        LottoResultCorrection::class,
        LottoResultCorrectionItem::class,
        LottoNumberBlock::class,
        LottoGroupPackage::class,
        LottoGroupPackageBetSetting::class,
        LottoTicket::class,
        LottoTicketItem::class,
        SettlementBatch::class,
        LottoWinning::class,
        LottoResultSource::class,
        LottoResultFetchLog::class,
        MemberLottoMarketPolicy::class,
        LottoFrontendThemeSetting::class,
        LottoNavbar::class,
        LottoNavbarItem::class,
    ];
}
