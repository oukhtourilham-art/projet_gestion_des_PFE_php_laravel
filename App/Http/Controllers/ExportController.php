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

        $student   = $soutenance->student;
        $encadrant = $student->encadrant;

        // Président = encadrant de l'étudiant
        $president = $encadrant
            ? trim($encadrant->nom . ' ' . $encadrant->prenom)
            : null;

        // Rapporteurs = membres du jury (juries[0] et juries[1])
        $rap1Prof = $soutenance->juries->get(0)?->professor;
        $rap2Prof = $soutenance->juries->get(1)?->professor;
        $rapporteur1 = $rap1Prof ? trim($rap1Prof->nom . ' ' . $rap1Prof->prenom) : null;
        $rapporteur2 = $rap2Prof ? trim($rap2Prof->nom . ' ' . $rap2Prof->prenom) : null;

        $nomFichier = 'Fiche_PFE_' . $student->nom . '_' . $student->prenom;

        // ── PDF ──
        if ($format === 'pdf') {
            $logoU = storage_path('app/public/logos/logo_universite.png');
            $logoE = storage_path('app/public/logos/logo_ensah.png');

            $data = [
                'logo_universite'        => file_exists($logoU) ? base64_encode(file_get_contents($logoU)) : '',
                'logo_ensah'             => file_exists($logoE) ? base64_encode(file_get_contents($logoE)) : '',
                'annee_universitaire'    => '2025-2026',
                'nom_prenom_etudiant'    => trim($student->nom . ' ' . $student->prenom),
                'filiere'                => $student->filiere,
                'intitule_rapport'       => $student->sujet,
                'encadrant_interne'      => $president,
                // Jury : président = encadrant, rapporteurs = membres jury
                'jury_president'         => $president,
                'jury_rapporteur1'       => $rapporteur1,
                'jury_rapporteur2'       => $rapporteur2,
                // Signatures : prénom uniquement
                'jury_president_court'   => $encadrant?->prenom,
                'jury_rapporteur1_court' => $rap1Prof?->prenom,
                'jury_rapporteur2_court' => $rap2Prof?->prenom,
                'note_contenu'           => null,
                'note_memoire'           => null,
                'note_soutenance'        => null,
                'moyenne'                => null,
                'date_soutenance'        => $soutenance->date_soutenance
                    ? \Carbon\Carbon::parse($soutenance->date_soutenance)->format('d/m/Y')
                    : null,
            ];

            $pdf = Pdf::loadView('exports.pv', $data)->setPaper('A4', 'portrait');
            return $pdf->download($nomFichier . '.pdf');
        }

        // ── WORD ──
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'marginTop'    => 720,   // 1.27cm
            'marginBottom' => 720,
            'marginLeft'   => 900,   // 1.6cm
            'marginRight'  => 900,
        ]);

        $bold      = ['bold' => true];
        $boldU     = ['bold' => true, 'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE];
        $center    = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];
        $spAfter   = ['spaceAfter' => 80];

        // ── En-tête : logos + texte ──
        $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $table->addRow();
        $cellL = $table->addCell(1500);
        $logoUPath = storage_path('app/public/logos/logo_universite.png');
        if (file_exists($logoUPath)) {
            $cellL->addImage($logoUPath, ['width' => 55, 'height' => 55]);
        }
        $cellC = $table->addCell(6500);
        $cellC->addText('UNIVERSITE ABDELMALEK ESSAADI', ['bold' => true, 'size' => 13], $center);
        $cellC->addText("Ecole Nationale des Sciences Appliquées d'Al-Hoceima - Maroc", ['size' => 10], $center);
        $cellR = $table->addCell(1500);
        $logoEPath = storage_path('app/public/logos/logo_ensah.png');
        if (file_exists($logoEPath)) {
            $cellR->addImage($logoEPath, ['width' => 55, 'height' => 55, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        }

        $section->addTextBreak(1);

        // ── Titres ──
        $section->addText('Département de Mathématiques et Informatique', ['bold' => true, 'size' => 13], $center);
        $section->addText("Fiche d'évaluation du Projet de Fin d'Étude", ['size' => 12], $center);
        $section->addText('Année Universitaire : 2025-2026', ['size' => 12], array_merge($center, ['spaceAfter' => 200]));

        // ── Nom étudiant ──
        $section->addText("Nom - Prénom de l'élève ingénieur :", $boldU, $spAfter);
        $section->addText('• ' . (trim($student->nom . ' ' . $student->prenom) ?: '…………………………………………'), ['size' => 12], ['spaceAfter' => 120, 'indentation' => ['left' => 360]]);

        // ── Filière : afficher la filière de l'étudiant soulignée ──
        $filiereMap = [
            'DATA' => 'Ingénierie des Données',
            'GI'   => 'Génie Informatique',
            'TDIA' => 'Technologies et Développement IA',
        ];
        $filiere = strtoupper($student->filiere ?? '');

        $filierePara = $section->addTextRun(['spaceAfter' => 160]);
        $filierePara->addText('Filière : ', $boldU);
        $filierePara->addText('     ');

        $knownFilieres = array_keys($filiereMap);
        if (!in_array($filiere, $knownFilieres)) {
            // Filière inconnue → afficher brut souligné
            $filierePara->addText($filiere, $boldU);
        } else {
            foreach ($filiereMap as $code => $label) {
                if ($code === $filiere) {
                    $filierePara->addText($label, ['bold' => true, 'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE]);
                } else {
                    $filierePara->addText($label);
                }
                if ($code !== array_key_last($filiereMap)) {
                    $filierePara->addText('          ');
                }
            }
        }

        // ── Sujet ──
        $section->addText('Intitulé du rapport :', $boldU, $spAfter);
        $section->addText('• ' . ($student->sujet ?: '………………………………………………………………………'), ['size' => 12], ['spaceAfter' => 120, 'indentation' => ['left' => 360]]);

        // ── Encadrant ──
        $section->addText("L'encadrant (e) interne :", $boldU, $spAfter);
        $section->addText('• Pr.  ' . ($president ?: '………………………………………………………………………'), ['size' => 12], ['spaceAfter' => 160, 'indentation' => ['left' => 360]]);

        // ── Jury ──
        $section->addText('Membres du jury :', $boldU, $spAfter);
        $juryTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'width' => 9000, 'unit' => 'dxa']);
        $juryTable->addRow();
        $juryTable->addCell(9000)->addText(
            'Pr.       ' . ($president ?: '…………………………………………………') . '                    Président',
            ['size' => 12]
        );
        $juryTable->addRow();
        $cell2 = $juryTable->addCell(9000);
        $rapRun = $cell2->addTextRun();
        $rapRun->addText('Pr.       ' . ($rapporteur1 ?: '…………………………………………………') . '                 Rapporteur', ['size' => 12]);
        $rapRun->addText('                                        ');
        $rapRun->addText('Pr.       ' . ($rapporteur2 ?: '…………………………………………………') . '                 Rapporteur', ['size' => 12]);

        $section->addTextBreak(1);

        // ── Notes ──
        $section->addText('Note du Contenu *(En prenant en compte l\'appréciation de l\'entreprise)*', $boldU, $spAfter);
        $section->addText('C  =  ……………………', ['size' => 12], $spAfter);

        $section->addText('Note du Mémoire', $boldU, $spAfter);
        $section->addText('M  =  ……………………', ['size' => 12], $spAfter);

        $section->addText('Note de la Soutenance', $boldU, $spAfter);
        $section->addText('S  =  ……………………', ['size' => 12], ['spaceAfter' => 160]);

        // ── Tableau moyenne ──
        $moyTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'width' => 9000, 'unit' => 'dxa']);
        $moyTable->addRow();
        $moyTable->addCell(9000)->addText('MOYENNE', ['bold' => true, 'size' => 12], $center);
        $moyTable->addRow();
        $moyTable->addCell(9000)->addText('Moyenne   = C * 0,5 + M * 0,2 + S * 0,3  =  ……………………', ['bold' => true, 'size' => 12]);

        $section->addTextBreak(1);

        // ── Date ──
        $dateStr = $soutenance->date_soutenance
            ? \Carbon\Carbon::parse($soutenance->date_soutenance)->format('d/m/Y')
            : '……………………';
        $section->addText('Le :        ' . $dateStr, ['size' => 12], ['spaceAfter' => 200]);

        // ── Signatures ──
        $section->addText('Signature des membres du jury :', ['size' => 12], ['spaceAfter' => 600]);
        $sigTable = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'width' => 9000, 'unit' => 'dxa']);
        $sigTable->addRow();
        $sigTable->addCell(3000)->addText('Pr.     ' . ($encadrant?->prenom ?: '……………………'), ['size' => 12], $center);
        $sigTable->addCell(3000)->addText('Pr.     ' . ($rap1Prof?->prenom ?: '……………………'), ['size' => 12], $center);
        $sigTable->addCell(3000)->addText('Pr.     ' . ($rap2Prof?->prenom ?: '……………………'), ['size' => 12], $center);

        // ── Téléchargement Word ──
        $tmpPath = storage_path('app/temp_pv_' . $id . '.docx');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tmpPath);

        return response()->download($tmpPath, $nomFichier . '.docx')->deleteFileAfterSend(true);
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

        // Charger la soutenance complète avec les relations pour le blade
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