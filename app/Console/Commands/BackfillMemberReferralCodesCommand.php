<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillMemberReferralCodesCommand extends Command
{
    protected $signature = 'member:backfill-referral-codes
        {--apply : Write generated codes to members table}
        {--chunk=1000 : Chunk size for scanning members}
        {--limit=0 : Maximum members to process (0 = no limit)}
        {--member-code=* : Only process specific member code(s)}';

    protected $description = 'Generate missing 8-char referral_code for existing members';

    public function handle(): int
    {
        if (!Schema::hasTable('members')) {
            $this->error('ไม่พบตาราง members');
            return self::FAILURE;
        }

        if (!Schema::hasColumn('members', 'referral_code')) {
            $this->error('ไม่พบคอลัมน์ members.referral_code (กรุณารัน migrate ก่อน)');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $chunk = max(100, (int) $this->option('chunk'));
        $limit = max(0, (int) $this->option('limit'));
        $memberCodes = collect((array) $this->option('member-code'))
            ->map(static fn ($value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();

        $query = DB::table('members')
            ->select(['code', 'user_name', 'referral_code'])
            ->where('code', '>', 0)
            ->where(function ($builder): void {
                $builder->whereNull('referral_code')
                    ->orWhere('referral_code', '');
            })
            ->orderBy('code');

        if (!empty($memberCodes)) {
            $query->whereIn('code', $memberCodes);
        }

        $eligibleCount = (clone $query)->count();
        if ($eligibleCount === 0) {
            $this->info('ไม่พบสมาชิกที่ต้อง generate referral_code');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'เริ่ม backfill referral_code (eligible=%d, apply=%s, chunk=%d%s)',
            $eligibleCount,
            $apply ? 'Y' : 'N',
            $chunk,
            $limit > 0 ? ', limit=' . $limit : ''
        ));

        $processed = 0;
        $updated = 0;
        $previewRows = [];

        $query->chunkById($chunk, function (Collection $members) use (
            $apply,
            $limit,
            &$processed,
            &$updated,
            &$previewRows
        ): bool {
            foreach ($members as $member) {
                if ($limit > 0 && $processed >= $limit) {
                    return false;
                }

                $processed++;
                $memberCode = (int) ($member->code ?? 0);
                if ($memberCode <= 0) {
                    continue;
                }

                $generated = $this->generateUniqueReferralCode();

                if (!$apply) {
                    $previewRows[] = [
                        'member_code' => $memberCode,
                        'user_name' => (string) ($member->user_name ?? ''),
                        'generated_referral_code' => $generated,
                    ];
                    continue;
                }

                $affected = DB::table('members')
                    ->where('code', $memberCode)
                    ->where(function ($builder): void {
                        $builder->whereNull('referral_code')
                            ->orWhere('referral_code', '');
                    })
                    ->update([
                        'referral_code' => $generated,
                        'date_update' => now(),
                    ]);

                if ($affected > 0) {
                    $updated++;
                }
            }

            return true;
        }, 'code');

        $this->info(sprintf(
            'เสร็จสิ้น (processed=%d, updated=%d, dry_run=%s)',
            $processed,
            $updated,
            $apply ? 'N' : 'Y'
        ));

        if (!$apply && !empty($previewRows)) {
            $this->table(
                ['member_code', 'user_name', 'generated_referral_code'],
                collect($previewRows)->take(20)->all()
            );
            if (count($previewRows) > 20) {
                $this->line('... แสดงตัวอย่าง 20 รายการแรก');
            }
        }

        return self::SUCCESS;
    }

    private function generateUniqueReferralCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $exists = DB::table('members')
                ->where('referral_code', $code)
                ->exists();

            if (!$exists) {
                return $code;
            }
        }

        throw new \RuntimeException('ไม่สามารถสร้าง referral_code ที่ไม่ซ้ำได้');
    }
}

