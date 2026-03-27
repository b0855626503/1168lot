<?php

namespace Gametech\Lotto\Services\AutoResult;

use Gametech\Lotto\Exceptions\TemplateRenderException;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultSource;
use Illuminate\Support\Carbon;

class ResultRequestBuilder
{
    public const MODE_ROUND_DATE = 'ROUND_DATE';
    public const MODE_ROUND_DATE_MINUS_DAYS = 'ROUND_DATE_MINUS_DAYS';
    public const MODE_ROUND_DATE_PLUS_DAYS = 'ROUND_DATE_PLUS_DAYS';
    public const MODE_RESULT_AT_DATE = 'RESULT_AT_DATE';

    /**
     * @return array{lookup_date:string,url:string,method:string,headers:array<string,string>,body:array<string,mixed>|string|null,query:array<string,mixed>}
     */
    public function build(LottoDraw $draw, LottoResultSource $source): array
    {
        $lookupDate = $this->resolveLookupDate($draw, (string) $source->lookup_date_mode, (int) $source->lookup_date_offset_days);

        $context = [
            'lookup_date' => $lookupDate->format('Y-m-d'),
            'lookup_date_compact' => $lookupDate->format('Ymd'),
            'draw_date' => optional($draw->draw_date)->format('Y-m-d'),
            'draw_id' => (string) $draw->id,
            'market_id' => (string) $draw->market_id,
            'result_at' => optional($draw->result_at)->format('Y-m-d H:i:s'),
            'now' => now()->format('Y-m-d H:i:s'),
        ];

        $query = $this->renderTemplate($source->request_query_template_json ?? [], $context);
        $headers = $this->renderTemplate($source->request_headers_json ?? [], $context);
        $body = $this->renderTemplate($source->request_body_template_json ?? null, $context);

        $url = (string) $source->endpoint_url;
        if (is_array($query) && $query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        return [
            'lookup_date' => $lookupDate->format('Y-m-d'),
            'url' => $url,
            'method' => strtoupper((string) $source->http_method),
            'headers' => is_array($headers) ? $headers : [],
            'body' => $body,
            'query' => is_array($query) ? $query : [],
        ];
    }

    private function resolveLookupDate(LottoDraw $draw, string $mode, int $offsetDays): Carbon
    {
        $drawDate = $draw->draw_date ? Carbon::parse((string) $draw->draw_date) : now();
        $resultDate = $draw->result_at ? Carbon::parse((string) $draw->result_at) : $drawDate;

        return match ($mode) {
            self::MODE_ROUND_DATE => $drawDate->copy(),
            self::MODE_ROUND_DATE_MINUS_DAYS => $drawDate->copy()->subDays($offsetDays),
            self::MODE_ROUND_DATE_PLUS_DAYS => $drawDate->copy()->addDays($offsetDays),
            self::MODE_RESULT_AT_DATE => $resultDate->copy(),
            default => throw new TemplateRenderException('lookup_date_mode ไม่รองรับ: ' . $mode),
        };
    }

    /**
     * @param mixed $template
     * @param array<string, string|null> $context
     * @return mixed
     */
    private function renderTemplate($template, array $context)
    {
        if ($template === null) {
            return null;
        }

        if (is_array($template)) {
            $rendered = [];
            foreach ($template as $key => $value) {
                $rendered[$key] = $this->renderTemplate($value, $context);
            }

            return $rendered;
        }

        if (! is_string($template)) {
            return $template;
        }

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $matches) use ($context): string {
            $key = (string) ($matches[1] ?? '');
            if (! array_key_exists($key, $context)) {
                throw new TemplateRenderException('ไม่รู้จัก placeholder: ' . $key);
            }

            $value = $context[$key];
            if ($value === null) {
                throw new TemplateRenderException('placeholder ไม่มีค่า: ' . $key);
            }

            return (string) $value;
        }, $template) ?? $template;
    }
}
