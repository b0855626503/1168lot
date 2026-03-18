<?php

namespace Gametech\Sms\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Sms\Models\SmsCampaign;
use Gametech\Sms\Models\SmsImportBatch;
use Gametech\Sms\Services\Recipients\SmsRecipientBuilderService;
use Illuminate\Http\Request;

class SmsCampaignRecipientsController extends AppBaseController
{
    public function build(Request $request, SmsRecipientBuilderService $builder)
    {
        $request->validate([
            'campaign_id' => 'required|integer|exists:sms_campaigns,id',
            'mode' => 'required|string|in:member_all,upload_only,mixed',
            'import_batch_id' => 'nullable|integer|exists:sms_import_batches,id',
        ]);

        $campaign = SmsCampaign::findOrFail((int) $request->input('campaign_id'));
        $mode = $request->input('mode');

        $result = [];

        if ($mode === 'member_all' || $mode === 'mixed') {
            $result['members'] = $builder->buildFromMembers($campaign);
        }

        if ($mode === 'upload_only' || $mode === 'mixed') {
            $batchId = (int) $request->input('import_batch_id');
            $batch = SmsImportBatch::findOrFail($batchId);

            // phones ถูกเก็บไว้ใน meta.preview/phones ชั่วคราว
            $phones = (array) ($batch->meta['phones'] ?? []);
            $result['upload'] = $builder->buildFromImportBatch(
                $campaign,
                $batch,
                $phones,
                (string) ($batch->country_code ?: '66')
            );
        }

        return response()->json([
            'success' => true,
            'result' => $result,
            'campaign_total_recipients' => $campaign->fresh()->total_recipients,
        ]);
    }
}
