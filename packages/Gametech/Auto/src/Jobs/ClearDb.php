<?php

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;


class ClearDb implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;



    public function handle()
    {
        $datehalf = now()->subMonths(1)->toDateString();
        $dateone = now()->subMonths(2)->toDateString();
        $date = now()->subMonths(4)->toDateString();
        $datetwo = now()->subMonths(6)->toDateString();

//        $this->info($date);
//        $this->info('Start');
//        $ext = DB::table('members_creditlog')->whereDate('date_create', '<', $date)->delete();
        $ext = 'a';
        $ext2 = '';
        $ext2 = DB::table('members_credit_log')->whereDate('date_create', '<', $datetwo)->delete();
//            $this->info('delete members_credit_log');
//            $this->info($ext2);

//        $ext = '';
        if (!is_null($ext2)) {
            $ext = DB::table('bank_payment')->whereDate('date_create', '<', $datetwo)->delete();
//            $this->info('delete bank_payment');
//            $this->info($ext);
        }

//        $ext = '';
        if (!is_null($ext)) {
            $ext2 = DB::table('members_log')->whereDate('date_create', '<', $date)->delete();
//            $this->info('delete members_log');
//            $this->info($ext2);
        }

//        $ext2 = '';
        if (!is_null($ext2)) {
            $ext = DB::table('all_log')->whereDate('date_create', '<', $date)->delete();
//            $this->info('delete all_log');
//            $this->info($ext2);
        }


//        $ext = '';
        if (!is_null($ext)) {
            $ext2 = DB::table('bills_free')->whereDate('date_create', '<', $datetwo)->delete();
        }

//        $ext2 = '';
        if (!is_null($ext2)) {
            $ext = DB::table('bills')->whereDate('date_create', '<', $datetwo)->delete();
        }

//        $ext = '';
        if (!is_null($ext)) {
            $ext2 = DB::table('payments_log')->whereDate('date_create', '<', $date)->delete();
        }

//        $ext2 = '';
        if (!is_null($ext2)) {
            $ext = DB::table('payments_log_free')->whereDate('date_create', '<', $date)->delete();
        }

//        $ext = '';
        if (!is_null($ext)) {
            $ext2 =  DB::table('logs')->whereDate('date_create', '<', $dateone)->delete();
        }

//        $ext2 = '';
        if (!is_null($ext2)) {
            $ext = DB::table('members_cashback')->whereDate('date_create', '<', $datetwo)->delete();
        }

//        $ext = '';
        if (!is_null($ext)) {
            $ext2 = DB::table('members_ic')->whereDate('date_update', '<', $datetwo)->delete();
        }

//        $ext2 = '';
        if (!is_null($ext2)) {
            $ext = DB::table('logger_user_activity')->whereDate('created_at', '<', $dateone)->delete();
//            $this->info('delete logger_user_activity');
//            $this->info($ext2);
        }

//        $ext = '';
        if (!is_null($ext)) {
            $ext2 =  DB::table('logger_admin_activity')->whereDate('created_at', '<', $dateone)->delete();
//            $this->info('delete logger_admin_activity');
//            $this->info($ext);
        }

//        $ext2 = '';
//        if (!is_null($ext2)) {
//            $ext = DB::table('members_promotionlog')->whereDate('date_create', '<', $date)->delete();
//        }

        $ext = 'a';
        if (!is_null($ext)) {
            $ext2 = DB::table('bonus_spin')->whereDate('date_create', '<', $date)->delete();
        }

//        $ext2 = '';
        if (!is_null($ext2)) {
            $ext = DB::table('members_pointlog')->whereDate('date_create', '<', $date)->delete();
        }

//        $ext = '';
//        if (!is_null($ext2)) {
//            $ext = DB::table('payments_promotion')->whereDate('date_create', '<', $date)->delete();
//        }


//        $ext = '';
        if (!is_null($ext)) {
            $ext2 = DB::table('members_diamondlog')->whereDate('date_create', '<', $date)->delete();
        }

//        $ext2 = '';
        if (!is_null($ext2)) {
            $ext = DB::table('members_credit_free_log')->whereDate('date_create', '<', $datetwo)->delete();
        }

//        $ext = '';
        if (!is_null($ext)) {
            $ext2 =  DB::table('members_freecredit')->whereDate('date_create', '<', $datetwo)->delete();
        }

//        $ext2 = '';
        if (!is_null($ext2)) {
            $ext = DB::table('failed_jobs')->truncate();
        }

        if(!is_null($ext)){
            Artisan::call('db:optimize');
        }


//        $this->info('stop');

    }
}
