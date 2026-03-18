<?php

namespace Gametech\Sms\Services\Import;

use Gametech\Sms\Support\PhoneNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SmsImportParser
{
    /**
     * Parse upload file and return:
     * - phones (normalized list, unique)
     * - counters
     * - preview rows
     */
    public function parse(UploadedFile $file, array $options = []): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $countryCode = (string) ($options['country_code'] ?? '66');
        $hasHeader = (bool) ($options['has_header'] ?? true);
        $phoneColumn = $options['phone_column'] ?? null;
        $maxPreview = (int) config('sms.import.max_preview_rows', 20);

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->parseCsv($file->getRealPath(), $countryCode, $hasHeader, $phoneColumn, $maxPreview);
        }

        if (in_array($ext, ['xls', 'xlsx'], true)) {
            return $this->parseExcel($file->getRealPath(), $countryCode, $hasHeader, $phoneColumn, $maxPreview);
        }

        return [
            'ok' => false,
            'error' => 'UNSUPPORTED_FILE_TYPE',
            'ext' => $ext,
        ];
    }

    private function parseCsv(string $path, string $countryCode, bool $hasHeader, ?string $phoneColumn, int $maxPreview): array
    {
        $fh = new \SplFileObject($path, 'r');
        $fh->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $fh->setCsvControl(',');

        $header = null;
        $phones = [];
        $seen = [];
        $preview = [];

        $totalRows = 0;
        $valid = 0;
        $invalid = 0;
        $duplicate = 0;

        foreach ($fh as $row) {
            if (!is_array($row) || count($row) === 1 && $row[0] === null) {
                continue;
            }

            $totalRows++;

            if ($totalRows === 1 && $hasHeader) {
                $header = array_map(fn($v) => trim((string)$v), $row);
                continue;
            }

            $rawPhone = $this->extractPhoneFromRow($row, $header, $phoneColumn);
            $e164 = PhoneNormalizer::toE164($rawPhone, $countryCode);

            if ($e164 === null) {
                $invalid++;
            } else {
                if (isset($seen[$e164])) {
                    $duplicate++;
                } else {
                    $seen[$e164] = true;
                    $phones[] = $e164;
                    $valid++;
                }
            }

            if (count($preview) < $maxPreview) {
                $preview[] = [
                    'raw' => $rawPhone,
                    'e164' => $e164,
                ];
            }
        }

        return [
            'ok' => true,
            'ext' => 'csv',
            'total_rows' => $totalRows,
            'valid_phones' => $valid,
            'invalid_phones' => $invalid,
            'duplicate_phones' => $duplicate,
            'phones' => $phones,
            'preview' => $preview,
            'resolved_phone_column' => $phoneColumn,
        ];
    }

    private function parseExcel(string $path, string $countryCode, bool $hasHeader, ?string $phoneColumn, int $maxPreview): array
    {
        // ไม่เพิ่ม dependency แบบเงียบ ๆ:
        // ถ้าโปรเจกต์ยังไม่ได้ติดตั้ง phpoffice/phpspreadsheet จะ fail อย่างมีข้อความชัดเจน
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return [
                'ok' => false,
                'error' => 'PHPSPREADSHEET_NOT_INSTALLED',
                'hint' => 'ติดตั้ง phpoffice/phpspreadsheet หรือแปลงไฟล์เป็น CSV',
            ];
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true); // keyed by column letters

        $phones = [];
        $seen = [];
        $preview = [];

        $totalRows = 0;
        $valid = 0;
        $invalid = 0;
        $duplicate = 0;

        $header = null;

        foreach ($rows as $idx => $rowAssoc) {
            $totalRows++;

            $values = array_values($rowAssoc);

            if ($totalRows === 1 && $hasHeader) {
                $header = array_map(fn($v) => trim((string)$v), $values);
                continue;
            }

            $rawPhone = $this->extractPhoneFromRow($values, $header, $phoneColumn);
            $e164 = PhoneNormalizer::toE164($rawPhone, $countryCode);

            if ($e164 === null) {
                $invalid++;
            } else {
                if (isset($seen[$e164])) {
                    $duplicate++;
                } else {
                    $seen[$e164] = true;
                    $phones[] = $e164;
                    $valid++;
                }
            }

            if (count($preview) < $maxPreview) {
                $preview[] = [
                    'raw' => $rawPhone,
                    'e164' => $e164,
                ];
            }
        }

        return [
            'ok' => true,
            'ext' => 'xlsx',
            'total_rows' => $totalRows,
            'valid_phones' => $valid,
            'invalid_phones' => $invalid,
            'duplicate_phones' => $duplicate,
            'phones' => $phones,
            'preview' => $preview,
            'resolved_phone_column' => $phoneColumn,
        ];
    }

    private function extractPhoneFromRow(array $row, ?array $header, ?string $phoneColumn): ?string
    {
        // 1) ถ้าระบุชื่อคอลัมน์มา และมี header ให้ map
        if ($phoneColumn && $header) {
            $idx = $this->findHeaderIndex($header, $phoneColumn);
            if ($idx !== null) {
                return $this->scalar($row[$idx] ?? null);
            }
        }

        // 2) ถ้า header มี ให้เดาจาก candidates
        if ($header) {
            $candidates = (array) config('sms.import.phone_column_candidates', []);
            foreach ($candidates as $cand) {
                $idx = $this->findHeaderIndex($header, (string)$cand);
                if ($idx !== null) {
                    return $this->scalar($row[$idx] ?? null);
                }
            }
        }

        // 3) fallback: ใช้คอลัมน์แรก
        return $this->scalar($row[0] ?? null);
    }

    private function findHeaderIndex(array $header, string $needle): ?int
    {
        $needle = strtolower(trim($needle));
        foreach ($header as $i => $h) {
            if (strtolower(trim((string)$h)) === $needle) {
                return $i;
            }
        }
        return null;
    }

    private function scalar($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : $s;
    }
}
