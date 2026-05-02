<?php

namespace Gametech\Marketing\Services;

class MarketingClickClassifier
{
    private const PREVIEW_BOT_PATTERNS = [
        'facebookexternalhit' => 'facebookexternalhit',
        'telegrambot' => 'telegrambot',
        'line-poker' => 'line_preview',
        'line/' => 'line_preview',
        'twitterbot' => 'twitterbot',
        'linkedinbot' => 'linkedinbot',
        'slackbot' => 'slackbot',
        'whatsapp' => 'whatsapp_preview',
        'discordbot' => 'discordbot',
    ];

    private const BOT_KEYWORDS = [
        'googlebot', 'bingbot', 'yandexbot', 'baiduspider',
        'duckduckbot', 'slurp', 'msnbot', 'ahrefsbot',
        'semrushbot', 'dotbot', 'rogerbot', 'exabot',
        'ia_archiver', 'archive.org_bot', 'crawler', 'spider',
        'scraper', 'wget/', 'curl/', 'python-requests', 'go-http-client',
        'java/', 'libwww-perl', 'okhttp', 'zgrab',
    ];

    /**
     * @return array{
     *     classification_type: string,
     *     classification_reason: string,
     *     risk_score: int,
     *     is_bot: bool,
     *     is_preview_bot: bool,
     *     is_suspicious: bool,
     * }
     */
    public function classify(string $userAgent, string $ipHash, ?string $method = null): array
    {
        $ua = strtolower(trim($userAgent));

        if ($ua === '') {
            return $this->result('suspicious', 'empty_user_agent', 70, false, false, true);
        }

        foreach (self::PREVIEW_BOT_PATTERNS as $pattern => $reason) {
            if (str_contains($ua, $pattern)) {
                return $this->result('preview_bot', $reason, 30, true, true, false);
            }
        }

        foreach (self::BOT_KEYWORDS as $keyword) {
            if (str_contains($ua, $keyword)) {
                return $this->result('bot', 'crawler_keyword', 50, true, false, false);
            }
        }

        if ($method !== null && ! in_array(strtoupper($method), ['GET', 'POST', 'HEAD'], true)) {
            return $this->result('suspicious', 'invalid_method', 60, false, false, true);
        }

        return $this->result('unknown', 'unknown', 10, false, false, false);
    }

    public function confirmAsHuman(string $currentType): string
    {
        if ($currentType === 'unknown') {
            return 'human';
        }

        return $currentType;
    }

    /**
     * @return array{
     *     classification_type: string,
     *     classification_reason: string,
     *     risk_score: int,
     *     is_bot: bool,
     *     is_preview_bot: bool,
     *     is_suspicious: bool,
     * }
     */
    private function result(
        string $type,
        string $reason,
        int $riskScore,
        bool $isBot,
        bool $isPreviewBot,
        bool $isSuspicious
    ): array {
        return [
            'classification_type' => $type,
            'classification_reason' => $reason,
            'risk_score' => $riskScore,
            'is_bot' => $isBot,
            'is_preview_bot' => $isPreviewBot,
            'is_suspicious' => $isSuspicious,
        ];
    }
}
