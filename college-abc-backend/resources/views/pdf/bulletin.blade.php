<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Bulletin - {{ $student->full_name ?? $student->nom }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0.5cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            width: 100%;
        }

        .bulletin {
            width: 100%;
            max-width: 100%;
            border: 2px solid #000;
            padding: 10px;
            overflow: hidden;
            word-wrap: break-word;
        }

        /* En-tête officiel à 3 colonnes */
        .header {
            width: 100%;
            table-layout: fixed;
            margin-bottom: 10px;
        }

        .header td {
            vertical-align: top;
            text-align: center;
        }

        .header .left {
            width: 35%;
        }

        .header .center {
            width: 30%;
        }

        .header .right {
            width: 35%;
        }

        .school-name {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .motto {
            font-size: 8px;
            font-style: italic;
        }

        .logo {
            width: 70px;
            height: 70px;
            border: 1px solid #000;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
        }

        .country {
            font-size: 11px;
            font-weight: bold;
        }

        /* Année scolaire */
        .year-line {
            text-align: right;
            font-weight: bold;
            font-size: 11px;
            margin: 8px 0;
        }

        /* Titre du bulletin */
        .title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin: 15px 0;
        }

        /* Bloc identité élève */
        .identity {
            width: 100%;
            table-layout: fixed;
            border: 1px solid #000;
            margin-bottom: 15px;
        }

        .identity td {
            padding: 6px 8px;
            overflow: hidden;
        }

        .identity .lbl {
            font-weight: bold;
            width: 15%;
        }

        .identity .val {
            width: 35%;
            border-bottom: 1px dotted #000;
        }

        /* Tableau des notes */
        .notes {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .notes th,
        .notes td {
            border: 1px solid #000;
            padding: 5px 4px;
            text-align: center;
        }

        .notes th {
            background-color: #e9e9e9;
            font-weight: bold;
        }

        .notes .matiere {
            text-align: left;
            font-weight: bold;
        }

        .notes .total-row {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .notes .total-row .moyenne {
            font-size: 12px;
        }

        /* Bloc récapitulatif */
        .recap {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .recap td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }

        .recap .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }

        .recap .line {
            margin-bottom: 3px;
        }

        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            text-align: center;
            line-height: 12px;
            font-size: 9px;
            margin-right: 5px;
        }

        /* Signatures */
        .signatures {
            width: 100%;
            margin-top: 10px;
        }

        .signatures .date-line {
            text-align: right;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .signatures .sig-table {
            width: 100%;
        }

        .signatures .sig-table td {
            width: 50%;
            text-align: center;
            padding-top: 5px;
            height: 80px;
            vertical-align: top;
        }

        /* Note de bas de page */
        .footer-note {
            border-top: 1px solid #000;
            text-align: center;
            font-size: 8px;
            padding-top: 5px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="bulletin">
        <!-- EN-TÊTE OFFICIEL -->
        <table class="header">
            <tr>
                <td class="left">
                    <div class="school-name">{{ $etablissement['nom'] ?? 'WEND-MANEGDA' }}</div>
                    <div class="motto">DISCIPLINE - LABEUR - REUSSITE</div>
                    <div>BP : 50 OUAGADOUGOU</div>
                </td>
                <td class="center">
                    @if(isset($etablissement['logo']) && $etablissement['logo'])
                    <img src="{{ public_path('storage/'.$etablissement['logo']) }}" style="max-height: 70px;">
                    @else
                    <div class="logo">LOGO</div>
                    @endif
                </td>
                <td class="right">
                    <div class="country">BURKINA FASO</div>
                    <div class="motto">Unité - Progrès - Justice</div>
                </td>
            </tr>
        </table>

        <!-- ANNÉE SCOLAIRE -->
        <div class="year-line">Année scolaire : {{ $school_year }}</div>

        <!-- TITRE -->
        <div class="title">
            Bulletin de notes du {{ $bulletin->trimestre }}{{ $bulletin->trimestre == 1 ? 'er' : 'ème' }} trimestre
        </div>

        <!-- IDENTITÉ DE L'ÉLÈVE -->
        <table class="identity">
            <tr>
                <td class="lbl">Nom :</td>
                <td class="val"><strong>{{ $student->last_name ?? $student->nom }}</strong></td>
                <td class="lbl">Classe :</td>
                <td class="val"><strong>{{ $class->nom }}</strong></td>
            </tr>
            <tr>
                <td class="lbl">Prénom(s) :</td>
                <td class="val">{{ $student->first_name ?? $student->prenoms }}</td>
                <td class="lbl">Effectif :</td>
                <td class="val">{{ $bulletin->effectif_classe ?? '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Né(e) le :</td>
                <td class="val">{{ $student->date_naissance ? (is_string($student->date_naissance) ? $student->date_naissance : $student->date_naissance->format('d/m/Y')) : '-' }}</td>
                <td class="lbl">Matricule :</td>
                <td class="val">{{ $student->matricule }}</td>
            </tr>
        </table>

        <!-- TABLEAU DES NOTES -->
        <table class="notes">
            <thead>
                <tr>
                    <th width="35%">MATIÈRES</th>
                    <th width="10%">COEF</th>
                    <th width="15%">MOY / 20</th>
                    <th width="15%">MOY. POND.</th>
                    <th width="25%">APPRÉCIATIONS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matieres as $m)
                <tr>
                    <td class="matiere">{{ $m['nom'] }}</td>
                    <td>{{ $m['coefficient'] }}</td>
                    <td>{{ number_format($m['moyenne'], 2) }}</td>
                    <td>{{ number_format($m['points'], 2) }}</td>
                    <td style="font-size: 8px; font-style: italic;">
                        @php
                        $moy = $m['moyenne'];
                        if($moy >= 16) $appreciation = 'Très Bien';
                        elseif($moy >= 14) $appreciation = 'Bien';
                        elseif($moy >= 12) $appreciation = 'Assez Bien';
                        elseif($moy >= 10) $appreciation = 'Passable';
                        else $appreciation = 'Insuffisant';
                        @endphp
                        {{ $appreciation }}
                    </td>
                </tr>
                @endforeach
                <!-- Ligne de total -->
                <tr class="total-row">
                    <td class="matiere">TOTAL / MOYENNE GÉNÉRALE</td>
                    <td>{{ $bulletin->total_coefficients }}</td>
                    <td class="moyenne">{{ number_format($bulletin->moyenne_generale, 2) }}</td>
                    <td>{{ number_format($bulletin->total_points, 2) }}</td>
                    <td>Rang : {{ $bulletin->rang }}{{ $bulletin->rang == 1 ? 'er' : 'ème' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- RÉCAPITULATIF : Stats + Distinctions + Conduite -->
        <table class="recap">
            <tr>
                <td width="33%">
                    <div class="section-title">Résultats de la classe</div>
                    <div class="line">Moy. Premier : <strong>{{ number_format($bulletin->moyenne_premier, 2) }}</strong></div>
                    <div class="line">Moy. Dernier : <strong>{{ number_format($bulletin->moyenne_dernier, 2) }}</strong></div>
                    <div class="line">Moy. Classe : <strong>{{ number_format($bulletin->moyenne_classe, 2) }}</strong></div>
                </td>
                <td width="34%">
                    <div class="section-title">Distinctions</div>
                    <div class="line"><span class="checkbox">{{ $bulletin->moyenne_generale >= 12 ? 'X' : '' }}</span> Tableau d'Honneur</div>
                    <div class="line"><span class="checkbox">{{ $bulletin->moyenne_generale >= 14 ? 'X' : '' }}</span> Encouragements</div>
                    <div class="line"><span class="checkbox">{{ $bulletin->moyenne_generale >= 16 ? 'X' : '' }}</span> Félicitations</div>
                </td>
                <td width="33%">
                    <div class="section-title">Assiduité & Conduite</div>
                    <div class="line">Abs. Justifiées : {{ $bulletin->absences_justifiees ?? 0 }}</div>
                    <div class="line">Abs. Non Just. : {{ $bulletin->absences_non_justifiees ?? 0 }}</div>
                    <div style="margin-top: 5px; font-style: italic;">{{ $bulletin->appreciation_generale ?? 'Travail satisfaisant.' }}</div>
                </td>
            </tr>
        </table>

        <!-- SIGNATURES -->
        <div class="signatures">
            <div class="date-line">Ouagadougou, le {{ date('d/m/Y') }}</div>
            <table class="sig-table">
                <tr>
                    <td><u>Le Parent d'élève</u></td>
                    <td><u>Le Chef d'Établissement</u></td>
                </tr>
            </table>
        </div>

        <!-- NOTE DE BAS DE PAGE -->
        <div class="footer-note">
            NB : Il ne sera délivré qu'un seul exemplaire du présent bulletin. Veuillez en prendre soin.
        </div>
    </div>
</body>

</html>