<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGeneratorV3;

final class ApiDocAnalyzer
{
    public function analyze(string $doc): array
    {
        $text = $this->normalizeText($doc);
        $lower = mb_strtolower($text);

        $endpoints = $this->extractEndpoints($text);

        $capabilities = [
            'deposit' => $this->containsAny($lower, ['deposit', 'payin', 'payment create', 'create payment', 'checkout', 'qr']),
            'withdraw' => $this->containsAny($lower, ['withdraw', 'payout', 'transfer out', 'cashout', 'disbursement']),
            'callback' => $this->containsAny($lower, ['callback', 'webhook', 'ipn', 'notify url', 'notification url']),
            'balance' => $this->containsAny($lower, ['balance', 'merchant balance', 'wallet balance', 'account balance']),
            'customer' => $this->containsAny($lower, ['customer', 'member', 'client profile']),
            'customer_account' => $this->containsAny($lower, ['customer account', 'bank account', 'account_identifier', 'account number']),
            'status_query' => $this->containsAny($lower, ['check status', 'query status', 'transaction status', 'get transaction']),
            'limit' => $this->containsAny($lower, ['limit', 'min deposit', 'max deposit', 'min withdraw', 'max withdraw']),
        ];

        $auth = [
            'api_key' => $this->containsAny($lower, ['api key', 'x-api-key', 'apikey', 'merchant key']),
            'bearer' => $this->containsAny($lower, ['bearer']),
            'hmac' => $this->containsAny($lower, ['hmac', 'signature', 'sha256', 'sha-256']),
            'rsa' => $this->containsAny($lower, ['rsa', 'private key', 'public key']),
            'basic' => $this->containsAny($lower, ['basic auth']),
            'unknown' => false,
        ];

        $auth['unknown'] = !($auth['api_key'] || $auth['bearer'] || $auth['hmac'] || $auth['rsa'] || $auth['basic']);

        return [
            'capabilities' => $capabilities,
            'auth' => $auth,
            'endpoints' => $endpoints,
            'status_candidates' => $this->extractStatusCandidates($lower),
            'notes' => [
                'parser' => 'heuristic_v3',
                'requires_human_review' => $auth['unknown'] || empty($endpoints),
            ],
        ];
    }

    private function normalizeText(string $doc): string
    {
        $doc = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $doc) ?? $doc;
        $doc = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $doc) ?? $doc;
        $doc = strip_tags($doc);
        $doc = html_entity_decode($doc, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/[ \t]+/', ' ', $doc) ?? $doc;
    }

    private function extractEndpoints(string $text): array
    {
        $endpoints = [];

        preg_match_all('/\b(GET|POST|PUT|PATCH|DELETE)\s+(https?:\/\/[^\s]+|\/[a-zA-Z0-9_\-\/\{\}:?=&.\[\]]+)/i', $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $endpoints[] = [
                'method' => strtoupper($match[1]),
                'path' => $match[2],
            ];
        }

        return array_values(array_unique($endpoints, SORT_REGULAR));
    }

    private function extractStatusCandidates(string $lower): array
    {
        $known = [
            'pending',
            'processing',
            'success',
            'paid',
            'completed',
            'failed',
            'rejected',
            'expired',
            'refunded',
            'cancelled',
            'canceled',
            'approved',
        ];

        $found = [];
        foreach ($known as $status) {
            if (str_contains($lower, $status)) {
                $found[] = $status;
            }
        }

        return $found;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}
