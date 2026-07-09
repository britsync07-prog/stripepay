<?php

namespace Tests\Unit;

use App\Exports\TextValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SpreadsheetExportTest extends TestCase
{
    protected function tearDown(): void
    {
        // setValueBinder is a global static — restore the default for other tests.
        Cell::setValueBinder(new DefaultValueBinder);
        parent::tearDown();
    }

    /**
     * A value beginning with `=` (or +, -, @) must be stored as literal text,
     * not a live formula — otherwise a malicious lead name/email executes when
     * an admin opens the export (spreadsheet formula/DDE injection). This
     * exercises the exact path SpreadsheetExport uses: TextValueBinder + fromArray.
     */
    #[DataProvider('dangerousValues')]
    public function test_dangerous_values_are_stored_as_text(string $payload): void
    {
        Cell::setValueBinder(new TextValueBinder);

        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->fromArray([[$payload]], null, 'A1');
        $cell = $sheet->getCell('A1');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertFalse($cell->isFormula(), 'Untrusted value must not be a formula.');
        $this->assertSame($payload, $cell->getValue());
    }

    public function test_numbers_still_bind_as_numeric(): void
    {
        Cell::setValueBinder(new TextValueBinder);

        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->fromArray([[1234]], null, 'A1');

        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('A1')->getDataType());
    }

    /** @return array<int, array<int, string>> */
    public static function dangerousValues(): array
    {
        return [
            ['=1+1'],
            ['=HYPERLINK("http://evil","x")'],
            ['+1'],
            ['-1'],
            ['@SUM(A1:A9)'],
            ["=cmd|'/c calc'!A1"],
        ];
    }
}
