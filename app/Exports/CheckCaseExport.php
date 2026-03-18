<?php

namespace App\Exports;


use App\DataTables\Concerns\ExportableLargeData;
use Maatwebsite\Excel\Concerns\WithMapping;
use Yajra\DataTables\Exports\DataTablesCollectionExport;

class CheckCaseExport extends DataTablesCollectionExport implements WithMapping
{

//   use ExportableLargeData;
    public function headings(): array
    {
        return [
            'วันที่สร้างรายการ',
            'txid',
            'เลขเคส',
            'UserName',
            'จำนวนแจ้งฝาก',
            'จำนวนที่สแกนจ่าย',
            'สถานะ',
            'วันที่อัพเดทรายการ',
        ];
    }

//    public function collection()
//    {
//        return  Member::query()->select('date_regis','user_name','firstname','lastname','lineid','tel')->where('enable','Y')->whereBetween('date_regis',[now()->startOfMonth()->toDateString(),now()->toDateString()])->cursor();
//    }

    public function map($row): array
    {
        return [
            $row['date_create'],
            $row['txid'],
            $row['detail'],
            $row['username'],
            $row['amount'],
            $row['payamount'],
            $row['status'],
            $row['date_update'],
        ];
    }
}
