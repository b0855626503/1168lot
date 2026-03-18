<?php

namespace Gametech\CenterOA\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class MemberGameLogRepository extends Repository
{
    protected $cacheMinutes = 0;
    protected $cacheOnly = [];
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model(): string
    {
        return \Gametech\Member\Models\Member::class;
    }

    public function getAff($id)
    {

        //        return $this->withCount(['downs' => function ($query) {
        //            $query->where('enable', 'Y');
        //        }])->withSum(['paymentsPromotion:credit_bonus' => function ($query) {
        //            $query->active()->aff();
        //        }])->withCount(['paymentsPromotion' => function ($query) {
        //                $query->active()->aff();
        //            }])->find($id,['code']);

        return $this->select('code')->without('bank')->where('code', $id)->withCount(['downs' => function ($query) {
            $query->where('enable', 'Y');
        }])->withSum(['paymentsPromotion:credit_bonus' => function ($query) {
            $query->active()->aff();
        }])->withCount(['paymentsPromotion' => function ($query) {
            $query->active()->aff();
        }])->first();
    }

    public function getAffTest($id)
    {

        return $this->withCount(['down' => function ($query) {
            $query->where('enable', 'Y');
        }])->withSum(['paymentsPromotion:credit_bonus' => function ($query) {
            $query->active()->aff();
        }])->find($id);
    }

    public function getUser($search)
    {
        return $this->with('user')->where('user_name',$search)->active()->orWhereHas('user', function (Builder $query) use ($search) {
            $query->where('user_name',$search);
        })->first();


    }

    public function getPro($id): int
    {
        $result = $this->find($id)->bankPayments()->orderBy('date_create', 'desc')->complete()->active()->income()->uncheck()->where('bankstatus', 1)->value('value');

        if (is_null($result)) {
            $result = 0;
        }

        return $result;
    }

    public function sumBonus($id, $date = null)
    {
        return $this->withSum(['bonus_spin:amount' => function ($query) use ($date) {
            if ($date) {
                $query->whereDate('date_create', $date);
            }
            $query->active();
        }])->find($id);

    }

    public function sumPromotion($id)
    {
        return $this->withSum(['paymentsPromotion:credit_bonus' => function ($query) {
            $query->active()->aff();
        }])->find($id);
    }

    public function sumWithdrawSeamless($id, $date = null)
    {

        return $this->with('bank')->withSum(['withdrawSeamless:amount' => function ($query) use ($date) {
            if ($date) {
                $query->whereDate('date_create', $date);

            }
            $query->active()->whereIn('status', [0, 1]);
        }])->find($id);

    }

    public function sumWithdrawSeamlessFree($id, $date = null)
    {

        return $this->with('bank')->withSum(['withdrawSeamlessFree:amount' => function ($query) use ($date) {
            if ($date) {
                $query->whereDate('date_create', $date);

            }
            $query->active()->whereIn('status', [0, 1]);
        }])->find($id);
    }

    public function sumWithdraw($id, $date = null)
    {

        return $this->with('bank')->withSum(['withdraw:amount' => function ($query) use ($date) {
            if ($date) {
                $query->whereDate('date_create', $date);

            }
            $query->active()->whereIn('status', [0, 1]);
        }])->find($id);
    }

    public function sumWithdrawFree($id, $date = null)
    {
        return $this->with('bank')->withSum(['withdrawFree:amount' => function ($query) use ($date) {
            if ($date) {
                $query->whereDate('date_create', $date);

            }
            $query->active()->whereIn('status', [0, 1]);
        }])->find($id);
    }

    public function sumBillFree($id)
    {
        return $this->with('bank')->withSum(['billsFree:amount' => function ($query) {

            $query->active()->where('transfer_type', 2);
        }])->find($id);
    }

