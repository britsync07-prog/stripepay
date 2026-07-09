<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\IValueBinder;

/**
 * Binds every string cell as explicit text, so a value beginning with
 * `= + - @` (or a tab/CR) is never interpreted as a formula. Prevents CSV/DDE
 * "formula injection": untrusted fields (a lead's name/email/phone) exported to
 * .xlsx must not execute when an admin opens the file. Non-strings fall back to
 * the default binder so numbers/dates still bind normally.
 */
class TextValueBinder implements IValueBinder
{
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return (new DefaultValueBinder)->bindValue($cell, $value);
    }
}
