<?php

namespace Gametech\Sms\Providers\Vonage;

use Gametech\Sms\Contracts\SmsProviderInterface;
use Gametech\Sms\Models\SmsRecipient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VonageSmsProvider implements SmsProviderInterface
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey    = config('sms.providers.vonage.api_key');
        $this->apiSecret = config('sms.providers.vonage.api_secret');
        $this->endpoint  = 'https://rest.nexmo.com/sms/json';
    }

    public function send(SmsRecipient $recipient, string $message, ?string $sender = null): array
    {
        try {
            $response = Http::asForm()->post($this->endpoint, [
                'api_key'    => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'to'         => ltrim($recipient->phone_e164, '+'),
                'from'       => $sender ?: config('sms.providers.vonage.from'),
                'text'       => $message,
            ]);

            if (! $response->ok()) {
                return [
                    'success' => false,
                    'error_message' => 'HTTP_ERROR_' . $response->status(),
                ];
            }

            $data = $response->json();
            $msg  = $data['messages'][0] ?? null;

            if (! $msg) {
                return [
                    'success' => false,
                    'error_message' => 'INVALID_PROVIDER_RESPONSE',
                ];
            }

            // Vonage: status = 0 คือ success
            if ((string) $msg['status'] !== '0') {
                return [
                    'success'       => false,
                    'error_code'    => $msg['status'],
                    'error_message' => $msg['error-text'] ?? 'SEND_FAILED',
                ];
            }

            return [
                'success' => true,
                'provider_message_id' => $msg['message-id'] ?? null,
            ];

        } catch (\Throwable $e) {
            Log::channel('sms')->error('[VonageSmsProvider] exception', [
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error_message' => $e->getMessage(),
            ];
        }
    }
}
