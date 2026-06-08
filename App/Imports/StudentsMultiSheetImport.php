<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentsMultiSheetImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new StudentSheetImport('GI'),
            1 => new StudentSheetImport('DATA'),
            2 => new StudentSheetImport('TDAI'),
        ];
    }
}