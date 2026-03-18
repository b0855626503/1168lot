<?php

namespace Gametech\LineOA\Services;

use Gametech\LineOA\Models\LineAccount;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineMessagingClientBk
{
    /**
     * base URL ของ LINE Messaging API
     */
    protected string $baseUrl = 'https://api.line.me';

    /**
     * คืน Http client ที่ config token / baseUrl ให้เรียบร้อยแล้ว
     */
    function http(LineAccount $account)
    {
        $timeout = (int) (config('line_oa.http_timeout', 5));

        return Http::withToken($account->access_token)
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($timeout);
    }

    /**
     * ส่งข้อความ text แบบ push (ใช้เวลาแอดมินตอบลูกค้า / บอทแจ้งเอง)
     *
     * @param  LineAccount  $account  OA ที่จะใช้ยิงข้อความ
     * @param  string       $toUserId line_user_id ของลูกค้า (จาก LineContact)
     * @param  string       $text     ข้อความ
     * @param  array        $extraPayload สำหรับใส่ field เพิ่มใน message (เช่น quickReply ฯลฯ)
     * @return array{success: bool, status: int|null, body: mixed, error: string|null}
     */
    function pushText(LineAccount $account, string $toUserId, string $text, array $extraPayload = []): array
    {
        $message = array_merge([
            'type' => 'text',
            'text' => $text,
        ], $extraPayload);

        $payload = [
            'to'       => $toUserId,
            'messages' => [$message],
        ];

        return $this->sendRequest($account, '/v2/bot/message/push', $payload, 'pushText');
    }

    public function sendImageMessage(LineAccount $account, string $lineUserId, string $originalUrl, string $previewUrl)
    {
        $body = [
            'to'       => $lineUserId,
            'messages' => [[
                'type'               => 'image',
                'originalContentUrl' => $originalUrl,
                'previewImageUrl'    => $previewUrl,
            ]],
        ];

        return $this->request($account, 'POST', '/v2/bot/message/push', $body);
    }

    /**
     * ส่งข้อความหลายอันแบบ push
     *
     * @param  LineAccount $account
     * @param  string      $toUserId
     * @param  array       $messages
     * @return array{success: bool, status: int|null, body: mixed, error: string|null}
     */
    function pushMessages(LineAccount $account, string $toUserId, array $messages): array
    {
        $payload = [
            'to'       => $toUserId,
            'messages' => $messages,
        ];

        return $this->sendRequest($account, '/v2/bot/message/push', $payload, 'pushMessages');
    }

    /**
     * ตอบกลับข้อความ (reply)
     *
     * @param  LineAccount $account
     * @param  string      $replyToken
     * @param  string      $text
     * @param  array       $extraPayload
     * @return array{success: bool, status: int|null, body: mixed, error: string|null}
     */
    function replyText(LineAccount $account, string $replyToken, string $text, array $extraPayload = []): array
    {
        $message = array_merge([
            'type' => 'text',
            'text' => $text,
        ], $extraPayload);

        $payload = [
            'replyToken' => $replyToken,
            'messages'   => [$message],
        ];

        return $this->sendRequest($account, '/v2/bot/message/reply', $payload, 'replyText');
    }

    /**
     * ดึง profile ของ user จาก LINE (displayName / pictureUrl / statusMessage)
     */
    public function getProfile(LineAccount $account, string $userId): array
    {
        $uri = '/v2/bot/profile/' . $userId;

        try {
            /** @var Response $response */
            $response = $this->http($account)->get($uri);

            $success = $response->successful();
            $status  = $response->status();
            $body    = $response->json();

            if (! $success) {
                $errorBody = $response->body();

                Log::channel('line_oa')->warning('[LineMessagingClient] getProfile failed', [
                    'account_id' => $account->id,
                    'uri'        => $uri,
                    'status'     => $status,
                    'response'   => $errorBody,
                ]);

                return [
                    'success' => false,
                    'status'  => $status,
                    'body'    => $body ?? $errorBody,
                    'error'   => is_string($errorBody) ? $errorBody : json_encode($errorBody),
                ];
            }

            Log::channel('line_oa')->info('[LineMessagingClient] getProfile success', [
                'account_id' => $account->id,
                'uri'        => $uri,
                'status'     => $status,
            ]);

            return [
                'success' => true,
                'status'  => $status,
                'body'    => $body,
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::channel('line_oa')->error('[LineMessagingClient] getProfile exception', [
                'account_id' => $account->id,
                'uri'        => $uri,
                'error'      => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status'  => null,
                'body'    => null,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * core method สำหรับยิง POST ไปยัง LINE
     *
     * @return array{success: bool, status: int|null, body: mixed, error: string|null}
     */
    protected function sendRequest(LineAccount $account, string $uri, array $payload, string $context): array
    {
        try {
            /** @var Response $response */
            $response = $this->http($account)->post($uri, $payload);

            $success = $response->successful();
            $status  = $response->status();
            $body    = $response->json();

            if (! $success) {
                $errorBody = $response->body();

                Log::channel('line_oa')->warning('[LineMessagingClient] request failed', [
                    'account_id' => $account->id,
                    'context'    => $context,
                    'uri'        => $uri,
                    'status'     => $status,
                    'payload'    => $payload,
                    'response'   => $errorBody,
                ]);

                return [
                    'success' => false,
                    'status'  => $status,
                    'body'    => $body ?? $errorBody,
                    'error'   => is_string($errorBody) ? $errorBody : json_encode($errorBody),
                ];
            }

            Log::channel('line_oa')->info('[LineMessagingClient] request success', [
                'account_id' => $account->id,
                'context'    => $context,
                'uri'        => $uri,
                'status'     => $status,
            ]);

            return [
                'success' => true,
                'status'  => $status,
                'body'    => $body,
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::channel('line_oa')->error('[LineMessagingClient] exception', [
                'account_id' => $account->id,
                'context'    => $context,
                'uri'        => $uri,
                'payload'    => $payload,
                'error'      => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status'  => null,
                'body'    => null,
                'error'   => $e->getMessage(),
            ];
        }
    }
}
