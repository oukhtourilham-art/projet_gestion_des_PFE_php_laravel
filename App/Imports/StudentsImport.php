<?php

namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Student;

class StudentsImport implements ToModel, WithHeadingRow
{ public function model(array $row)
   {
  dd($row);
    if (empty($row["CNE"])) return null;
    

   return new Student([
    "CNE"=> $row["cne"],
    "nom"=> $row["nom"],
    "prenom"=> $row["prenom"],
    "email_perso"=> $row["email_personnel"],
    "email_etu"=> $row["email_academique"],
]);
}
}
