<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Professor;
use App\Models\Student;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function showForm()
    {
        $sallesDisponibles = [
            'Amphi A', 'Salle 4 AB', 'Salle 5 AB',
            'Salle 15 AB', 'Salle 16 AB', 'Salle 17 AB',
            'Salle 21 AB', 'Salle 22 AB', 'Salle 23 AB', 'Salle 24 AB'
        ];

        $sallesSelectionnees = session('salles', $sallesDisponibles);
        $planningConfig      = null;   
        $nbCreneaux          = 0;  

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

<<<<<<< HEAD
        return redirect()->back()->with('success',
            'Étudiants importés avec succès ! (' . Student::count() . ' étudiants au total)');
    }

    // Import professeurs 
    public function importProfessors(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);

        Excel::import(new ProfessorsImport, $request->file('excel_file'));

        return redirect()->back()->with('success',
            'Professeurs importés avec succès ! (' . Professor::count() . ' professeurs au total)');
    }

    // Enregistrer les salles sélectionnées
    public function saveSalles(Request $request)
    {
        $request->validate([
            'salles' => 'required|array|min:1',
        ]);

        $salles     = $request->salles;
        $nbSalles   = count($salles);
        $nbJours    = count(session('jours_soutenance', []));
        $nbEtudiants = \App\Models\Student::count();

        // Vérification que les dates sont enregistrées
        if ($nbJours == 0) {
            return redirect()->back()
                ->with('error', 'Veuillez d\'abord enregistrer les dates de soutenance avant de choisir les salles.');
=======
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Impossible de lire le fichier : ' . $e->getMessage());
>>>>>>> bc857cde4a18497a97e97134a791ece973bdc0fe
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

            $filiere = strtoupper($sheetName); // normalise : GI, DATA, TDIA, etc.
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

    // Salles (inchangé sauf signature)
    public function saveSalles(Request $request)
    {
        $request->validate(['salles' => 'required|array|min:1']);

        $salles      = $request->salles;
        $nbSalles    = count($salles);
        $nbJours     = count(session('jours_soutenance', []));
        $nbEtudiants = Student::count();

        // Nombre de créneaux par jour — lu depuis la session (sauvé par le form des dates)
        $nbCreneauxParJour = session('nb_creneaux', 4);

        if ($nbJours == 0) {
            return redirect()->back()
                ->with('error', 'Veuillez d\'abord enregistrer les dates de soutenance.');
        }

        if ($nbEtudiants == 0) {
            return redirect()->back()
<<<<<<< HEAD
                ->with('error', ' Aucun étudiant trouvé. Veuillez d\'abord importer les étudiants.');
=======
                ->with('error', 'Aucun étudiant trouvé. Importez d\'abord les étudiants.');
>>>>>>> bc857cde4a18497a97e97134a791ece973bdc0fe
        }

        $totalCreneaux = $nbCreneauxParJour * $nbSalles * $nbJours;

        if ($totalCreneaux < $nbEtudiants) {
<<<<<<< HEAD
            $sallesNecessaires = ceil($nbEtudiants / (5 * $nbJours));
            return redirect()->back()
                ->with('error',
                    'Salles insuffisantes ! ' .
                    'Avec ' . $nbSalles . ' salle(s) × 5 créneaux × ' . $nbJours . ' jour(s) = ' .
                    $totalCreneaux . ' créneaux disponibles, ' .
                    'mais vous avez ' . $nbEtudiants . ' étudiants. ' .
                    'Il faut au minimum ' . $sallesNecessaires . ' salle(s).'
                );
=======
            $sallesNecessaires = ceil($nbEtudiants / ($nbCreneauxParJour * $nbJours));
            return redirect()->back()->with('error',
                'Salles insuffisantes ! ' .
                $nbSalles . ' salle(s) × ' . $nbCreneauxParJour . ' créneaux × ' . $nbJours . ' jour(s) = ' .
                $totalCreneaux . ' créneaux, mais ' . $nbEtudiants . ' étudiants. ' .
                'Minimum : ' . $sallesNecessaires . ' salle(s).'
            );
>>>>>>> bc857cde4a18497a97e97134a791ece973bdc0fe
        }

        session(['salles' => $salles]);

        return redirect()->back()->with('success',
            '✅ ' . $nbSalles . ' salle(s) enregistrée(s) ! ' .
            $totalCreneaux . ' créneaux pour ' . $nbEtudiants . ' étudiants ' .
            '(' . ($totalCreneaux - $nbEtudiants) . ' libre(s)).'
        );
    }

    public function addSalle(Request $request)
    {
        $request->validate(['nouvelle_salle' => 'required|string|max:50']);
        $salles   = session('salles', []);
        $nouvelle = trim($request->nouvelle_salle);
        if (!in_array($nouvelle, $salles)) {
            $salles[] = $nouvelle;
            session(['salles' => $salles]);
        }
        return redirect()->back()->with('success', 'Salle "' . $nouvelle . '" ajoutée !');
    }
}