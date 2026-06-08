<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Professor;
use App\Models\Student;
use App\Imports\StudentsMultiSheetImport;
use App\Imports\ProfessorsImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function showForm()
    {
        //Les Salles disponibles par défaut
        $sallesDisponibles = [
            'Amphi A', 'Salle 4 AB', 'Salle 5 AB',
            'Salle 15 AB', 'Salle 16 AB', 'Salle 17 AB',
            'Salle 21 AB', 'Salle 22 AB', 'Salle 23 AB', 'Salle 24 AB'
        ];

        // Salles déjà sélectionnées (depuis session)
        $sallesSelectionnees = session('salles', $sallesDisponibles);

        return view('import', compact('sallesDisponibles', 'sallesSelectionnees'));
    }

    // Import fichier unique multi-feuilles
    public function importStudents(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);

        Excel::import(new StudentsMultiSheetImport, $request->file('excel_file'));

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
                ->with('error', '❌ Veuillez d\'abord enregistrer les dates de soutenance avant de choisir les salles.');
        }

        // Calcul des créneaux disponibles
        // 5 créneaux par jour × nb salles × nb jours
        $totalCreneaux = 5 * $nbSalles * $nbJours;

        if ($nbEtudiants == 0) {
            return redirect()->back()
                ->with('error', '❌ Aucun étudiant trouvé. Veuillez d\'abord importer les étudiants.');
        }

        // Vérification principale
        if ($totalCreneaux < $nbEtudiants) {
            $sallesNecessaires = ceil($nbEtudiants / (5 * $nbJours));
            return redirect()->back()
                ->with('error',
                    '❌ Salles insuffisantes ! ' .
                    'Avec ' . $nbSalles . ' salle(s) × 5 créneaux × ' . $nbJours . ' jour(s) = ' .
                    $totalCreneaux . ' créneaux disponibles, ' .
                    'mais vous avez ' . $nbEtudiants . ' étudiants. ' .
                    'Il faut au minimum ' . $sallesNecessaires . ' salle(s).'
                );
        }

        //Si tout est bon -> enregistrer
        session(['salles' => $salles]);

        $creneauxRestants = $totalCreneaux - $nbEtudiants;

        return redirect()->back()
            ->with('success',
                '✅ ' . $nbSalles . ' salle(s) enregistrée(s) ! ' .
                $totalCreneaux . ' créneaux disponibles pour ' .
                $nbEtudiants . ' étudiants ' .
                '(' . $creneauxRestants . ' créneau(x) libre(s)).'
            );
    }

    // Ajouter une salle personnalisée
    public function addSalle(Request $request)
    {
        $request->validate([
            'nouvelle_salle' => 'required|string|max:50',
        ]);

        $salles = session('salles', []);
        $nouvelle = trim($request->nouvelle_salle);

        if (!in_array($nouvelle, $salles)) {
            $salles[] = $nouvelle;
            session(['salles' => $salles]);
        }

        return redirect()->back()->with('success', 'Salle "' . $nouvelle . '" ajoutée !');
    }
}