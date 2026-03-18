<?php

namespace Gametech\Sms\Services\Recipients;

use Gametech\Sms\Models\SmsCampaign;
use Gametech\Sms\Models\SmsImportBatch;
use Gametech\Sms\Models\SmsOptOut;
use Gametech\Sms\Models\SmsRecipient;
use Gametech\Sms\Support\PhoneNormalizer;
use Illuminate\Support\Facades\DB;

class SmsRecipientBuilderService
{
    /**
     * สร้าง recipients จากสมาชิก (members)
     * - chunk
     * - normalize
     * - respect consent/opt-out ตาม campaign
     * - insertOrIgnore กันซ้ำด้วย unique (campaign_id, phone_e164)
     */
    public function buildFromMembers(SmsCampaign $campaign, int $chunkSize = 1000): array
    {
        $membersTable = config('sms.members.table', 'members');
        $pk = config('sms.members.pk', 'code');
        $telCol = config('sms.members.tel_column', 'tel');
        $consentCol = config('sms.members.consent_column');
        $consentYes = config('sms.members.consent_yes_value', 'Y');

        $countryCode = '66';

        $total = 0;
        $inserted = 0;
        $invalid = 0;
        $suppressed = 0;

        $query = DB::table($membersTable)->select([$pk, $telCol]);

        if ($campaign->require_consent && $consentCol) {
            $query->addSelect($consentCol)
                ->where($consentCol, '=', $consentYes);
        }

        // NOTE: filter_json คุณจะมาแตกเงื่อนไขจริงทีหลังได้ ตอนนี้เก็บไว้ก่อน
        // ถ้าอยากใส่จริงตอนนี้ก็ได้ แต่จะกระทบ schema/logic หลายจุด

        $query->orderBy($pk);

        $query->chunk($chunkSize, function ($rows) use (
            $campaign, $pk, $telCol, $countryCode,
            &$total, &$inserted, &$invalid, &$suppressed
        ) {
            $total += count($rows);

            // normalize + map
            $items = [];
            $phones = [];

            foreach ($rows as $r) {
                $raw = (string) ($r->{$telCol} ?? '');
                $e164 = PhoneNormalizer::toE164($raw, $countryCode);

                if (!$e164) {
                    $invalid++;
                    continue;
                }

                $phones[] = $e164;

                $items[] = [
                    'team_id' => $campaign->team_id,
                    'campaign_id' => $campaign->id,
                    'import_batch_id' => null,
                    'source_type' => 'member',
                    'source_id' => (int) $r->{$pk},
                    'phone_e164' => $e164,
                    'phone_raw' => $raw,
                    'country_code' => $countryCode,
                    'has_consent' => $campaign->require_consent ? true : null,
                    'is_opted_out' => false, // จะอัปเดตเป็น true หากอยู่ใน opt-out
                    'status' => 'queued',
                    'provider' => $campaign->provider ?: 'vonage',
                    'attempts' => 0,
                    'queued_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!$items) {
                return;
            }

            // suppression/opt-out
            $opted = [];
            if ($campaign->respect_opt_out && $phones) {
                $opted = DB::table('sms_opt_outs')
                    ->whereIn('phone_e164', array_values(array_unique($phones)))
                    ->pluck('phone_e164')
                    ->all();
            }

            if ($opted) {
                $optMap = array_fill_keys($opted, true);

                $filtered = [];
                foreach ($items as $it) {
                    if (isset($optMap[$it['phone_e164']])) {
                        $suppressed++;
                        continue;
                    }
                    $filtered[] = $it;
                }
                $items = $filtered;
            }

            if (!$items) {
                return;
            }

            // bulk insert (กันซ้ำด้วย unique campaign_id+phone_e164)
            $before = DB::table('sms_recipients')->where('campaign_id', $campaign->id)->count();
            DB::table('sms_recipients')->insertOrIgnore($items);
            $after = DB::table('sms_recipients')->where('campaign_id', $campaign->id)->count();

            $inserted += max(0, $after - $before);
        });

        // sync counters ไป campaign (แบบง่ายก่อน)
        $campaign->total_recipients = (int) DB::table('sms_recipients')->where('campaign_id', $campaign->id)->count();
        $campaign->invalid_count = $campaign->invalid_count + $invalid;
        $campaign->suppressed_count = $campaign->suppressed_count + $suppressed;
        $campaign->save();

        return compact('total', 'inserted', 'invalid', 'suppressed');
    }

    /**
     * สร้าง recipients จาก import batch (phones ที่ parse แล้ว)
     */
    public function buildFromImportBatch(SmsCampaign $campaign, SmsImportBatch $batch, array $phones, string $countryCode = '66'): array
    {
        $total = count($phones);
        $suppressed = 0;

        // suppression/opt-out
        $opted = [];
        if ($campaign->respect_opt_out && $phones) {
            $opted = DB::table('sms_opt_outs')
                ->whereIn('phone_e164', array_values(array_unique($phones)))
                ->pluck('phone_e164')
                ->all();
        }
        $optMap = $opted ? array_fill_keys($opted, true) : [];

        $now = now();
        $items = [];

        foreach ($phones as $e164) {
            if (isset($optMap[$e164])) {
                $suppressed++;
                continue;
            }

            $items[] = [
                'team_id' => $campaign->team_id,
                'campaign_id' => $campaign->id,
                'import_batch_id' => $batch->id,
                'source_type' => 'upload',
                'source_id' => $batch->id,
                'phone_e164' => $e164,
                'phone_raw' => null,
                'country_code' => $countryCode,

                'has_consent' => null,
                'is_opted_out' => false,

                'status' => 'queued',
                'provider' => $campaign->provider ?: 'vonage',
                'attempts' => 0,
                'queued_at' => $now,

                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $before = DB::table('sms_recipients')->where('campaign_id', $campaign->id)->count();
        if ($items) {
            DB::table('sms_recipients')->insertOrIgnore($items);
        }
        $after = DB::table('sms_recipients')->where('campaign_id', $campaign->id)->count();

        $inserted = max(0, $after - $before);

        // update batch counters
        $batch->suppressed_phones = (int) $suppressed;
        $batch->status = 'ready';
        $batch->save();

        // sync campaign counters
        $campaign->total_recipients = (int) $after;
        $campaign->suppressed_count = $campaign->suppressed_count + $suppressed;
        $campaign->save();

        return compact('total', 'inserted', 'suppressed');
    }
}
