<?php
namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class StudentsImport implements ToCollection
{  public function __construct(private string $filiere) {}
    public function collection(Collection $rows)
    {
        foreach ($rows->skip(1) as $row) {
            if (empty($row[0])) continue;

            Student::create([
                'CNE'         => $row[0],
                'nom'         => $row[1],
                'prenom'      => $row[2],
                'email_perso' => $row[3],
                'email_etu'   => $row[4],
                'filiere'     => $this->filiere,
            ]);
        }
    }
}