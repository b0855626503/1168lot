<?php

namespace App\DataTables\Concerns;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait ExportableLargeData
{
    /**
     * @var string
     */
    private $file_path;

    /**
     * @var string
     */
    public $root_path = 'app/sheets/';

    /**
     * ExportableLargeData constructor.
     */
    public function __construct()
    {
        $this->createSheetsFolder();

        $this->file_path = $this->fileFullPath();
    }

    /**
     * @return ResponseFactory|Response
     */
    public function buildExcelFile()
    {
        $source = app()->call([$this, 'query']);
        $source = $this->applyScopes($source);

        $dataTable = app()->call([$this, 'dataTable'], compact('source'));
        $dataTable->skipPaging();
        $query = $dataTable->getFilteredQuery();

        $fp = fopen($this->file_path, "a+");

        $query->chunk(10, function ($rows, $key) use ($fp) {

            if ($key == 1) {
                fputcsv($fp, array_keys($rows->first()->toArray()));
            }

            foreach ($rows as $row) {
                fputcsv($fp, array_values($row->toArray()));
            }

        });

        fclose($fp);

        return response();
    }


    public function createSheetsFolder()
    {
        if (!file_exists(storage_path($this->root_path))) {
            mkdir(storage_path($this->root_path), 0777, TRUE);
        }
    }

    /**
     * @return string
     */
    protected function fileFullPath()
    {
        return storage_path($this->root_path . $this->getFilename() . $this->detectFileExtension());
    }


    /**
     * Export results to CSV file.
     *
     * @return mixed
     */
    public function csv()
    {
        return $this->buildExcelFile()->download($this->file_path);
    }

    /**
     * Export results to PDF file.
     *
     * @return mixed
     */
    public function pdf()
    {
        if ('snappy' == config('datatables-buttons.pdf_generator', 'snappy')) {
            return $this->snappyPdf();
        }

        return $this->buildExcelFile()->download($this->file_path);
    }

    /**
     * @return BinaryFileResponse
     */
    public function excel()
    {
        return $this->buildExcelFile()->download($this->file_path);
    }

    /**
     * @return string|null
     */
    private function detectFileExtension()
    {
        $extension = NULL;

        if (request()->action == 'excel') {
            $extension = '.' . strtolower($this->excelWriter);
        }

        if (request()->action == 'csv') {
            $extension = '.' . strtolower($this->csvWriter);
        }

        if (request()->action == 'pdf') {
            $extension = '.' . strtolower($this->pdfWriter);
        }

        return $extension;
    }
}