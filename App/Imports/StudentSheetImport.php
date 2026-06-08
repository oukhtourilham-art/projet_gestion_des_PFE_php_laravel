<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class StudentSheetImport implements ToCollection, WithHeadingRow
{
    private $filiere;

    public function __construct($filiere)
    {
        $this->filiere = $filiere;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            $cne = trim($row['cne'] ?? '');
            if (empty($cne)) continue;

            Student::updateOrCreate(
                ['CNE' => $cne],
                [
                    'nom'         => trim($row['nom'] ?? ''),
                    'prenom'      => trim($row['prenom'] ?? ''),
                    'email_perso' => trim($row['email_personnel'] ?? ''),
                    'email_etu'   => trim($row['email_academique'] ?? ''),
                    'filiere'     => $this->filiere,
                    'sujet'       => trim($row['sujet'] ?? ''),
                    'langue'      => strtoupper(trim($row['langue'] ?? 'FR')),
                    'binome'      => filter_var($row['binome'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]
            );
        }
    }
}