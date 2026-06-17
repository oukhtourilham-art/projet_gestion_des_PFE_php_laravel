<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Professor;
use App\Models\Student;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProfessorsImport;

class ImportController extends Controller
{
    public function showForm()
    {
        $defaultSalles = [
            'Amphi A', 'Salle 4 AB', 'Salle 5 AB',
            'Salle 15 AB', 'Salle 16 AB', 'Salle 17 AB',
            'Salle 21 AB', 'Salle 22 AB', 'Salle 23 AB', 'Salle 24 AB'
        ];

        $sallesSelectionnees = session('salles', $defaultSalles);
        $sallesDisponibles = array_values(array_unique(array_merge($defaultSalles, $sallesSelectionnees)));
        $planningConfig      = null;
        $nbCreneaux          = session('nb_creneaux', null);

        $filieres = Student::whereNotNull('filiere')
            ->distinct()
            ->pluck('filiere')
            ->sort()
            ->values();

        return view('import', compact(
            'sallesDisponibles', 'sallesSelectionnees',
            'planningConfig', 'filieres', 'nbCreneaux'
        ));
    }

    //Import unifié : étudiants + profs 
    public function importUnified(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|mimes:xlsx,xls',
        ]);

        $path = $request->file('fichier')->getPathname();

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Impossible de lire le fichier : ' . $e->getMessage());
        }

        $nbEtudiants  = 0;
        $nbProfs      = 0;
        $filieresTrouvees = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetName = trim($sheet->getTitle());

            if (strtolower($sheetName) === 'profs') {
                $nbProfs = $this->importProfsFromSheet($sheet);
                continue;
            }

            $filiere = strtoupper($sheetName);
            $filieresTrouvees[] = $filiere;
            $nbEtudiants += $this->importStudentsFromSheet($sheet, $filiere);
        }

        $msg = "✅ Import terminé — {$nbEtudiants} étudiant(s) importé(s)";
        if ($nbProfs > 0) {
            $msg .= ", {$nbProfs} professeur(s) importé(s)";
        }
        if (!empty($filieresTrouvees)) {
            $msg .= ". Filières détectées : " . implode(', ', $filieresTrouvees);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function importProfessors(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);

        Excel::import(new ProfessorsImport, $request->file('excel_file'));

        return redirect()->back()->with('success',
            'Professeurs importés avec succès ! (' . Professor::count() . ' professeurs au total)');
    }

   
    private function importStudentsFromSheet($sheet, string $filiere): int
    {
        $rows = $sheet->toArray(null, true, true, true); // associatif par lettre colonne
        $count = 0;
        $isFirst = true;

        foreach ($rows as $row) {
            // Ignorer la ligne d'en-tête
            if ($isFirst) { $isFirst = false; continue; }

            $cne = trim($row['A'] ?? '');
            if (empty($cne)) continue; // ligne vide

            $binomeRaw = strtolower(trim($row['H'] ?? 'false'));
            $binome    = in_array($binomeRaw, ['true', '1', 'oui', 'yes']) ? 1 : 0;

            Student::updateOrCreate(
                ['CNE' => $cne],
                [
                    'nom'        => trim($row['B'] ?? ''),
                    'prenom'     => trim($row['C'] ?? ''),
                    'email_perso'=> trim($row['D'] ?? ''),
                    'email_etu'  => trim($row['E'] ?? ''),
                    'sujet'      => trim($row['F'] ?? ''),
                    'langue'     => strtoupper(trim($row['G'] ?? 'FR')),
                    'binome'     => $binome,
                    'filiere'    => $filiere,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function importProfsFromSheet($sheet): int
    {
        $rows = $sheet->toArray(null, true, true, true);
        $count = 0;
        $isFirst = true;

        foreach ($rows as $row) {
            if ($isFirst) { $isFirst = false; continue; }

            $nom = trim($row['A'] ?? '');
            if (empty($nom)) continue;

            Professor::updateOrCreate(
                ['nom' => $nom, 'prenom' => trim($row['B'] ?? '')],
                ['discipline' => trim($row['C'] ?? '')]
            );
            $count++;
        }

        return $count;
    }

    public function saveSalles(Request $request)
    {
        $request->validate(['salles' => 'required|array|min:1']);

        $salles      = $request->salles;
        $nbSalles    = count($salles);
        $nbJours     = count(session('jours_soutenance', []));
        $nbSoutenances = $this->estimateGroupCount();
        $nbCreneauxParJour = session('nb_creneaux');

        if ($nbJours == 0) {
            return redirect()->back()
                ->with('error', 'Veuillez d\'abord enregistrer les dates de soutenance.');
        }

        if ($nbCreneauxParJour === null || $nbCreneauxParJour == 0) {
            return redirect()->back()
                ->with('error', 'Configuration des créneaux manquante. Veuillez d\'abord configurer les dates et créneaux.');
        }

        if ($nbSoutenances == 0) {
            return redirect()->back()
                ->with('error', 'Aucun étudiant trouvé. Importez d\'abord les étudiants.');
        }

        $totalCreneaux = $nbCreneauxParJour * $nbSalles * $nbJours;

        if ($totalCreneaux < $nbSoutenances) {
            $sallesNecessaires = ceil($nbSoutenances / ($nbCreneauxParJour * $nbJours));
            return redirect()->back()
                ->with('error',
                    'Salles insuffisantes ! ' .
                    $nbSalles . ' salle(s) × ' . $nbCreneauxParJour . ' créneaux × ' . $nbJours . ' jour(s) = ' .
                    $totalCreneaux . ' créneaux disponibles, ' .
                    'mais vous avez ' . $nbSoutenances . ' soutenance(s) à planifier. ' .
                    'Il faut au minimum ' . $sallesNecessaires . ' salle(s).'
                );
        }

        session(['salles' => $salles]);

        return redirect()->back()->with('success',
            '✅ ' . $nbSalles . ' salle(s) enregistrée(s) ! ' .
            $totalCreneaux . ' créneaux pour ' . $nbSoutenances . ' soutenances ' .
            '(' . ($totalCreneaux - $nbSoutenances) . ' libre(s)).'
        );
    }

    public function addSalle(Request $request)
    {
        $request->validate(['nouvelle_salle' => 'required|string|max:50']);
        $nouvelle = trim($request->nouvelle_salle);
        
        $salles = session('salles', [
            'Amphi A', 'Salle 4 AB', 'Salle 5 AB',
            'Salle 15 AB', 'Salle 16 AB', 'Salle 17 AB',
            'Salle 21 AB', 'Salle 22 AB', 'Salle 23 AB', 'Salle 24 AB'
        ]);
        
        if (!in_array($nouvelle, $salles)) {
            $salles[] = $nouvelle;
            session(['salles' => $salles]);
        }
        
        return redirect()->back()->with('success', 'Salle "' . $nouvelle . '" ajoutée !');
    }

    private function estimateGroupCount(): int
    {
        $totalStudents = Student::count();
        if ($totalStudents == 0) {
            return 0;
        }

        $binomes = Student::where('binome', 1)->count();
        $solos = Student::where('binome', 0)->count();

        return $solos + (int)floor($binomes / 2);
    }
}