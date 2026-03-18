<?php

namespace App\Exports;


use App\DataTables\Concerns\ExportableLargeData;
use Maatwebsite\Excel\Concerns\WithMapping;
use Yajra\DataTables\Exports\DataTablesCollectionExport;

class UsersExport extends DataTablesCollectionExport implements WithMapping
{

//   use ExportableLargeData;
    public function headings(): array
    {
        return [
            'Date Regis',
            'Firstname',
            'LastName',
            'UserName',
            'Line ID',
            'Mobile',
            'count_deposit',
        ];
    }

//    public function collection()
//    {
//        return  Member::query()->select('date_regis','user_name','firstname','lastname','lineid','tel')->where('enable','Y')->whereBetween('date_regis',[now()->startOfMonth()->toDateString(),now()->toDateString()])->cursor();
//    }

    public function map($row): array
    {
        return [
            $row['date_regis'],
            $row['firstname'],
            $row['lastname'],
            $row['user_name'],
            $row['lineid'],
            $row['tel'],
            $row['count_deposit'] ?? $row['deposit'],
        ];
    }
}
