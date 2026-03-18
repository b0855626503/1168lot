<?php

namespace App\Console\Commands;

use Gametech\Game\Models\GameUserProxy;
use Gametech\Member\Models\MemberProxy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateGameIdsForMembers extends Command
{
    protected $signature = 'game:generate-ids 
                            {--force : บังคับสร้างใหม่แม้มีไอดีแล้ว}';

    protected $description = 'สร้างไอดีเกมให้สมาชิกทุกคน (เช็กในตาราง games_user ก่อน)';

    public function handle()
    {
        $force = $this->option('force');
        $this->info('เริ่มสร้างไอดีเกม...');

        MemberProxy::whereNull('game_user')
            ->where('enable', 'Y')
            ->orderBy('code')
            ->chunkById(50, function ($members) use ($force) {
                foreach ($members as $member) {
                    try {
                        $exists = GameUserProxy::where('member_code', $member->code)->exists();

                        if (! $force && $exists) {
                            $this->line("{$member->code} ข้าม: {$member->user_name} มีไอดีเกมแล้ว");

                            continue;
                        }

                        DB::transaction(function () use ($member, $exists, $force) {
                            // ถ้าจะบังคับสร้างใหม่: ลบของเดิมก่อน (ถ้าต้องการจริง ๆ ค่อยเอาคอมเมนต์ออก)
                            if ($exists && $force) {
                                // GameUserProxy::where('member_code', $member->code)->delete();
                            }

                            $res = app('Gametech\Game\Repositories\GameUserRepository')
                                ->addGameUser(1, $member->code, $member);

                            $gameUserId = null;

                            // กันเคส response รูปแบบแปลก ๆ
                            if (is_array($res) && ($res['success'] ?? false) === true) {
                                $gameUserId = data_get($res, 'data.user_name');
                            }

                            // fallback หาในฐานข้อมูล
                            if (! $gameUserId) {
                                $gameUserId = GameUserProxy::where('member_code', $member->code)->value('user_name');
                            }

                            if ($gameUserId) {
                                MemberProxy::whereKey($member->code)->update([
                                    'game_user' => $gameUserId,
                                ]);

                                $this->info("{$member->code} OK: อัปเดต members.game_user = {$gameUserId}");
                            } else {
                                $msg = is_array($res) ? ($res['msg'] ?? $res['message'] ?? 'unknown') : 'unknown';
                                $this->warn("{$member->code} สร้างไอดีเกมไม่สำเร็จ (ไม่พบ game_user id) : {$msg}");
                            }
                        });
                    } catch (\Throwable $e) {
                        // log error แต่ไม่ให้ทั้ง command ตาย
                        \Log::error('GenerateGameIdsForMembers failed for member', [
                            'member_code' => $member->code,
                            'user_name' => $member->user_name ?? null,
                            'error' => $e->getMessage(),
                        ]);

                        $this->error("ERROR: member {$member->code} ({$member->user_name}) : {$e->getMessage()}");
                        // แล้ว continue ไป member ถัดไป
                    }
                }
            }, 'code');

        $this->info('สร้างไอดีเกมเสร็จสิ้น!');
    }
}
