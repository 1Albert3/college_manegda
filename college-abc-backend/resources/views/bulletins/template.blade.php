<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin - {{ $student->full_name }}</title>
    <style>
        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            line-height: 1.2;
            color: #000;
            background: #fff;
            padding: 5px 20px;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        /* Header Layout */
        .header-table {
            width: 100%;
            border: none;
            margin-bottom: 5px;
        }

        .header-left {
            width: 35%;
            text-align: center;
            vertical-align: top;
        }

        .header-center {
            width: 30%;
            text-align: center;
            vertical-align: middle;
        }

        .header-right {
            width: 35%;
            text-align: center;
            vertical-align: top;
        }

        .school-name {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .motto {
            font-size: 8px;
            font-style: italic;
            margin: 1px 0;
        }

        .bp {
            font-size: 9px;
        }

        .country {
            font-weight: bold;
            font-size: 10px;
        }

        .unity {
            font-size: 8px;
            font-style: italic;
        }

        /* Titles */
        .bulletin-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin: 5px 0;
            font-style: italic;
        }

        .academic-year {
            text-align: right;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Student Identity Box */
        .identity-table {
            width: 100%;
            margin-bottom: 8px;
            border: none;
        }

        .identity-label {
            font-weight: bold;
            width: 80px;
            font-size: 10px;
        }

        .identity-value {
            border-bottom: 1px dotted #000;
            font-size: 10px;
            padding-left: 5px;
        }

        /* Grades Table */
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .grades-table th,
        .grades-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
        }

        .grades-table th {
            font-weight: bold;
            background-color: #f2f2f2;
            font-size: 10px;
        }

        .text-left {
            text-align: left !important;
        }

        .bold {
            font-weight: bold;
        }

        /* Summary Blocks */
        .summary-container {
            width: 100%;
            display: table;
            margin-top: 5px;
        }

        .stat-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stat-table td {
            border: 1px solid #000;
            padding: 3px;
        }

        /* Awards table */
        .awards-table {
            width: 100%;
            border-collapse: collapse;
        }

        .awards-table th {
            border: 1px solid #000;
            background-color: #f2f2f2;
            padding: 3px;
        }

        .awards-table td {
            border: 1px solid #000;
            padding: 3px;
        }

        .checkbox {
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            display: inline-block;
            text-align: center;
            line-height: 14px;
            font-weight: bold;
            font-size: 10px;
        }

        /* Footer / Signatures */
        .footer-signatures {
            width: 100%;
            margin-top: 10px;
        }

        .signature-box {
            width: 50%;
            text-align: center;
            vertical-align: top;
            height: 60px;
        }

        .date-city {
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .nb {
            font-size: 8px;
            border-top: 1px solid #000;
            padding-top: 3px;
            text-align: center;
            margin-top: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="school-name">{{ $school_info['name'] ?? 'WEND-MANEGDA' }}</div>
                    <div class="motto">DISCIPLINE - LABEUR - REUSSITE</div>
                    <div class="bp">BP : 49 OUAGADOUGOU</div>
                </td>
                <td class="header-center">
                    <div style="width: 80px; height: 80px; border: 1px solid #000; margin: 0 auto; line-height: 80px; font-size: 10px;">LOGO</div>
                </td>
                <td class="header-right">
                    <div class="country">BURKINA FASO</div>
                    <div class="unity">Unité - Progrès - Justice</div>
                </td>
            </tr>
        </table>

        <div class="academic-year">Année scolaire : {{ $school_year ?? '2024-2025' }}</div>

        <div class="bulletin-title">
            Bulletin de notes du {{ $semester ?? '1' }}{{ ($semester ?? 1) == 1 ? 'er' : 'ème' }} trimestre
        </div>

        <!-- Identity Box -->
        <table class="identity-table">
            <tr>
                <td width="55%">
                    <table width="100%">
                        <tr>
                            <td class="identity-label">Nom</td>
                            <td class="identity-value bold">{{ $student->last_name ?? $student->nom ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="identity-label">Prénom (s)</td>
                            <td class="identity-value">{{ $student->first_name ?? $student->prenoms ?? $student->full_name }}</td>
                        </tr>
                        <tr>
                            <td class="identity-label">Né le</td>
                            <td class="identity-value">{{ $student->date_naissance ?? $student->date_of_birth ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="identity-label">Matricule</td>
                            <td class="identity-value">{{ $student->matricule }}</td>
                        </tr>
                    </table>
                </td>
                <td width="45%" style="padding-left: 20px;">
                    <table width="100%">
                        <tr>
                            <td class="identity-label">Classe</td>
                            <td class="identity-value bold">{{ $student->currentEnrollment?->classroom?->name ?? '---' }}</td>
                        </tr>
                        <tr>
                            <td class="identity-label">Effectif</td>
                            <td class="identity-value">{{ $averages['class_size'] ?? '---' }}</td>
                        </tr>
                        <tr>
                            <td class="identity-label">Redoublée</td>
                            <td class="identity-value">{{ ($student->is_redoublant ?? false) ? 'Oui' : 'Néant' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Grades Table -->
        <table class="grades-table">
            <thead>
                <tr>
                    <th width="30%">Matières</th>
                    <th width="8%">Coeff.</th>
                    <th width="12%">Moy. / 20</th>
                    <th width="12%">Moy. Pond.</th>
                    <th width="20%">Appréciations</th>
                    <th width="18%">Signatures</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grades as $matiere)
                <tr>
                    <td class="text-left bold">{{ $matiere['subject'] ?? $matiere['nom'] }}</td>
                    <td>{{ $matiere['coefficient'] }}</td>
                    <td>{{ number_format($matiere['average'] ?? $matiere['moyenne'], 2) }}</td>
                    <td>{{ number_format($matiere['weighted_average'] ?? $matiere['points'], 2) }}</td>
                    <td style="font-size: 9px; font-style: italic;">
                        @php
                        $moy = $matiere['average'] ?? $matiere['moyenne'];
                        $m = 'Insuffisant';
                        if($moy >= 16) $m = 'Très bien';
                        elseif($moy >= 14) $m = 'Bien';
                        elseif($moy >= 12) $m = 'Assez bien';
                        elseif($moy >= 10) $m = 'Passable';
                        @endphp
                        {{ $m }}
                    </td>
                    <td></td>
                </tr>
                @endforeach

                <!-- Totals Section -->
                <tr class="bold">
                    <td class="text-left">Total provisoire</td>
                    <td>{{ $averages['total_coefficients'] ?? '---' }}</td>
                    <td>---</td>
                    <td>{{ number_format(($averages['total_weighted_sum'] ?? ($averages['general_average'] * ($averages['total_coefficients'] ?? 1))), 2) }}</td>
                    <td colspan="2" rowspan="6" style="padding: 0; vertical-align: top;">
                        <table class="stat-table" style="border: none;">
                            <tr>
                                <td width="65%">Meilleure moyenne</td>
                                <td width="35%" class="bold">---</td>
                            </tr>
                            <tr>
                                <td>Plus faible moyenne</td>
                                <td class="bold">---</td>
                            </tr>
                            <tr>
                                <td>Moyenne de la classe</td>
                                <td class="bold">---</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="text-left">Discipline</td>
                    <td colspan="2">Retrait de points : 0</td>
                    <td></td>
                </tr>
                <tr class="bold">
                    <td class="text-left">Total définitif</td>
                    <td>{{ $averages['total_coefficients'] ?? '---' }}</td>
                    <td>---</td>
                    <td>{{ number_format(($averages['total_weighted_sum'] ?? ($averages['general_average'] * ($averages['total_coefficients'] ?? 1))), 2) }}</td>
                </tr>
                <tr class="bold">
                    <td class="text-left" style="background-color: #f9f9f9;">Moyenne du {{ $semester ?? 1 }}{{ ($semester ?? 1) == 1 ? 'er' : 'ème' }} trimestre</td>
                    <td colspan="3" style="font-size: 14px; text-align: center; background-color: #f9f9f9;">{{ number_format($averages['general_average'], 2) }}</td>
                </tr>
                <tr>
                    <td class="text-left">Moyenne du trimestre précédent</td>
                    <td colspan="3" style="text-align: center;">---</td>
                </tr>
                <tr class="bold">
                    <td class="text-left" style="background-color: #f9f9f9;">Rang</td>
                    <td colspan="3" style="font-size: 14px; text-align: center; background-color: #f9f9f9;">{{ $averages['rank'] ?? '--' }}{{ ($averages['rank'] ?? 0) == 1 ? 'er' : 'ème' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Distinctions and Council decision -->
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 10px;">
            <tr>
                <td width="42%" style="vertical-align: top;">
                    <table class="awards-table">
                        <tr>
                            <th colspan="2">Distinction (s) ou sanction</th>
                        </tr>
                        <tr>
                            <td width="75%">Tableau d'honneur</td>
                            <td width="25%" align="center">
                                <span class="checkbox">{{ $averages['general_average'] >= 12 ? 'X' : '' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td>Encouragement</td>
                            <td align="center">
                                <span class="checkbox">{{ ($averages['general_average'] >= 14 && $averages['general_average'] < 16) ? 'X' : '' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td>Félicitations</td>
                            <td align="center">
                                <span class="checkbox">{{ $averages['general_average'] >= 16 ? 'X' : '' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td>Avertissement</td>
                            <td align="center"><span class="checkbox"></span></td>
                        </tr>
                        <tr>
                            <td>Blâme</td>
                            <td align="center"><span class="checkbox"></span></td>
                        </tr>
                    </table>
                </td>
                <td width="58%" style="vertical-align: top; padding-left: 10px;">
                    <table class="stat-table" style="height: 100%;">
                        <tr>
                            <th width="30%">Conduite</th>
                            <th width="70%">Décision du conseil de classe</th>
                        </tr>
                        <tr>
                            <td height="113" align="center" style="font-size: 14px; font-weight: bold;">
                                Bonne
                            </td>
                            <td style="vertical-align: top; padding: 10px; font-style: italic; font-size: 11px;">
                                Travail satisfaisant.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Footer Signatures -->
        <div class="footer-signatures">
            <div class="date-city">
                Ouagadougou, le {{ date('d/m/Y') }}
            </div>
            <table width="100%">
                <tr>
                    <td class="signature-box">
                        <div class="bold" style="text-decoration: underline;">Le Censeur</div>
                    </td>
                    <td class="signature-box">
                        <div class="bold" style="text-decoration: underline;">Le Proviseur</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="nb">
            NB: Il ne sera délivré qu'un seul bulletin. Prenez bien soin.
        </div>
    </div>
</body>

</html>