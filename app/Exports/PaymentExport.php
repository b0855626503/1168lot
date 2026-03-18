<?php

namespace App\Exports;


use Maatwebsite\Excel\Concerns\WithMapping;
use Yajra\DataTables\Exports\DataTablesCollectionExport;


class PaymentExport extends DataTablesCollectionExport implements WithMapping
{

//   use ExportableLargeData;
    public function headings(): array
    {
        return [
            'เวลาธนาคาร',
            'ธนาคาร',
            'เลขบช',
            'เลขเคส',
            'UserName',
            'จำนวนเงิน',
        ];
    }


    public function map($row): array
    {
        return [
            $row['date_raw'],
            $row['bank_raw'],
            $row['acc_no'],
            $row['txid'],
            $row['user_name'],
            $row['amount_raw'],


        ];
    }
}
