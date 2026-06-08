<?php

namespace App\Http\Controllers;

use App\Models\Soutenance;
use App\Models\Professor;
use App\Models\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{

    public function index()
    {
        $soutenances = Soutenance::with(['student.encadrant', 'juries.professor'])->get();
        return view('export', compact('soutenances'));
    }

    // Export Planning PDF
    public function exportPDF()
    {
        $soutenances = Soutenance::with(['student.encadrant', 'juries.professor'])->get();
        $pdf = Pdf::loadView('exports.planning', compact('soutenances'))
                  ->setPaper('a4', 'landscape');
        return $pdf->download('planning-soutenances.pdf');
    }

    // Export Planning Word 
    public function exportWord()
    {
        $soutenances = Soutenance::with(['student.encadrant', 'juries.professor'])->get();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'orientation'  => 'landscape',
            'marginTop'    => 600,
            'marginBottom' => 600,
            'marginLeft'   => 800,
            'marginRight'  => 800,
        ]);

        $section->addText(
            'Planning des Soutenances PFE — ' . date('Y'),
            ['bold' => true, 'size' => 16],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]
        );

        $styleTable  = ['borderSize' => 4, 'borderColor' => 'cccccc', 'cellMargin' => 100];
        $styleHeader = ['bgColor' => '1a3a5c'];
        $fontHeader  = ['bold' => true, 'color' => 'ffffff', 'size' => 10];
        $fontCell    = ['size' => 10];
        $centerPar   = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];

        $table = $section->addTable($styleTable);
        $table->addRow(400);

        foreach (['#', 'Étudiant', 'Filière', 'Date', 'Heure', 'Salle', 'Encadrant', 'Jury'] as $col) {
            $table->addCell(null, $styleHeader)->addText($col, $fontHeader, $centerPar);
        }

        foreach ($soutenances as $i => $s) {
            $encadrant = ($s->student->encadrant->nom ?? '') . ' ' . ($s->student->encadrant->prenom ?? '');
            $jury = $s->juries->map(fn($j) => ($j->professor->nom ?? '') . ' ' . ($j->professor->prenom ?? ''))->implode(' / ');

            // Vérifier si binôme existe
            $binome = $s->binome_student_id
                ? \App\Models\Student::find($s->binome_student_id)
                : null;

            // Préparer nom/prénom/filière avec binôme si existe
            $nomEtudiant     = ($s->student->prenom ?? '') . ' ' . ($s->student->nom ?? '');
            $filiereEtudiant = $s->student->filiere ?? '';

            if ($binome) {
                $nomEtudiant     .= "\n" . ($binome->prenom ?? '') . ' ' . ($binome->nom ?? '');
                $filiereEtudiant .= "\n" . ($binome->filiere ?? '');
            }

            $table->addRow();
            $table->addCell(300)->addText($i + 1, $fontCell, $centerPar);

            // Cellule étudiant — ajouter les 2 lignes si binôme
            $cellEtudiant = $table->addCell(1800);
            $cellEtudiant->addText(($s->student->prenom ?? '') . ' ' . ($s->student->nom ?? ''), $fontCell);
            if ($binome) {
                $cellEtudiant->addText(($binome->prenom ?? '') . ' ' . ($binome->nom ?? ''), $fontCell);
            }

            // Cellule filière — ajouter les 2 lignes si binôme
            $cellFiliere = $table->addCell(800);
            $cellFiliere->addText($s->student->filiere ?? '', $fontCell, $centerPar);
            if ($binome) {
                $cellFiliere->addText($binome->filiere ?? '', $fontCell, $centerPar);
            }

            $table->addCell(900)->addText($s->date_soutenance ?? '-', $fontCell, $centerPar);
            $table->addCell(600)->addText($s->heure_debut ?? '-', $fontCell, $centerPar);
            $table->addCell(700)->addText($s->salle ?? '-', $fontCell, $centerPar);
            $table->addCell(1800)->addText($encadrant, $fontCell);
            $table->addCell(2000)->addText($jury, $fontCell);
        }

        //tempnam pour éviter les problèmes de permission
        $filename = 'planning-soutenances.docx';
        $temp = tempnam(sys_get_temp_dir(), 'planning_') . '.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($temp);
        return response()->download($temp, $filename)->deleteFileAfterSend(true);
    }

    // Export Affectation PDF
    public function exportAffectationPDF()
    {
        $professors = Professor::with('students')->get();
        $pdf = Pdf::loadView('exports.affectation', compact('professors'))
                  ->setPaper('a4', 'portrait');
        return $pdf->download('affectation-encadrants.pdf');
    }

    // Export Affectation Word 
    public function exportAffectationWord()
    {
        $professors = Professor::with('students')->get();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop'    => 600,
            'marginBottom' => 600,
            'marginLeft'   => 800,
            'marginRight'  => 800,
        ]);

        $section->addText(
            'Affectation des Encadrants PFE — ' . date('Y'),
            ['bold' => true, 'size' => 16],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 300]
        );

        $styleTable  = ['borderSize' => 4, 'borderColor' => 'cccccc', 'cellMargin' => 100];
        $styleHeader = ['bgColor' => '1a3a5c'];
        $fontHeader  = ['bold' => true, 'color' => 'ffffff', 'size' => 10];
        $fontCell    = ['size' => 10];

        $table = $section->addTable($styleTable);
        $table->addRow(400);

        foreach (['Encadrant', 'Discipline', 'Nb Étudiants', 'Étudiants'] as $col) {
            $table->addCell(null, $styleHeader)->addText($col, $fontHeader);
        }

        foreach ($professors as $prof) {
            $etudiants = $prof->students->map(fn($e) => $e->nom . ' ' . $e->prenom)->implode(', ');
            $table->addRow();
            $table->addCell(2000)->addText($prof->nom . ' ' . $prof->prenom, $fontCell);
            $table->addCell(1500)->addText($prof->discipline ?? '-', $fontCell);
            $table->addCell(800)->addText($prof->students->count(), $fontCell);
            $table->addCell(5000)->addText($etudiants ?: '-', $fontCell);
        }

        
        $filename = 'affectation-encadrants.docx';
        $temp = tempnam(sys_get_temp_dir(), 'affect_') . '.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($temp);
        return response()->download($temp, $filename)->deleteFileAfterSend(true);
    }


    // Export PV individuel 
    public function exportPV($id, $format = 'pdf')
    {
        $soutenance = Soutenance::with([
            'student.encadrant',
            'juries.professor',
        ])->findOrFail($id);

        $nom = $soutenance->student->nom ?? 'etudiant';

        if ($format == 'pdf') {
            $pdf = Pdf::loadView('exports.pv', compact('soutenance'))
                      ->setPaper('a4', 'portrait');
            return $pdf->download('pv-' . $nom . '.pdf');
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop'    => 600,
            'marginBottom' => 600,
            'marginLeft'   => 800,
            'marginRight'  => 800,
        ]);

        $encadrant = ($soutenance->student->encadrant->nom ?? '') . ' ' . ($soutenance->student->encadrant->prenom ?? '');

        $section->addText('UNIVERSITE ABDELMALEK ESSAADI', ['bold' => true, 'size' => 13], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addText('École Nationale des Sciences Appliquées d\'Al-Hoceima', ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addText('Fiche d\'évaluation du Projet de Fin d\'Étude', ['bold' => true, 'size' => 13], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]);
        $section->addText('Année Universitaire : 2025-2026', ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 300]);

        $section->addText('Nom - Prénom : ' . ($soutenance->student->prenom ?? '') . ' ' . ($soutenance->student->nom ?? ''), ['size' => 11]);
        $section->addText('Filière : ' . ($soutenance->student->filiere ?? ''), ['size' => 11]);
        $section->addText(' ', ['size' => 11]);

        $section->addText('L\'encadrant(e) interne :', ['bold' => true, 'size' => 11]);
        $section->addText('Pr. ' . $encadrant, ['size' => 11]);
        $section->addText(' ', ['size' => 11]);

        $section->addText('Membres du jury :', ['bold' => true, 'size' => 11]);
        $section->addText('– Pr. ' . $encadrant . ' (Encadrant)', ['size' => 11]);
        foreach ($soutenance->juries as $i => $j) {
            $role = $i === 0 ? 'Président' : 'Rapporteur';
            $section->addText('– Pr. ' . ($j->professor->nom ?? '') . ' ' . ($j->professor->prenom ?? '') . ' (' . $role . ')', ['size' => 11]);
        }

        $section->addText(' ', ['size' => 11]);
        $section->addText('Note du Contenu (C) = ___________', ['size' => 11]);
        $section->addText('Note du Mémoire (M) = ___________', ['size' => 11]);
        $section->addText('Note de la Soutenance (S) = ___________', ['size' => 11]);
        $section->addText(' ', ['size' => 11]);
        $section->addText('MOYENNE = C × 0,5 + M × 0,2 + S × 0,3 = ___________', ['bold' => true, 'size' => 12]);

        $section->addText(' ', ['size' => 11]);
        $date = $soutenance->date_soutenance
            ? \Carbon\Carbon::parse($soutenance->date_soutenance)->format('d/m/Y')
            : '___/___/______';
        $section->addText('Le : ' . $date, ['size' => 11]);
        $section->addText(' ', ['size' => 11]);

        $section->addText('Signature des membres du jury :', ['bold' => true, 'size' => 11]);
        $section->addText('Pr. ' . $encadrant . ' (Encadrant)' . str_repeat(' ', 20) . '________________', ['size' => 11]);
        foreach ($soutenance->juries as $i => $j) {
            $role = $i === 0 ? 'Président' : 'Rapporteur';
            $section->addText('Pr. ' . ($j->professor->nom ?? '') . ' ' . ($j->professor->prenom ?? '') . ' (' . $role . ')' . str_repeat(' ', 20) . '________________', ['size' => 11]);
        }

        
        $filename = 'pv-' . $nom . '.docx';
        $temp = tempnam(sys_get_temp_dir(), 'pv_') . '.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($temp);
        return response()->download($temp, $filename)->deleteFileAfterSend(true);
    }
}