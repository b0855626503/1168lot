<?php

namespace Gametech\FrontendApi\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class RegisterBankAccountNameService
{
    /**
     * @var array<string, string>
     */
    private array $bankCodeMap = [
        '1' => 'BBL',
        '2' => 'KBANK',
        '3' => 'KTB',
        '4' => 'SCB',
        '5' => 'GHB',
        '6' => 'KKP',
        '7' => 'CIMB',
        '10' => 'TTB',
        '11' => 'BAY',
        '12' => 'UOB',
        '13' => 'LHB',
        '14' => 'GSB',
        '15' => 'TTB',
        '17' => 'BAAC',
        '19' => 'TTB',
    ];

    public function __construct(private HttpFactory $http) {}

    /**
     * @return array{
     *     account_name:string,
     *     firstname:string,
     *     lastname:string,
     *     bank_shortcode:string
     * }
     */
    public function resolve(int|string $bankCode, string $accountNumber): array
    {
        $bankShortcode = $this->bankShortcode($bankCode);
        if ($bankShortcode === null) {
            throw new RuntimeException('UNSUPPORTED_BANK');
        }

        $endpoint = trim((string) config('services.me2me.account_name_url'));
        $apiKey = trim((string) config('services.me2me.api_key'));
        $timeout = (int) config('services.me2me.timeout', 10);

        if ($endpoint === '' || $apiKey === '') {
            throw new RuntimeException('BANK_NAME_SERVICE_NOT_CONFIGURED');
        }

        $response = $this->http
            ->withHeaders([
                'x-api-key' => $apiKey,
            ])
            ->asJson()
            ->timeout($timeout > 0 ? $timeout : 10)
            ->post($endpoint, [
                'toBankAccNumber' => $accountNumber,
                'toBankAccNameCode' => $bankShortcode,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('BANK_NAME_LOOKUP_FAILED');
        }

        $payload = $response->json();
        if (! data_get($payload, 'status')) {
            throw new RuntimeException('BANK_NAME_LOOKUP_FAILED');
        }

        $accountName = $this->cleanInvisibleAndSpaces((string) data_get($payload, 'data.accountName', ''));
        if ($accountName === '') {
            throw new RuntimeException('BANK_NAME_LOOKUP_FAILED');
        }

        $nameParts = $this->splitNameUniversal($accountName);

        return [
            'account_name' => $accountName,
            'firstname' => (string) ($nameParts['firstname'] ?? ''),
            'lastname' => (string) ($nameParts['lastname'] ?? ''),
            'bank_shortcode' => $bankShortcode,
        ];
    }

    private function bankShortcode(int|string $bankCode): ?string
    {
        return $this->bankCodeMap[(string) $bankCode] ?? null;
    }

    private function cleanInvisibleAndSpaces(string $value): string
    {
        $value = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{200E}\x{200F}\x{2060}\x{00A0}\x{202F}\x{FEFF}]/u', '', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }

    /**
     * @return array{firstname:string, lastname:string}
     */
    private function splitNameUniversal(string $fullName): array
    {
        $fullName = $this->cleanInvisibleAndSpaces($fullName);

        $prefixes = [
            'นาย', 'นางสาว', 'นาง', 'น.ส.', 'น.', 'ดร.', 'ศ.', 'ผศ.', 'รศ.', 'ด.ญ.', 'ด.ช.', 'เด็กชาย.', 'เด็กหญิง.',
            'เด็กชาย', 'เด็กหญิง', 'สาว',
            'Mr.', 'Mrs.', 'Ms.', 'Miss', 'Dr.', 'Prof.', 'Sir', 'Madam', 'MISTER', 'MISS', 'MS', 'MR', 'MRS', 'KHUN',
        ];

        foreach ($prefixes as $prefix) {
            if (mb_stripos($fullName, $prefix) === 0) {
                $fullName = trim(mb_substr($fullName, mb_strlen($prefix)));
                break;
            }
        }

        $parts = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($parts) || $parts === []) {
            return [
                'firstname' => '',
                'lastname' => '',
            ];
        }

        if (count($parts) === 1) {
            return [
                'firstname' => (string) $parts[0],
                'lastname' => '',
            ];
        }

        return [
            'firstname' => (string) array_shift($parts),
            'lastname' => trim(implode(' ', $parts)),
        ];
    }
}
