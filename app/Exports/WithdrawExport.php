<?php

namespace App\Exports;


use Maatwebsite\Excel\Concerns\WithMapping;
use Yajra\DataTables\Exports\DataTablesCollectionExport;

class WithdrawExport extends DataTablesCollectionExport implements WithMapping
{

//   use ExportableLargeData;
    public function headings(): array
    {
        return [
            'ธนาคาร',
            'เวลาธนาคาร',
            'จำนวนเงิน',
            'User ID',
            'สมาชิก',
        ];
    }

//    public function collection()
//    {
//        return  Member::query()->select('date_regis','user_name','firstname','lastname','lineid','tel')->where('enable','Y')->whereBetween('date_regis',[now()->startOfMonth()->toDateString(),now()->toDateString()])->cursor();
//    }

    public function map($row): array
    {
        return [
            $row['bank_raw'],
            $row['date'],
            $row['money'],
            $row['user_name'],
            $row['member_name'],
        ];
    }
}
