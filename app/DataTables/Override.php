<?php

namespace App\DataTables;

use Maatwebsite\Excel\Classes\LaravelExcelWorksheet;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Yajra\Datatables\Services\DataTable;

class Override extends DataTable
{
    protected function buildExcelFile()
    {
        /** @var \Maatwebsite\Excel\Excel $excel */
        $excel = app('excel');

        return $excel->create($this->getFilename(), function (LaravelExcelWriter $excel) {
            $excel->sheet('exported-data', function (LaravelExcelWorksheet $sheet) {
                $this->query()->chunk(100, function ($modelInstance) use ($sheet) {
                    $modelAsArray = $modelInstance->toArray();
                    foreach ($modelAsArray as $model)
                        $sheet->appendRow($model);
                });
            });
        });
    }

    public function query()
    {
        // this method is overwritten in my datatable class , and it's that query result that is chunked method buildExcelFile() above
    }
}