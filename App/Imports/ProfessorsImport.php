<?php

namespace App\Imports;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Professor;

class ProfessorsImport implements ToCollection
{ public function collection (Collection $rows)
   {
    foreach ($rows->skip(2) as $row) {
    if (empty($row[0]))continue;
    professor::create([
    'nom'  => trim($row[0]),
    'prenom'=> trim($row[1]),
     'discipline'=> trim($row[2]),
]);
    }}
}
