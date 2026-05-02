<?php

namespace Tests\Unit\Marketing;

use Gametech\Marketing\Services\MarketingClickClassifier;
use PHPUnit\Framework\TestCase;

class MarketingClickClassifierTest extends TestCase
{
    private MarketingClickClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new MarketingClickClassifier;
    }

    public function test_empty_user_agent_is_suspicious(): void
    {
        $result = $this->classifier->classify('', 'abc123');

        $this->assertSame('suspicious', $result['classification_type']);
        $this->assertSame('empty_user_agent', $result['classification_reason']);
        $this->assertFalse($result['is_bot']);
        $this->assertFalse($result['is_preview_bot']);
        $this->assertTrue($result['is_suspicious']);
        $this->assertGreaterThan(50, $result['risk_score']);
    }

    public function test_facebook_external_hit_is_preview_bot(): void
    {
        $result = $this->classifier->classify('facebookexternalhit/1.1', 'abc');

        $this->assertSame('preview_bot', $result['classification_type']);
        $this->assertTrue($result['is_bot']);
        $this->assertTrue($result['is_preview_bot']);
        $this->assertFalse($result['is_suspicious']);
    }

    public function test_telegram_bot_is_preview_bot(): void
    {
        $result = $this->classifier->classify('TelegramBot (like TwitterBot) foo', 'abc');

        $this->assertSame('preview_bot', $result['classification_type']);
        $this->assertTrue($result['is_preview_bot']);
    }

    public function test_line_preview_is_preview_bot(): void
    {
        $result = $this->classifier->classify('Mozilla/5.0 (compatible; Line/; +https://line.me/en/', 'abc');

        $this->assertSame('preview_bot', $result['classification_type']);
        $this->assertTrue($result['is_preview_bot']);
    }

    public function test_googlebot_is_bot(): void
    {
        $result = $this->classifier->classify('Googlebot/2.1 (+http://www.google.com/bot.html)', 'abc');

        $this->assertSame('bot', $result['classification_type']);
        $this->assertSame('crawler_keyword', $result['classification_reason']);
        $this->assertTrue($result['is_bot']);
        $this->assertFalse($result['is_preview_bot']);
        $this->assertFalse($result['is_suspicious']);
    }

    public function test_generic_crawler_keyword_is_bot(): void
    {
        $result = $this->classifier->classify('python-requests/2.28', 'abc');

        $this->assertSame('bot', $result['classification_type']);
    }

    public function test_unknown_ua_without_javascript_is_unknown(): void
    {
        $result = $this->classifier->classify('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'abc');

        $this->assertSame('unknown', $result['classification_type']);
        $this->assertFalse($result['is_bot']);
        $this->assertFalse($result['is_preview_bot']);
        $this->assertFalse($result['is_suspicious']);
    }

    public function test_invalid_method_is_suspicious(): void
    {
        $result = $this->classifier->classify('Mozilla/5.0 Chrome', 'abc', 'DELETE');

        $this->assertSame('suspicious', $result['classification_type']);
        $this->assertSame('invalid_method', $result['classification_reason']);
        $this->assertTrue($result['is_suspicious']);
    }

    public function test_valid_get_method_is_not_suspicious(): void
    {
        $result = $this->classifier->classify('Mozilla/5.0 Chrome', 'abc', 'GET');

        $this->assertNotSame('suspicious', $result['classification_type']);
    }

    public function test_confirm_as_human_upgrades_unknown(): void
    {
        $this->assertSame('human', $this->classifier->confirmAsHuman('unknown'));
    }

    public function test_confirm_as_human_preserves_non_unknown(): void
    {
        $this->assertSame('bot', $this->classifier->confirmAsHuman('bot'));
        $this->assertSame('preview_bot', $this->classifier->confirmAsHuman('preview_bot'));
        $this->assertSame('suspicious', $this->classifier->confirmAsHuman('suspicious'));
        $this->assertSame('human', $this->classifier->confirmAsHuman('human'));
    }

    public function test_result_has_all_required_keys(): void
    {
        $result = $this->classifier->classify('Mozilla/5.0', 'abc');

        $this->assertArrayHasKey('classification_type', $result);
        $this->assertArrayHasKey('classification_reason', $result);
        $this->assertArrayHasKey('risk_score', $result);
        $this->assertArrayHasKey('is_bot', $result);
        $this->assertArrayHasKey('is_preview_bot', $result);
        $this->assertArrayHasKey('is_suspicious', $result);
    }

    public function test_risk_score_is_between_0_and_100(): void
    {
        $agents = [
            '',
            'Mozilla/5.0',
            'Googlebot/2.1',
            'facebookexternalhit',
            'TelegramBot',
        ];

        foreach ($agents as $ua) {
            $result = $this->classifier->classify($ua, 'hash');
            $this->assertGreaterThanOrEqual(0, $result['risk_score'], "risk_score below 0 for ua: {$ua}");
            $this->assertLessThanOrEqual(100, $result['risk_score'], "risk_score above 100 for ua: {$ua}");
        }
    }
}
