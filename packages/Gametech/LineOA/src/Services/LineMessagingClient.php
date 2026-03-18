<?php

namespace Gametech\LineOA\Services;

use Gametech\LineOA\Models\LineAccount;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineMessagingClient
{
    /**
     * base URL ของ LINE Messaging API
     */
    protected string $baseUrl = 'https://api.line.me';

    protected string $apiUrl = 'https://api-data.line.me';

    /**
     * คืน Http client ที่ config token / baseUrl ให้เรียบร้อยแล้ว (สำหรับ JSON)
     */
    public function http(LineAccount $account)
    {
        $timeout = (int) (config('line_oa.http_timeout', 5));

        return Http::withToken($account->access_token)
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($timeout);
    }

    /**
     * ส่งข้อความ text แบบ push
     */
    public function pushText(LineAccount $account, string $toUserId, string $text, array $extraPayload = [], ?string $quoteToken = null): array
    {
        $message = array_merge([
            'type' => 'text',
            'text' => $text,
        ], $extraPayload);

        $payload = [
            'to' => $toUserId,
            'messages' => [$message],
        ];

        return $this->sendRequest($account, '/v2/bot/message/push', $payload, 'pushText');
    }

    /**
     * ส่งรูปแบบ push image (ต้องเป็น URL ที่ LINE เข้าถึงได้จากภายนอก และควรเป็น https)
     *
     * @return array{success: bool, status: int|null, body: mixed, error: string|null}
     */
    public function pushImage(LineAccount $account, string $toUserId, string $originalContentUrl, string $previewImageUrl, array $extraPayload = [], ?string $quoteToken = null): array
    {
        $payload = [
            'to' => $toUserId,
            'messages' => [[
                'type' => 'image',
                'originalContentUrl' => $originalContentUrl,
                'previewImageUrl' => $previewImageUrl,
            ]],
        ];

        return $this->sendRequest($account, '/v2/bot/message/push', $payload, 'pushImage');
    }

    public function pushSticker(LineAccount $account, string $toUserId, string $packageId, string $stickerId, array $extraPayload = [], ?string $quoteToken = null): array
    {
        $message = array_merge([
            'type' => 'sticker',
            'packageId' => $packageId,
            'stickerId' => $stickerId,
        ], $extraPayload);

        $payload = [
            'to' => $toUserId,
            'messages' => [$message],
        ];

        return $this->sendRequest($account, '/v2/bot/message/push', $payload, 'pushSticker');
    }

    /**
     * ส่งรูปแบบ push message (ให้ลูกค้าดูรูป)
     */
    public function sendImageMessage(LineAccount $account, string $lineUserId, string $originalUrl, string $previewUrl, ?string $quoteToken = null): array
    {
        $message = [
            'type' => 'image',
            'originalContentUrl' => $originalUrl,
            'previewImageUrl' => $previewUrl ?: $originalUrl,
        ];

        if ($quoteToken) {
            $message['quoteToken'] = $quoteToken;
        }


        return $this->pushMessages($account, $lineUserId, [$message]);
    }

    /**
     * ส่งข้อความหลายอันแบบ push
     */
    public function pushMessages(LineAccount $account, string $toUserId, array $messages, array $extraPayload = [], ?string $quoteToken = null): array
    {
        if ($quoteToken && ! empty($messages)) {
            // แนบ quoteToken ให้ message ตัวแรก (ถ้ายังไม่ได้ระบุ)
            if (is_array($messages[0]) && ! array_key_exists('quoteToken', $messages[0])) {
                $messages[0]['quoteToken'] = $quoteToken;
            }
        }

        $payload = [
            'to' => $toUserId,
            'messages' => $messages,
        ];

        return $this->sendRequest($account, '/v2/bot/message/push', $payload, 'pushMessages');
    }

    /**
     * ตอบกลับข้อความ (reply)
     */
    public function replyText(LineAccount $account, string $replyToken, string $text, array $extraPayload = [], ?string $quoteToken = null): array
    {
        $message = array_merge([
            'type' => 'text',
            'text' => $text,
        ], $extraPayload);

        $payload = [
            'replyToken' => $replyToken,
            'messages' => [$message],
        ];

        return $this->sendRequest($account, '/v2/bot/message/reply', $payload, 'replyText');
    }

    /**
     * ดึง profile ของ user จาก LINE
     */
    public function getProfile(LineAccount $account, string $userId): array
    {
        $uri = '/v2/bot/profile/'.$userId;

        try {
            /** @var Response $response */
            $response = $this->http($account)->get($uri);

            $success = $response->successful();
            $status = $response->status();
            $body = $response->json();

            if (! $success) {
                $errorBody = $response->body();

                Log::channel('line_oa')->warning('[LineMessagingClient] getProfile failed', [
                    'account_id' => $account->id,
                    'uri' => $uri,
                    'status' => $status,
                    'response' => $errorBody,
                ]);

                return [
                    'success' => false,
                    'status' => $status,
                    'body' => $body ?? $errorBody,
                    'error' => is_string($errorBody) ? $errorBody : json_encode($errorBody),
                ];
            }

            Log::channel('line_oa')->info('[LineMessagingClient] getProfile success', [
                'account_id' => $account->id,
                'uri' => $uri,
                'status' => $status,
            ]);

            return [
                'success' => true,
                'status' => $status,
                'body' => $body,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::channel('line_oa')->error('[LineMessagingClient] getProfile exception', [
                'account_id' => $account->id,
                'uri' => $uri,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ดึง binary ของ message content (image / video / audio) จาก LINE
     *
     * ใช้กับ message ที่ contentProvider.type = "line"
     *
     * @return array{success: bool, status: int|null, body: string|null, error: string|null}
     */
    public function downloadMessageContent(LineAccount $account, string $messageId): array
    {
        $uri = '/v2/bot/message/'.$messageId.'/content';

        try {
            $timeout = (int) (config('line_oa.http_timeout', 5));

            /** @var Response $response */
            $response = Http::withToken($account->access_token)
                ->baseUrl($this->apiUrl)
                ->timeout($timeout)
                ->withHeaders(['Accept' => '*/*']) // สำคัญ: อย่าขอ JSON
                ->get($uri);

            $status = $response->status();
            $body = $response->body(); // binary string

            if (! $response->successful()) {
                Log::channel('line_oa')->warning('[LineMessagingClient] downloadMessageContent failed', [
                    'account_id' => $account->id,
                    'uri' => $uri,
                    'status' => $status,
                    'body' => $body,
                ]);

                return [
                    'success' => false,
                    'status' => $status,
                    'body' => $body,
                    'error' => $body,
                ];
            }

            return [
                'success' => true,
                'status' => $status,
                'body' => $body,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::channel('line_oa')->error('[LineMessagingClient] downloadMessageContent exception', [
                'account_id' => $account->id,
                'uri' => $uri,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * core method สำหรับยิง POST ไปยัง LINE (JSON)
     *
     * @return array{success: bool, status: int|null, body: mixed, error: string|null}
     */
    protected function sendRequest(LineAccount $account, string $uri, array $payload, string $context): array
    {
        try {
            Log::channel('line_oa')->warning('[LineMessagingClient] request blocked: account not active', [
                'account_id' => $account->id,
                'context'    => $context,
                'uri'        => $uri,
                'payload'    => $payload,
                'status'     => $account->status,
            ]);

            if ($account->status !== 'active') {
                Log::channel('line_oa')->warning('[LineMessagingClient] request blocked: account not active', [
                    'account_id' => $account->id,
                    'context'    => $context,
                    'uri'        => $uri,
                    'payload'    => $payload,
                    'status'     => $account->status,
                ]);

                return [
                    'success' => false,
                    'status'  => null,
                    'body'    => null,
                    'error'   => 'LINE account is not active.',
                ];
            }
            /** @var Response $response */
            $response = $this->http($account)->post($uri, $payload);

            $success = $response->successful();
            $status = $response->status();
            $body = $response->json();

            if ($status >= 200 && $status < 300) {
                // เพิ่ม log debug เฉพาะ pushMessages (ชั่วคราว)
                if ($context === 'pushMessages') {
                    Log::channel('line_oa')->info('[LineMessagingClient] pushMessages success', [
                        'account_id' => $account->id,
                        'uri' => $uri,
                        'payload' => $payload,
                        'status' => $status,
                    ]);
                }

                return [
                    'success' => true,
                    'status' => $status,
                    'body' => $body,
                    'error' => null,
                ];
            }

            if (! $success) {
                $errorBody = $response->body();

                Log::channel('line_oa')->warning('[LineMessagingClient] request failed', [
                    'account_id' => $account->id,
                    'context' => $context,
                    'uri' => $uri,
                    'status' => $status,
                    'payload' => $payload,
                    'response' => $errorBody,
                ]);

                return [
                    'success' => false,
                    'status' => $status,
                    'body' => $body ?? $errorBody,
                    'error' => is_string($errorBody) ? $errorBody : json_encode($errorBody),
                ];
            }

            Log::channel('line_oa')->info('[LineMessagingClient] request success', [
                'account_id' => $account->id,
                'context' => $context,
                'uri' => $uri,
                'status' => $status,
            ]);

            return [
                'success' => true,
                'status' => $status,
                'body' => $body,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::channel('line_oa')->error('[LineMessagingClient] exception', [
                'account_id' => $account->id,
                'context' => $context,
                'uri' => $uri,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function markMessagesAsRead(LineAccount $account, string $markAsReadToken): array
    {
        $payload = [
            'markAsReadToken' => $markAsReadToken,
        ];

        return $this->sendRequest($account, '/v2/bot/chat/markAsRead', $payload, 'markMessagesAsRead');
    }
}
