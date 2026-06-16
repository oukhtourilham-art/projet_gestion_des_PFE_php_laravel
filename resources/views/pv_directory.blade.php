@extends('layouts.app')

@section('title', 'Répertoire des PV')

@section('content')

<h2>Répertoire des Procès-Verbaux par Encadrant</h2>
<p style="color: gray; font-size: 14px;">
    Chaque encadrant dispose de son propre dossier contenant les PV de ses étudiants,
    téléchargeables individuellement ou en lot (ZIP).
</p>

<hr>

@if($professors->isEmpty())
    <p style="color: #888;">Aucun encadrant avec des soutenances planifiées.</p>
@endif

@foreach($professors as $professor)

    @php
        $studentsWithSoutenance = $professor->students->filter(fn($s) => $s->soutenance !== null);
    @endphp

    @if($studentsWithSoutenance->isEmpty())
        @continue
    @endif

    <div style="margin-bottom: 30px; border: 1px solid #ccc; border-radius: 4px; overflow: hidden;">

        {{-- En-tête du dossier professeur --}}
        <div style="background: #1a3a5c; color: white; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong style="font-size: 15px;">
                    📁 Pr. {{ $professor->nom }} {{ $professor->prenom }}
                </strong>
                <span style="margin-left: 12px; font-size: 13px; opacity: 0.85;">
                    {{ $professor->discipline ?? '' }}
                    — {{ $studentsWithSoutenance->count() }} étudiant(s)
                </span>
            </div>
            <a href="{{ route('export.pv.zip', ['professorId' => $professor->id]) }}"
               style="background: white; color: #1a3a5c; padding: 5px 14px; border-radius: 3px;
                      font-size: 13px; font-weight: bold; text-decoration: none;">
                ⬇ Télécharger tous (ZIP)
            </a>
        </div>

        {{-- Liste des étudiants --}}
        <table border="1" cellpadding="6" cellspacing="0"
               style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #eef2f7;">
                    <th style="text-align: left; padding: 8px;">Étudiant</th>
                    <th style="text-align: left; padding: 8px;">Filière</th>
                    <th style="text-align: left; padding: 8px;">Date soutenance</th>
                    <th style="text-align: left; padding: 8px;">Salle</th>
                    <th style="text-align: center; padding: 8px;">PV</th>
                </tr>
            </thead>
            <tbody>
                @foreach($studentsWithSoutenance as $student)
                <tr>
                    <td style="padding: 7px 8px;">
                        {{ $student->prenom }} {{ $student->nom }}
                    </td>
                    <td style="padding: 7px 8px;">{{ $student->filiere ?? '-' }}</td>
                    <td style="padding: 7px 8px;">
                        {{ $student->soutenance->date_soutenance ?? 'Non planifiée' }}
                    </td>
                    <td style="padding: 7px 8px;">{{ $student->soutenance->salle ?? '-' }}</td>
                    <td style="text-align: center; padding: 7px 8px;">
                        <a href="{{ route('export.pv', ['id' => $student->soutenance->id, 'format' => 'pdf']) }}"
                           style="color: #1a3a5c; font-weight: bold;">PDF</a>
                        &nbsp;|&nbsp;
                        <a href="{{ route('export.pv', ['id' => $student->soutenance->id, 'format' => 'word']) }}"
                           style="color: #1a3a5c; font-weight: bold;">Word</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>

@endforeach

@endsection