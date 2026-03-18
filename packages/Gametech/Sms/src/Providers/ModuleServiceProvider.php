<?php

namespace Gametech\Sms\Providers;

use Gametech\Sms\Models\SmsCampaign;
use Gametech\Sms\Models\SmsDeliveryReceipt;
use Gametech\Sms\Models\SmsImportBatch;
use Gametech\Sms\Models\SmsOptOut;
use Gametech\Sms\Models\SmsRecipient;
use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [
        SmsCampaign::class,
        SmsDeliveryReceipt::class,
        SmsImportBatch::class,
        SmsOptOut::class,
        SmsRecipient::class,
    ];
}
