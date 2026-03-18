<?php

namespace Gametech\LineOA\Providers;

use Gametech\LineOA\Models\LineAccount;
use Gametech\LineOA\Models\LineContact;
use Gametech\LineOA\Models\LineConversation;
use Gametech\LineOA\Models\LineConversationNote;
use Gametech\LineOA\Models\LineMessage;
use Gametech\LineOA\Models\LineRegisterSession;
use Gametech\LineOA\Models\LineTemplate;
use Gametech\LineOA\Models\LineWebhookLog;
use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [
        LineAccount::class,
        LineContact::class,
        LineConversation::class,
        LineMessage::class,
        LineTemplate::class,
        LineRegisterSession::class,
        LineWebhookLog::class,
        LineConversationNote::class,
    ];
}
