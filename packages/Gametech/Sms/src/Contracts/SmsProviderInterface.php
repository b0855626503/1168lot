<?php

namespace Gametech\Sms\Contracts;

use Gametech\Sms\Models\SmsRecipient;

interface SmsProviderInterface
{
    /**
     * ส่ง SMS ออกไป
     *
     * @return array{
     *   success: bool,
     *   provider_message_id?: string,
     *   error_code?: string,
     *   error_message?: string
     * }
     */
    public function send(SmsRecipient $recipient, string $message, ?string $sender = null): array;
}
