<?php

namespace Gametech\Lotto\Services\Relay;

class LotteryRelayTypeRegistry
{
    /**
     * @return array<string,string>
     */
    public function marketCodeToCanonicalType(): array
    {
        return [
            'gsb-lotto' => 'gsb',
            'bacc-lotto' => 'baac',
            'glo-lotto' => 'goverment',
            'hanoi-special' => 'xsthm',
            'hanoi-normal' => 'minhngoc',
            'hanoi-vip' => 'mlnhngo',
            'malasia-lotto' => 'magnum4d',
            'laos-vip' => 'laosvip',
            'nikkei-morning' => 'nikkei-vip-morning',
            'hanoi-asean' => 'hanoiasean',
            'chaina-morning' => 'szse-vip-morning',
            'laos-tv' => 'laotv',
            'hangseng-morning' => 'hsi-vip-morning',
            'hanoi-hd' => 'xosohd',
            'taiwan-vip' => 'twse-vip',
            'hanoi-star' => 'minhngocstar',
            'korea-vip' => 'ktop30-vip',
            'nikkei-afternoon' => 'nikkei-vip-afternoon',
            'laos-hd' => 'laoshd',
            'hanoi-tv' => 'minhngoctv',
            'chaina-afternoon' => 'szse-vip-afternoon',
            'hangseng-afternoon' => 'hsi-vip-afternoon',
            'laos-strar' => 'laostars',
            'hanoi-redcross' => 'xosoredcross',
            'singapore-vip' => 'sgx-vip',
            'hanoi-harmonious' => 'xosounion',
            'hanoi-develop' => 'xosodevelop',
            'laos-harmonious' => 'laounion',
            'laos-asean' => 'laosasean',
            'laos-harmoniousvip' => 'laounionvip',
            'laos-starvip' => 'laostarsvip',
            'england-vip' => 'england-vip',
            'hanoi-extra' => 'xosoextra',
            'germany-vip' => 'germany-vip',
            'laos-redcross' => 'laoredcross',
            'russia-vip' => 'russia-vip',
            'downjone-vip' => 'dowjones-vip',
            'downjone-star' => 'dowjonestar',
            'downjone-midnight' => 'dowjones-midnight',
            'downjone-extra' => 'dowjones-extra',
            'laos-peace' => 'laosantipap',
            'laos-doorwin' => 'laopatuxay',
            'laos-population' => 'laocitizen',
            'nikkei-stockm' => 'nikkei-morning',
            'nikkei-stocka' => 'nikkei-afternoon',
            'egypt-stock' => 'egx30',
            'china-stockm' => 'szse-morning',
            'hangseng-stockm' => 'hsi-morning',
            'taiwan-stock' => 'twse',
            'korea-stock' => 'ktop30',
            'chaina-stocka' => 'szse-afternoon',
            'hangseng-stocka' => 'hsi-afternoon',
            'singapore-stock' => 'sgx',
            'thai-stocke' => 'set',
            'india-stock' => 'bsesn',
            'england-stock' => 'ftse100',
            'germany-stock' => 'gdaxi',
            'russia-stock' => 'moexbc',
            'downjone-stock' => 'dji',
            'laos-phathana' => 'laosdevelops',
        ];
    }

    public function canonicalTypeForMarketCode(string $marketCode): ?string
    {
        $code = strtolower(trim($marketCode));

        return $this->marketCodeToCanonicalType()[$code] ?? null;
    }

    /**
     * @return string[]
     */
    public function marketCodesForCanonicalType(string $canonicalType): array
    {
        $type = strtolower(trim($canonicalType));

        return array_keys(array_filter(
            $this->marketCodeToCanonicalType(),
            static fn (string $mappedType): bool => $mappedType === $type
        ));
    }
}
