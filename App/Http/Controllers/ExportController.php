<?php

namespace App\Http\Controllers;

use App\Models\Soutenance;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
   
    public function index()
    {
        $soutenances = Soutenance::with(['student', 'juries.professor'])->get();
        return view('export', compact('soutenances'));
    }


    public function exportPDF()
    {
        $soutenances = Soutenance::with(['student', 'juries.professor'])->get();

        $pdf = Pdf::loadView('exports.planning', ['soutenances' => $soutenances])
                  ->setPaper('a4', 'landscape');

        return $pdf->download('planning-soutenances.pdf');
    }

    public function exportWord()
    {
        $soutenances = Soutenance::with(['student', 'juries.professor'])->get();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop'   => 600,
            'marginBottom'=> 600,
            'marginLeft'  => 800,
            'marginRight' => 800,
        ]);

        $section->addText(
            'Planning des Soutenances PFE — ' . date('Y'),
            ['bold' => true, 'size' => 16, 'color' => '1a3a5c'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]
        );

        $section->addText(
            'Généré le : ' . now()->format('d/m/Y à H:i'),
            ['size' => 10, 'color' => '6b7280'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 400]
        );

        $styleTable = ['borderSize' => 4, 'borderColor' => 'cccccc', 'cellMargin' => 100];
        $styleHeader = ['bgColor' => '1a3a5c'];
        $fontHeader  = ['bold' => true, 'color' => 'ffffff', 'size' => 10];
        $fontCell    = ['size' => 10];
        $centerPar   = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];

        $table = $section->addTable($styleTable);

    
        $table->addRow(400);
        foreach (['#', 'Étudiant', 'Sujet', 'Date', 'Heure', 'Salle', 'Encadrant', 'Jury'] as $col) {
            $table->addCell(null, $styleHeader)->addText($col, $fontHeader, $centerPar);
        }

        foreach ($soutenances as $i => $s) {
            $jury = $s->juries->map(fn($j) => $j->professor->nom ?? '')->implode(' / ');

            $table->addRow();
            $table->addCell(300)->addText($i + 1, $fontCell, $centerPar);
            $table->addCell(1800)->addText(
                ($s->student->prenom ?? '') . ' ' . ($s->student->nom ?? ''), $fontCell
            );
            $table->addCell(3000)->addText($s->sujet ?? '', $fontCell);
            $table->addCell(900)->addText($s->date ?? '', $fontCell, $centerPar);
            $table->addCell(600)->addText($s->heure ?? '', $fontCell, $centerPar);
            $table->addCell(700)->addText($s->salle ?? '', $fontCell, $centerPar);
            $table->addCell(1800)->addText($s->encadrant->nom ?? '', $fontCell);
            $table->addCell(2000)->addText($jury, $fontCell);
        }

        $filename = 'planning-soutenances.docx';
        $temp     = storage_path('app/' . $filename);

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($temp);

        return response()->download($temp, $filename)->deleteFileAfterSend(true);
    }

    public function exportPV($id)
    {
        $soutenance = Soutenance::with([
            'student',
            'juries.professor',
            'encadrant',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('exports.pv', ['soutenance' => $soutenance])
                  ->setPaper('a4', 'portrait');

        $nom = $soutenance->student->nom ?? 'etudiant';

        return $pdf->download('pv-' . $nom . '.pdf');
    }
}