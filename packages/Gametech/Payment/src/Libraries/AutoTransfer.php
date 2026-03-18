<?php

namespace Gametech\Payment\Libraries;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AutoTransfer
{
    protected function http(): PendingRequest
    {
        $timeout = (int) config('autotransfer.timeout', 20);

//        Log::channel('autotransfer_api')->warning(
//            'PaymentOutAutoTransfer: withdraw header POST',
//            [
//                'source-system-name'      => config('autotransfer.source_system_name', ''),
//                'apikey'      => config('autotransfer.api_key', ''),
//            ]
//        );
        return Http::timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'source-system-name' => (string) config('autotransfer.source_system_name', ''),
                'apikey' => (string) config('autotransfer.api_key', ''),
            ]);
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('autotransfer.endpoint_url', ''), '/');
    }

    /**
     * [GET] /get_account_balances
     */
    public function getAccountBalances(): array
    {
        $url = $this->baseUrl() . '/get_account_balances';

        try {
            $res = $this->http()->get($url);

            if ($res->successful()) {
                return [
                    'success' => true,
                    'code' => $res->status(),
                    'msg' => 'success',
                    'data' => $res->json(),
                ];
            }

            return [
                'success' => false,
                'code' => $res->status(),
                'msg' => $res->body(),
                'data' => $res->json(),
            ];
        } catch (\Throwable $e) {
            Log::channel('autotransfer_api')->error('AutoTransfer getAccountBalances error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'code' => 0,
                'msg' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * [POST] /withdraw/transfer
     * NOTE: partner must pass callback_url (our endpoint) to receive final result.
     */
    public function withdrawTransfer(array $payload): array
    {
        $url = $this->baseUrl() . '/withdraw/transfer';

        try {
            $res = $this->http()->post($url, $payload);

            Log::channel('autotransfer_api')->warning(
                'PaymentOutAutoTransfer: withdraw payload POST',
                [
                    'source-system-name'      => config('autotransfer.source_system_name', ''),
                    'apikey'      => config('autotransfer.api_key', ''),
                    'url'      => $url,
                    'payload'      => $payload,
                    'res'      => $res,
                ]
            );

            if ($res->successful() || $res->status() === 202) {
                return [
                    'success' => true,
                    'code' => $res->status(),
                    'msg' => 'success',
                    'data' => $res->json(),
                ];
            }

            return [
                'success' => false,
                'code' => $res->status(),
                'msg' => $res->body(),
                'data' => $res->json(),
            ];
        } catch (\Throwable $e) {
            Log::channel('autotransfer_api')->error('AutoTransfer withdrawTransfer error', [
                'url' => $url,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'code' => 0,
                'msg' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * [POST] /deposit/send_slip/qr_code
     */
    public function sendSlipQrCode(array $payload): array
    {
        $url = $this->baseUrl() . '/deposit/send_slip/qr_code';

        try {
            $res = $this->http()->post($url, $payload);

            return [
                'success' => $res->successful(),
                'code' => $res->status(),
                'msg' => $res->successful() ? 'success' : $res->body(),
                'data' => $res->json(),
            ];
        } catch (\Throwable $e) {
            Log::channel('autotransfer_api')->error('AutoTransfer sendSlipQrCode error', [
                'url' => $url,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'code' => 0,
                'msg' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * [POST] /deposit/send_slip/qr_base64
     */
    public function sendSlipQrBase64(array $payload): array
    {
        $url = $this->baseUrl() . '/deposit/send_slip/qr_base64';

        try {
            $res = $this->http()->post($url, $payload);

            return [
                'success' => $res->successful(),
                'code' => $res->status(),
                'msg' => $res->successful() ? 'success' : $res->body(),
                'data' => $res->json(),
            ];
        } catch (\Throwable $e) {
            Log::channel('autotransfer_api')->error('AutoTransfer sendSlipQrBase64 error', [
                'url' => $url,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'code' => 0,
                'msg' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * [POST] /deposit/send_slip/qr_image (multipart)
     * $filePath is a local path to the image file (png/jpg)
     */
    public function sendSlipQrImage(string $filePath, array $form = []): array
    {
        $url = $this->baseUrl() . '/deposit/send_slip/qr_image';

        try {
            $req = Http::timeout((int) config('autotransfer.timeout', 20))
                ->acceptJson()
                ->withHeaders([
                    'source-system-name' => (string) config('autotransfer.source_system_name', ''),
                    'apikey' => (string) config('autotransfer.api_key', ''),
                ]);

            $res = $req->attach('file', file_get_contents($filePath), basename($filePath))
                ->post($url, $form);

            return [
                'success' => $res->successful(),
                'code' => $res->status(),
                'msg' => $res->successful() ? 'success' : $res->body(),
                'data' => $res->json(),
            ];
        } catch (\Throwable $e) {
            Log::channel('autotransfer_api')->error('AutoTransfer sendSlipQrImage error', [
                'url' => $url,
                'file' => $filePath,
                'form' => $form,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'code' => 0,
                'msg' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    public function Banks($bankcode)
    {

        switch ($bankcode) {
            case '1':
                $result = 'BBL';
                break;
            case '2':
                $result = 'KBANK';
                break;
            case '3':
                $result = 'KTB';
                break;
            case '4':
                $result = 'SCB';
                break;
            case '5':
                $result = 'GHB';
                break;
            case '6':
                $result = 'KKP';
                break;
            case '7':
                $result = 'CIMB';
                break;
            case '8':
                $result = 'ISBT';
                break;
            case '9':
                $result = 'TISCO';
                break;
            case '10':
            case '15':
                $result = 'TTB';
                break;
            case '11':
                $result = 'BAY';
                break;
            case '12':
                $result = 'UOBT';
                break;
            case '13':
                $result = 'LHBANK';
                break;
            case '14':
                $result = 'GSB';
                break;
            case '17':
                $result = 'BAAC';
                break;
            case '19':
                $result = 'TTB';
                break;
            default:
                $result = false;
                break;
        }

        return $result;

    }
}
