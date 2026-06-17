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

        // ---- Palettes identiques au PDF ----
        $palette = [
            ['FFEB99','7A5F00'], ['C8E6C9','1B5E20'], ['BBDEFB','0D3B6E'],
            ['F8BBD0','880E4F'], ['D1C4E9','4A148C'], ['B2EBF2','006064'],
            ['FFE0B2','E65100'], ['DCEDC8','33691E'], ['E1BEE7','6A1B9A'],
            ['B3E5FC','01579B'], ['FFCCBC','BF360C'], ['FFF9C4','8B6800'],
            ['CFD8DC','263238'], ['F0F4C3','5C6900'], ['FCE4EC','7B003A'],
            ['E8F5E9','1B4D1E'], ['E3F2FD','0A2E5C'], ['FFF3E0','7C3000'],
            ['F3E5F5','4A0072'], ['E0F7FA','004D52'],
        ];
        $filiereColors = [
            'GI'   => ['BBDEFB','0D3B6E'],
            'DATA' => ['C8E6C9','1B5E20'],
            'TDAI' => ['F8BBD0','880E4F'],
        ];
        $datePalette = [
            ['FFF9C4','7A5F00'], ['E1F5FE','01579B'], ['F3E5F5','4A0072'],
            ['E8F5E9','1B4D1E'], ['FCE4EC','7B003A'], ['FFF3E0','7C3000'],
        ];

        // ---- Construction des maps de couleurs ----
        $profColors = [];
        $colorIndex = 0;
        $dateColors = [];
        $dateIndex  = 0;

        foreach ($soutenances as $s) {
            $encKey = trim(($s->student->encadrant->nom ?? '') . ' ' . ($s->student->encadrant->prenom ?? ''));
            if ($encKey && !isset($profColors[$encKey])) {
                $profColors[$encKey] = $palette[$colorIndex % count($palette)];
                $colorIndex++;
            }
            if ($s->juries->count() > 0) {
                $j1Key = trim(($s->juries[0]->professor->nom ?? '') . ' ' . ($s->juries[0]->professor->prenom ?? ''));
                if ($j1Key && !isset($profColors[$j1Key])) {
                    $profColors[$j1Key] = $palette[$colorIndex % count($palette)];
                    $colorIndex++;
                }
            }
            if ($s->juries->count() > 1) {
                $j2Key = trim(($s->juries[1]->professor->nom ?? '') . ' ' . ($s->juries[1]->professor->prenom ?? ''));
                if ($j2Key && !isset($profColors[$j2Key])) {
                    $profColors[$j2Key] = $palette[$colorIndex % count($palette)];
                    $colorIndex++;
                }
            }
            $dateKey = \Carbon\Carbon::parse($s->date_soutenance)->format('d/m/Y');
            if (!isset($dateColors[$dateKey])) {
                $dateColors[$dateKey] = $datePalette[$dateIndex % count($datePalette)];
                $dateIndex++;
            }
        }

        // ---- Construction du document Word ----
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

        foreach (['#', 'Encadrant', 'Jury 1', 'Jury 2', 'Date', 'Heure', 'Salle', 'Nom étudiant', 'Prénom étudiant', 'Filière'] as $col) {
            $table->addCell(null, $styleHeader)->addText($col, $fontHeader, $centerPar);
        }

        foreach ($soutenances as $i => $s) {
            $binome = $s->binome_student_id
                ? \App\Models\Student::find($s->binome_student_id)
                : null;

            // Couleur encadrant
            $encKey   = trim(($s->student->encadrant->nom ?? '') . ' ' . ($s->student->encadrant->prenom ?? ''));
            $encColor = $profColors[$encKey] ?? ['EEEEEE', '333333'];

            // Couleur jury 1
            $j1Key   = $s->juries->count() > 0
                ? trim(($s->juries[0]->professor->nom ?? '') . ' ' . ($s->juries[0]->professor->prenom ?? ''))
                : '';
            $j1Color = $j1Key ? ($profColors[$j1Key] ?? ['EEEEEE', '333333']) : ['EEEEEE', '333333'];

            // Couleur jury 2
            $j2Key   = $s->juries->count() > 1
                ? trim(($s->juries[1]->professor->nom ?? '') . ' ' . ($s->juries[1]->professor->prenom ?? ''))
                : '';
            $j2Color = $j2Key ? ($profColors[$j2Key] ?? ['EEEEEE', '333333']) : ['EEEEEE', '333333'];

            // Couleur date
            $dateStr   = \Carbon\Carbon::parse($s->date_soutenance)->format('d/m/Y');
            $dateColor = $dateColors[$dateStr] ?? ['EEEEEE', '333333'];

            // Couleur filière
            $fil      = $s->student->filiere ?? '';
            $filColor = $filiereColors[$fil] ?? ['EEEEEE', '333333'];

            $table->addRow();

            // # (sans couleur)
            $table->addCell(300)->addText($i + 1, $fontCell, $centerPar);

            // Encadrant
            $encadrant = ($s->student->encadrant->nom ?? '') . ' ' . ($s->student->encadrant->prenom ?? '');
            $table->addCell(1800, ['bgColor' => $encColor[0]])
                  ->addText($encadrant, array_merge($fontCell, ['color' => $encColor[1], 'bold' => true]));

            // Jury 1
            $cellJ1 = $table->addCell(1600, ['bgColor' => $j1Color[0]]);
            if ($s->juries->count() > 0) {
                $cellJ1->addText(
                    ($s->juries[0]->professor->nom ?? '-') . ' ' . ($s->juries[0]->professor->prenom ?? ''),
                    array_merge($fontCell, ['color' => $j1Color[1], 'bold' => true])
                );
            } else {
                $cellJ1->addText('-', $fontCell);
            }

            // Jury 2
            $cellJ2 = $table->addCell(1600, ['bgColor' => $j2Color[0]]);
            if ($s->juries->count() > 1) {
                $cellJ2->addText(
                    ($s->juries[1]->professor->nom ?? '-') . ' ' . ($s->juries[1]->professor->prenom ?? ''),
                    array_merge($fontCell, ['color' => $j2Color[1], 'bold' => true])
                );
            } else {
                $cellJ2->addText('-', $fontCell);
            }

            // Date
            $table->addCell(900, ['bgColor' => $dateColor[0]])
                  ->addText($dateStr, array_merge($fontCell, ['color' => $dateColor[1], 'bold' => true]), $centerPar);

            // Heure (sans couleur)
            $table->addCell(600)->addText($s->heure_debut ?? '-', $fontCell, $centerPar);

            // Salle (sans couleur)
            $table->addCell(700)->addText($s->salle ?? '-', $fontCell, $centerPar);

            // Nom étudiant
            $cellNom = $table->addCell(1400, ['bgColor' => $filColor[0]]);
            $cellNom->addText($s->student->nom ?? '-', array_merge($fontCell, ['color' => $filColor[1]]));
            if ($binome) {
                $cellNom->addText($binome->nom ?? '', array_merge($fontCell, ['color' => $filColor[1]]));
            }

            // Prénom étudiant
            $cellPrenom = $table->addCell(1400, ['bgColor' => $filColor[0]]);
            $cellPrenom->addText($s->student->prenom ?? '-', array_merge($fontCell, ['color' => $filColor[1]]));
            if ($binome) {
                $cellPrenom->addText($binome->prenom ?? '', array_merge($fontCell, ['color' => $filColor[1]]));
            }

            // Filière
            $cellFil = $table->addCell(700, ['bgColor' => $filColor[0]]);
            $cellFil->addText($fil ?: '-', array_merge($fontCell, ['color' => $filColor[1], 'bold' => true]), $centerPar);
            if ($binome) {
                $binomeFil      = $binome->filiere ?? '';
                $binomeFilColor = $filiereColors[$binomeFil] ?? ['EEEEEE', '333333'];
                $cellFil->addText($binomeFil ?: '-', array_merge($fontCell, ['color' => $binomeFilColor[1], 'bold' => true]), $centerPar);
            }
        }

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

    // Répertoire PV par professeur
    public function pvDirectory()
    {
        $professors = Professor::with([
            'students.soutenance.juries.professor',
            'students.soutenance.student.encadrant',
        ])
        ->whereHas('students.soutenance')
        ->orderBy('nom')
        ->get();

        return view('exports.pv_directory', compact('professors'));
    }

    // Export ZIP de tous les PV d'un encadrant
    public function exportPVZip($professorId)
    {
        $professor = Professor::with([
            'students.soutenance.juries.professor',
            'students.soutenance.student.encadrant',
        ])->findOrFail($professorId);

        $zipName = 'PV_' . $professor->nom . '_' . $professor->prenom . '.zip';
        $zipPath = tempnam(sys_get_temp_dir(), 'pv_zip_') . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Impossible de créer le fichier ZIP.');
        }

        foreach ($professor->students as $student) {
            $soutenance = $student->soutenance;
            if (!$soutenance) {
                continue;
            }

            $soutenanceFull = Soutenance::with([
                'student.encadrant',
                'juries.professor',
            ])->find($soutenance->id);

            if (!$soutenanceFull) {
                continue;
            }

            $pdf = Pdf::loadView('exports.pv', ['soutenance' => $soutenanceFull])
                      ->setPaper('a4', 'portrait');

            $pdfContent = $pdf->output();
            $filename   = 'PV_' . ($student->nom ?? 'etudiant') . '_' . ($student->prenom ?? '') . '.pdf';

            $zip->addFromString($filename, $pdfContent);
        }

        $zip->close();

        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}