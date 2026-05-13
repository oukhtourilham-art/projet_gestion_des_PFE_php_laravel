<?php

namespace App\Imports;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class SujetsImport implements ToCollection, WithHeadingRow
{
   public function collection(Collection $rows)
     {
    foreach ($rows as $row){
        $cne    = trim($row['cne'] ?? '');
        $nom    = trim($row['nom'] ?? '');
        $sujet  = trim($row['sujet'] ?? '');
        $langue = strtoupper(trim($row['langue'] ?? 'FR'));
        $binome = filter_var($row['binome'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (empty($cne) || empty($sujet)) {
            continue;
        }

        $student = Student::where('cne', $cne)->first();
        if (!$student) {
            continue;
        }
        $student->nom = $nom;
        $student->sujet = $sujet;
        $student->langue = $langue;
        // CASE 1: !BINOME
        if (!$binome) {
            $student->binome_id = null;
            $student->save();
            continue;
        }

        // CASE 2:binome trouvez etudiant avec memesijet
        $partner = Student::where('sujet', $sujet)
            ->where('cne', '!=', $cne)
            ->whereNull('binome_id')
            ->first();

        if ($partner) {
            $student->binome_id = $partner->id;
            $partner->binome_id = $student->id;
            $partner->save();
        }

        $student->save();
    }
     }}