    public function loadBill($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->bills()->active()->orderBy('date_create', 'desc')->with(['game', 'promotion'])
            ->select(['bills.*'])
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('bills.date_create', [$date_start, $date_stop]);

            })->get();

    }

    public function loadBillFree($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->billsFree()->active()->orderBy('date_create', 'desc')->with(['game', 'promotion'])
            ->select(['bills_free.*'])
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('bills_free.date_create', [$date_start, $date_stop]);
                //                return $query->whereRaw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ?",[$date_start,$date_stop]);
            })->get();

    }

    public function loadWithdraw($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->bills()->active()->where('method', 'WITHDRAW')->orderBy('date_create', 'desc')
            ->select(['bills.*'])
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('bills.date_create', [$date_start, $date_stop]);

            })->get();

    }

    public function loadBillType($id, $method, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->bills()->active()->where('method', $method)->orderBy('date_create', 'desc')
            ->select(['bills.*'])
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('bills.date_create', [$date_start, $date_stop]);

            })->limit(10)->get();

    }

    public function loadBillTypeArr($id, $method, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->bills()->active()->whereIn('method', $method)->orderBy('date_create', 'desc')
            ->select(['bills.*'])
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('bills.date_create', [$date_start, $date_stop]);

            })->limit(10)->get();

    }


    public function loadBonus($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->bills()->active()->where('credit_bonus','>',0)->orderBy('date_create', 'desc')
            ->select(['bills.*'])
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('bills.date_create', [$date_start, $date_stop]);

            })->limit(10)->get();

    }

    public function loadWithdrawSeamless($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->withdrawSeamless()->active()->orderBy('date_create', 'desc')
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('date_create', [$date_start, $date_stop]);
                //                return $query->whereRaw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ?",[$date_start,$date_stop]);
            })->get();

    }

    public function loadWithdrawSeamlessFree($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->withdrawSeamlessFree()->active()->orderBy('date_create', 'desc')
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('date_create', [$date_start, $date_stop]);
                //                return $query->whereRaw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ?",[$date_start,$date_stop]);
            })->get();

    }

    public function loadWithdrawFree($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->withdrawFree()->active()->orderBy('date_create', 'desc')
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('date_create', [$date_start, $date_stop]);
                //                return $query->whereRaw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ?",[$date_start,$date_stop]);
            })->get();

    }

        public function loadTopup($id, $date_start = null, $date_stop = null)
        {
            return $this->find($id)->bankPayments()->income()->active()->orderBy('date_create', 'desc')
                ->when($date_start, function ($query, $date_start) use ($date_stop) {
                    return $query->whereBetween('date_create', [$date_start, $date_stop]);
     //                return $query->whereRaw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ?",[$date_start,$date_stop]);
                })->limit(10)->get();


        }

    public function loadDeposit($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->bills()->active()->where('method', 'TOPUP')->orderBy('date_create', 'desc')
            ->select(['bills.*'])
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('bills.date_create', [$date_start, $date_stop]);

            })->get();

    }

    public function loadSpin($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->bonus_spin()->orderBy('date_create', 'desc')
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('date_create', [$date_start, $date_stop]);
                //                return $query->whereRaw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ?",[$date_start,$date_stop]);
            })->get();

    }

    public function loadCashback($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->memberFreeCredit()->where('kind', 'CASHBACK')->active()->orderBy('date_create', 'desc')
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('date_create', [$date_start, $date_stop]);
                //                return $query->whereRaw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ?",[$date_start,$date_stop]);
            })->get();

    }

    public function loadCashbackNew($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->memberCreditFree()->where('kind', 'CASHBACK')->active()->orderBy('date_create', 'desc')
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('date_create', [$date_start, $date_stop]);
                //                return $query->whereRaw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ?",[$date_start,$date_stop]);
            })->get();

    }

    public function loadICNew($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->memberCreditFree()->where('kind', 'IC')->active()->orderBy('date_create', 'desc')
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('date_create', [$date_start, $date_stop]);
                //                return $query->whereRaw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ?",[$date_start,$date_stop]);
            })->get();

    }

    public function loadMoneyTran($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->memberTran()->where('kind', 'TRAN_USER')->active()->orderBy('date_create', 'desc')
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('date_create', [$date_start, $date_stop]);
                //                return $query->whereRaw("DATE_FORMAT(date_create,'%Y-%m-%d') between ? and ?",[$date_start,$date_stop]);
            })->get();

    }

    public function loadIC($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->memberIc()->active()->orderBy('date_cashback', 'desc')->with('down')
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('date_create', [$date_start, $date_stop]);
                //                return $query->whereRaw("DATE_FORMAT(date_cashback,'%Y-%m-%d') between ? and ?",[$date_start,$date_stop]);
            })->get();

    }

    public function loadDownline3($id, $date_start = null, $date_stop = null)
    {
        $data = $this->where('enable', 'Y')->orderBy('date_regis', 'desc')->with('down')->whereHas('down')
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('date_create', [$date_start, $date_stop]);
            })->find($id);

        return $data;
        dd($data->toSql());
    }

    public function loadDownline($id, $date_start = null, $date_stop = null)
    {
        $data = $this->active()->orderBy('date_regis', 'desc')
            ->with(['down' => function ($query) use ($date_start, $date_stop) {
                $query->when($date_start, function ($query, $date_start) use ($date_stop) {
                    return $query->whereBetween('date_create', [$date_start, $date_stop]);
                });
            }])->find($id);

        return $data;
        //        dd($data->toSql());
    }

    public function loadDownlineIncome($id, $date_start = null, $date_stop = null)
    {
        return $this->find($id)->paymentsPromotion()->active()->orderBy('date_create', 'desc')->with('down')->whereHas('down')
            ->when($date_start, function ($query, $date_start) use ($date_stop) {
                return $query->whereBetween('date_create', [$date_start, $date_stop]);
            })->get();

    }

    public function loadAccount($bank, $data)
    {
        $member_code = null;
        $bankcode = $data->bank_account->bank->code;

        switch ($bank) {
            case 'ttb':
                //                $field = "acc_bay = ?";
                //                $value = Str::substr($data->detail, -7);
                $title = $data->title;
                $bankcode = $this->Banks($data->report_id);
                $value = $data->atranferer;
                if ($data->report_id === 'BBL' || $data->report_id === 'BAY' || $data->report_id === 'KTB') {
                    $field = "bank_code = $bankcode and SUBSTRING(acc_no, 7, 4) = ?";
                } elseif ($data->report_id === 'GSB' || $data->report_id === 'BAAC') {
                    $field = "bank_code = $bankcode and firstname like '$title%' and SUBSTRING(acc_no, 9, 4) = ?";
                } else {
                    $field = "bank_code = $bankcode and firstname like '$title%' and SUBSTRING(acc_no, 7, 4) = ?";

                }

                break;
            case 'wing':
                $value = Str::of($data->atranferer)->trim();
                $field = 'bank_code = 24 and acc_no = ?';

                break;
            case 'bay':
                //                $field = "acc_bay = ?";
                //                $value = Str::substr($data->detail, -7);

                $bankcode = $this->Banks($data->report_id);
                $value = $data->atranferer;
                if ($data->report_id == 'BAY') {
                    $field = "bank_code = $bankcode and acc_no = ?";
                } else {
                    //                    $value = Str::substr($value, 4, 6);
                    $field = "bank_code = $bankcode and SUBSTRING(acc_no, 3 ,8) = ?";
                }

                break;
            case 'tw':
                //                $field = "tel = ?";
                if ($data->channel == 'WEBHOOK') {
                    $value = Str::of($data->detail)->trim();
                    $field = 'tel = ?';

                } else {
                    $value = Str::of($data->detail)->trim();
                    $field = "CASE WHEN wallet_id IS NULL THEN tel = '$value' ELSE wallet_id = ? END";
                }
                break;
            case 'scb':
                //                $field = "bank_code = 4 and name = ?";
                //                $value = Str::of($data->detail)->after('นาย')->after('นาง')->after('นายสาว')->trim()->__toString();
                if (! empty($data->atranferer)) {
                    if (Str::length($data->atranferer) == 4) {
                        $acc_chk = explode(' ', $data->detail);
                        if (isset($acc_chk[4])) {
                            $firstname = $acc_chk[4];
                            $acc = Str::of($acc_chk[2])->replaceMatches('/[^0-9]/', '')->trim();
                            $value = Str::of($acc)->replace('*', '');
                            $field = "bank_code = 4 and firstname = '$firstname' and acc_check = ?";
                        } else {
                            $field = 'bank_code = ?';
                            $value = 0;
                        }
                    } elseif ($data->channel == 'SMS') {
                        $bankcode = $this->Banks($data->report_id);
                        if ($bankcode == 5 || $bankcode == 14 || $bankcode == 17) {
                            $field = "bank_code = $bankcode and SUBSTRING(acc_no, 7, 6) = ?";
                        } else {
                            $field = "bank_code = $bankcode and SUBSTRING(acc_no, 5, 6) = ?";
                        }

                        $value = Str::of($data->atranferer)->replace('X', '');

                    } else {
                        $field = 'acc_check = ?';
                        $acc = Str::of($data->atranferer)->replaceMatches('/[^0-9]/', '')->trim();
                        $value = Str::of($acc)->replace('*', '');
                    }

                } else {

                    $acc_chk = explode(' ', $data->detail);
                    if (isset($acc_chk[4])) {
                        $firstname = $acc_chk[4];
                        $acc = Str::of($acc_chk[2])->replaceMatches('/[^0-9]/', '')->trim();
                        $value = Str::of($acc)->replace('*', '');
                        $field = "bank_code = 4 and firstname = '$firstname' and acc_check = ?";
                    } else {
                        $field = 'bank_code = ?';
                        $value = 0;
                    }
                }

                break;
            case 'kbank':
                $title = $data->title;
                if ($data->report_id) {
                    $bankcode = $this->Banks($data->report_id);
                    if ($bankcode == 2) {
                        $field = "bank_code = $bankcode and firstname like '$title%' and LENGTH(acc_no) = 10 and SUBSTRING(acc_no, 6, 4) = ?";
                    } elseif ($bankcode == 14) {
                        //                        $field = "bank_code = $bankcode and LENGTH(acc_no) = 12 and SUBSTRING(acc_no, 1, 10) = ?";
                        $field = "bank_code = $bankcode and acc_no = ?";
                    } else {
                        $field = "bank_code = $bankcode and acc_no = ?";
                    }

                } else {
                    $field = "bank_code = 2 and firstname like '$title%' and LENGTH(acc_no) = 10 and SUBSTRING(acc_no, 6, 4) = ?";
                }

                $value = Str::of($data->atranferer)->replace('*', '');

                break;
//            case 'ktb':
//                $field = 'acc_no = ?';
//                $acc = Str::of($data->atranferer)->replaceMatches('/[^0-9]/', '')->trim();
//                $value = Str::of($acc)->replace('*', '');
//
//                break;
        }

        $satang = $this->getSatang($data->value);
        if ($satang > 0) {
            $result = app('Gametech\Member\Repositories\MemberSatangRepository')->findWhere(['bank_code' => $bankcode, 'shortcode' => $value, 'value' => $satang]);
            if ($result->count() == 1) {
                $member_code = $result->first()->member_code;
            }
        }

        return $this->whereRaw($field, [$value])->where('enable', 'Y')
            ->when($member_code, function ($query) use ($member_code) {
                return $query->where('code', $member_code);
            })->get();
    }

    public function Banks($bankcode)
    {

        switch ($bankcode) {
            case 'BBL':
                $result = 1;
                break;
            case 'KBANK':
                $result = 2;
                break;
            case 'KTB':
                $result = 3;
                break;
            case 'SCB':
                $result = 4;
                break;
            case 'GHBANK':
                $result = 5;
                break;
            case 'KKP':
            case 'KKB':
                $result = 6;
                break;
            case 'CIMB':
                $result = 7;
                break;
            case 'IBANK':
                $result = 8;
                break;
            case 'TISCO':
                $result = 9;
                break;
            //            case 'TMB':
            //                $result = 19;
            //                break;
            case 'BAY':
                $result = 11;
                break;
            case 'UOB':
            case 'UOBT':
                $result = 12;
                break;
            case 'LHBANK':
                $result = 13;
                break;
            case 'GSB':
                $result = 14;
                break;
            case 'TBANK':
                $result = 15;
                break;
            case 'BAAC':
                $result = 17;
                break;
            case 'TTB':
            case 'TMB':
                $result = 19;
                break;
            default:
                $result = 0;
                break;
        }

        return $result;

    }

    public function getSatang($amount)
    {

        $satang = explode('.', $amount);
        if (count($satang) == 2) {
            return $satang[1];
        }

        return 0;
    }

    public function loadAccount2($bank, $data)
    {
        $member_code = null;
        //        $bankcode = $data->bank_account->bank->code;

        switch ($bank) {

            case 'wing':
                $value = Str::of($data)->trim();
                $field = 'bank_code = 24 and acc_no = ?';

                break;

        }

        //        $satang = $this->getSatang($data->value);
        //        if ($satang > 0) {
        //            $result = app('Gametech\Member\Repositories\MemberSatangRepository')->findWhere(['bank_code' => $bankcode, 'shortcode' => $value, 'value' => $satang]);
        //            if ($result->count() == 1) {
        //                $member_code = $result->first()->member_code;
        //            }
        //        }

        return $this->whereRaw($field, [$value])->where('enable', 'Y')
            ->when($member_code, function ($query) use ($member_code) {
                return $query->where('code', $member_code);
            })->get();
    }
}
