<?php

namespace App\Exports;


use App\DataTables\Concerns\ExportableLargeData;
use Gametech\Member\Models\Member;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MembersExport implements FromQuery, WithMapping, WithHeadings, ShouldQueue
{
    use Exportable;


    public function headings(): array
    {
        return [
            'Name',
            'Regis Date',
            'UserName',
            'Tel'
        ];
    }

    public function query()
    {
        return Member::query()->select('date_regis', 'user_name', 'name', 'tel')->where('enable', 'Y')->cursor();
    }

    public function map($row): array
    {
        return [
            $row['name'],
            $row['date_regis'],
            $row['user_name'],
            $row['tel']
        ];
    }

}
