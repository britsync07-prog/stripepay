<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Minimal, reusable .xlsx builder used by the admin list exports. Hand it a
 * sheet title, a heading row, and the data rows; it styles the header in the
 * brand gold, freezes it, auto-sizes the columns, and streams the file to the
 * browser. Uses phpoffice/phpspreadsheet directly (maatwebsite/excel does not
 * support PHP 8.5 — see CLAUDE.md).
 */
class SpreadsheetExport
{
    /**
     * @param  list<string>  $headings
     * @param  list<array<int, string|int|float|null>>  $rows
     */
    public function __construct(
        private string $title,
        private array $headings,
        private array $rows,
    ) {}

    public function download(string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        // Excel caps worksheet tab names at 31 characters.
        $sheet->setTitle(mb_substr($this->title, 0, 31));

        // The value binder is a global static; force string cells to explicit
        // text so untrusted values starting with `= + - @` cannot become live
        // formulas (spreadsheet formula injection), then restore the previous
        // binder so this mutation never leaks into a long-running worker
        // (Octane/Horizon) or any later spreadsheet operation. Binding happens
        // at write time (fromArray), so restoring before the streamed save is safe.
        $previousBinder = Cell::getValueBinder();
        Cell::setValueBinder(new TextValueBinder);

        try {
            $sheet->fromArray($this->headings, null, 'A1');

            if ($this->rows !== []) {
                $sheet->fromArray($this->rows, null, 'A2');
            }
        } finally {
            Cell::setValueBinder($previousBinder);
        }

        $this->styleHeader($sheet);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function styleHeader(Worksheet $sheet): void
    {
        $columns = count($this->headings);

        if ($columns === 0) {
            return;
        }

        $lastColumn = Coordinate::stringFromColumnIndex($columns);
        $headerRange = "A1:{$lastColumn}1";

        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('231911');
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F5A623'); // BlackDesck gold
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->freezePane('A2');

        for ($column = 1; $column <= $columns; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }
    }
}
