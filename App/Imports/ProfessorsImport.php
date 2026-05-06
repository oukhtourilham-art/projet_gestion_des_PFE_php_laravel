<?php

namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Student;

class ProfessorsImport implements ToModel, WithHeadingRow
{ public function model(array $row)
   {
    dd($row);
    if (empty($row["nom"])) return null;

   return new Student([
    "nom"=> $row["nom"],
    "prenom"=> $row["prenom"],
  "filiere"=> $row["Discipline"],
]);
}
}
