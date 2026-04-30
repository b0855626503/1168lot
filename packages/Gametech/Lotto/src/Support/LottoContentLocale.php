<?php

namespace Gametech\Lotto\Support;

final class LottoContentLocale
{
    /**
     * @return array<int, string>
     */
    public static function supported(): array
    {
        return ['th', 'en', 'lo', 'km'];
    }

    public static function normalize(?string $locale): string
    {
        $normalized = strtolower(trim((string) $locale));

        $mapped = match ($normalized) {
            'la', 'laos' => 'lo',
            'kh', 'khmer' => 'km',
            default => $normalized,
        };

        if (! in_array($mapped, self::supported(), true)) {
            return 'th';
        }

        return $mapped;
    }
}
