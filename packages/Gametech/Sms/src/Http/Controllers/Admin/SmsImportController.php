<?php

namespace Gametech\Sms\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Sms\Models\SmsImportBatch;
use Gametech\Sms\Services\Import\SmsImportParser;
use Illuminate\Http\Request;

class SmsImportController extends AppBaseController
{
    public function parse(Request $request, SmsImportParser $parser)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB ปรับได้
            'campaign_id' => 'nullable|integer',
            'country_code' => 'nullable|string|max:8',
            'has_header' => 'nullable|boolean',
            'phone_column' => 'nullable|string|max:50',
            'source_label' => 'nullable|string|max:120',
            'consent_basis' => 'nullable|string|max:50',
            'consent_note' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');

        $result = $parser->parse($file, [
            'country_code' => $request->input('country_code', '66'),
            'has_header' => (bool) $request->input('has_header', true),
            'phone_column' => $request->input('phone_column'),
        ]);

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'PARSE_FAILED',
                'hint' => $result['hint'] ?? null,
            ], 422);
        }

        // สร้าง import batch record
        $batch = SmsImportBatch::create([
            'team_id' => null,
            'campaign_id' => $request->input('campaign_id'),
            'file_name' => $file->getClientOriginalName(),
            'file_mime' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'file_sha1' => sha1_file($file->getRealPath()) ?: null,

            'source_label' => $request->input('source_label'),
            'phone_column' => $result['resolved_phone_column'] ?? $request->input('phone_column'),
            'country_code' => $request->input('country_code', '66'),
            'has_header' => (bool) $request->input('has_header', true),

            'consent_basis' => $request->input('consent_basis'),
            'consent_note' => $request->input('consent_note'),

            'total_rows' => (int) ($result['total_rows'] ?? 0),
            'valid_phones' => (int) ($result['valid_phones'] ?? 0),
            'invalid_phones' => (int) ($result['invalid_phones'] ?? 0),
            'duplicate_phones' => (int) ($result['duplicate_phones'] ?? 0),

            'status' => 'uploaded',
            'meta' => [
                'preview' => $result['preview'] ?? [],
                'phones' => $result['phones'] ?? [], // เก็บชั่วคราวใน meta ได้ แต่ถ้าไฟล์ใหญ่ “ไม่ควร”
                'ext' => $result['ext'] ?? null,
            ],
        ]);

        return response()->json([
            'success' => true,
            'batch_id' => $batch->id,
            'preview' => $result['preview'] ?? [],
            'counters' => [
                'total_rows' => $batch->total_rows,
                'valid_phones' => $batch->valid_phones,
                'invalid_phones' => $batch->invalid_phones,
                'duplicate_phones' => $batch->duplicate_phones,
            ],
        ]);
    }
}
