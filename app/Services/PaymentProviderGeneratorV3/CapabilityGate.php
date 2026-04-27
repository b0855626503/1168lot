<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGeneratorV3;

final class CapabilityGate
{
    public function evaluate(array $analysis): array
    {
        $capabilities = (array) ($analysis['capabilities'] ?? []);
        $auth = (array) ($analysis['auth'] ?? []);

        $questions = [];

        if (empty($capabilities['withdraw']) && config('payment_provider_generator.interactive.ask_when_missing_withdraw', true)) {
            $questions[] = [
                'key' => 'missing_withdraw',
                'message' => 'ไม่พบ withdraw/payout ในเอกสาร ต้องการทำอย่างไร?',
                'options' => ['skip', 'stub', 'abort'],
                'default' => 'stub',
            ];
        }

        if (empty($capabilities['callback']) && config('payment_provider_generator.interactive.ask_when_missing_callback', true)) {
            $questions[] = [
                'key' => 'missing_callback',
                'message' => 'ไม่พบ callback/webhook ในเอกสาร ต้องการทำอย่างไร?',
                'options' => ['polling', 'manual_confirm', 'abort'],
                'default' => 'polling',
            ];
        }

        if (empty($capabilities['balance']) && config('payment_provider_generator.interactive.ask_when_missing_balance', true)) {
            $questions[] = [
                'key' => 'missing_balance',
                'message' => 'ไม่พบ balance API ในเอกสาร ต้องการทำอย่างไร?',
                'options' => ['skip', 'stub', 'abort'],
                'default' => 'stub',
            ];
        }

        if (!empty($auth['unknown']) && config('payment_provider_generator.interactive.ask_when_signature_unknown', true)) {
            $questions[] = [
                'key' => 'unknown_auth',
                'message' => 'ไม่พบ auth/signature ชัดเจนในเอกสาร ต้องการทำอย่างไร?',
                'options' => ['api_key_only', 'manual_fill_later', 'abort'],
                'default' => 'manual_fill_later',
            ];
        }

        return [
            'requires_confirmation' => !empty($questions),
            'questions' => $questions,
        ];
    }
}
