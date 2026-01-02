<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.3;
            padding: 15px;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .logo {
            font-size: 20pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .college-info {
            font-size: 8pt;
            color: #666;
            margin-top: 5px;
            line-height: 1.4;
        }

        .invoice-title {
            font-size: 24pt;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 5px;
        }

        .invoice-number {
            font-size: 11pt;
            color: #666;
        }

        .invoice-meta {
            font-size: 9pt;
            margin-top: 10px;
        }

        .info-boxes {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-box {
            display: table-cell;
            width: 50%;
            padding: 15px;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }

        .info-box-right {
            border-left: none;
        }

        .info-box h3 {
            font-size: 11pt;
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }

        .info-box p {
            margin: 5px 0;
            font-size: 9pt;
        }

        .info-box strong {
            color: #2c3e50;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-emise {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .status-payee {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-partiellement_payee {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .status-en_retard {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-brouillon {
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        thead {
            background-color: #3498db;
            color: white;
        }

        th {
            padding: 10px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
        }

        td {
            padding: 8px 10px;
            border-bottom: 1px solid #dee2e6;
            font-size: 9pt;
        }

        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-section {
            margin-top: 20px;
            float: right;
            width: 50%;
        }

        .total-row {
            display: table;
            width: 100%;
            padding: 8px 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .total-label {
            display: table-cell;
            font-weight: bold;
            color: #2c3e50;
        }

        .total-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
        }

        .grand-total {
            background-color: #2c3e50;
            color: white;
            padding: 12px 15px;
            margin-top: 5px;
            font-size: 14pt;
        }

        .payment-summary {
            clear: both;
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-top: 30px;
        }

        .payment-summary h3 {
            color: #856404;
            font-size: 11pt;
            margin-bottom: 10px;
        }

        .payment-summary-grid {
            display: table;
            width: 100%;
        }

        .payment-summary-row {
            display: table-row;
        }

        .payment-summary-label {
            display: table-cell;
            padding: 6px 0;
            font-weight: bold;
            width: 60%;
        }

        .payment-summary-value {
            display: table-cell;
            padding: 6px 0;
            text-align: right;
            font-size: 11pt;
        }

        .amount-due {
            font-size: 16pt;
            color: #e74c3c;
            font-weight: bold;
        }

        .scholarships-section {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 3px;
        }

        .scholarships-section h4 {
            color: #155724;
            font-size: 10pt;
            margin-bottom: 8px;
        }

        .scholarship-item {
            font-size: 9pt;
            color: #155724;
            margin: 4px 0;
        }

        .notes {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 10px 15px;
            margin: 20px 0;
            font-size: 9pt;
        }

        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            font-size: 8pt;
            color: #666;
        }

        .payment-instructions {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 12px;
            margin: 20px 0;
            border-radius: 3px;
        }

        .payment-instructions h4 {
            color: #004085;
            font-size: 10pt;
            margin-bottom: 8px;
        }

        .payment-instructions p {
            font-size: 8pt;
            margin: 4px 0;
            line-height: 1.4;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72pt;
            color: rgba(231, 76, 60, 0.1);
            font-weight: bold;
            z-index: -1;
        }

        @page {
            margin: 15px;
        }
    </style>
</head>
<body>
    {{-- Watermark --}}
    @if($invoice->status === 'annulee')
        <div class="watermark">ANNULÉE</div>
    @elseif($invoice->status === 'payee')
        <div class="watermark" style="color: rgba(46, 204, 113, 0.1);">PAYÉE</div>
    @elseif($invoice->status === 'brouillon')
        <div class="watermark" style="color: rgba(149, 165, 166, 0.1);">BROUILLON</div>
    @endif

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="logo">{{ $college['name'] }}</div>
            <div class="college-info">
                {{ $college['address'] }}<br>
                Tél: {{ $college['phone'] }}<br>
                Email: {{ $college['email'] }}
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-title">FACTURE</div>
            <div class="invoice-number">N° {{ $invoice->invoice_number }}</div>
            <div class="invoice-meta">
                <strong>Date d'émission :</strong> {{ $invoice->issue_date->format('d/m/Y') }}<br>
                <strong>Date d'échéance :</strong> {{ $invoice->due_date->format('d/m/Y') }}<br>
                <strong>Période :</strong> {{ ucfirst(str_replace('_', ' ', $invoice->period)) }}
            </div>
            <span class="status-badge status-{{ $invoice->status }}">
                {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
            </span>
        </div>
    </div>

    {{-- Student and Academic Info --}}
    <div class="info-boxes">
        <div class="info-box">
            <h3>Facturé à</h3>
            <p><strong>Élève :</strong> {{ $student->full_name }}</p>
            <p><strong>Matricule :</strong> {{ $student->matricule }}</p>
            @if($student->currentEnrollment)
            <p><strong>Classe :</strong> {{ $student->currentEnrollment->classRoom->name ?? 'N/A' }}</p>
            @if($student->currentEnrollment->classRoom->cycle)
            <p><strong>Cycle :</strong> {{ $student->currentEnrollment->classRoom->cycle->name }}</p>
            @endif
            @endif
            @if($student->primaryParent)
            <p style="margin-top: 10px;"><strong>Parent/Tuteur :</strong></p>
            <p>{{ $student->primaryParent->name }}</p>
            @if($student->primaryParent->phone)
            <p>Tél: {{ $student->primaryParent->phone }}</p>
            @endif
            @endif
        </div>
        <div class="info-box info-box-right">
            <h3>Informations académiques</h3>
            <p><strong>Année scolaire :</strong> {{ $academicYear->name }}</p>
            <p><strong>Période de facturation :</strong> {{ ucfirst(str_replace('_', ' ', $invoice->period)) }}</p>
            @if($invoice->generated_by)
            <p style="margin-top: 10px;"><strong>Générée par :</strong></p>
            <p>{{ $invoice->generator->name }}</p>
            <p>Le {{ $invoice->generated_at->format('d/m/Y à H:i') }}</p>
            @endif
        </div>
    </div>

    {{-- Fee Types Table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 40%;">Désignation</th>
                <th style="width: 15%;" class="text-center">Qté</th>
                <th style="width: 20%;" class="text-right">Prix unitaire</th>
                <th style="width: 20%;" class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @php $index = 1; @endphp
            @foreach($invoice->feeTypes as $feeType)
            <tr>
                <td class="text-center">{{ $index++ }}</td>
                <td>
                    <strong>{{ $feeType->name }}</strong>
                    @if($feeType->description)
                    <br><small style="color: #666;">{{ $feeType->description }}</small>
                    @endif
                </td>
                <td class="text-center">{{ $feeType->pivot->quantity }}</td>
                <td class="text-right">{{ number_format($feeType->pivot->base_amount / $feeType->pivot->quantity, 0, ',', ' ') }} FCFA</td>
                <td class="text-right">
                    @if($feeType->pivot->discount_amount > 0)
                        <small style="text-decoration: line-through; color: #999;">
                            {{ number_format($feeType->pivot->base_amount, 0, ',', ' ') }} FCFA
                        </small><br>
                    @endif
                    <strong>{{ number_format($feeType->pivot->final_amount, 0, ',', ' ') }} FCFA</strong>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Scholarships if any --}}
    @if($invoice->scholarships && $invoice->scholarships->count() > 0)
    <div class="scholarships-section">
        <h4>🎓 Bourses et réductions appliquées</h4>
        @foreach($invoice->scholarships as $scholarship)
        <div class="scholarship-item">
            ✓ {{ $scholarship->name }} ({{ $scholarship->type_label }})
            @if($scholarship->percentage)
                - {{ $scholarship->formatted_percentage }}
            @else
                - {{ $scholarship->formatted_fixed_amount }}
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Totals --}}
    <div class="totals-section">
        <div class="total-row">
            <div class="total-label">Sous-total :</div>
            <div class="total-value">{{ number_format($invoice->total_amount, 0, ',', ' ') }} FCFA</div>
        </div>
        @if($invoice->discount_amount > 0)
        <div class="total-row" style="color: #27ae60;">
            <div class="total-label">Réductions :</div>
            <div class="total-value">- {{ number_format($invoice->discount_amount, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="total-row">
            <div class="total-label">Total après réductions :</div>
            <div class="total-value">{{ number_format($invoice->total_amount - $invoice->discount_amount, 0, ',', ' ') }} FCFA</div>
        </div>
        @endif
        @if($invoice->paid_amount > 0)
        <div class="total-row" style="color: #3498db;">
            <div class="total-label">Déjà payé :</div>
            <div class="total-value">- {{ number_format($invoice->paid_amount, 0, ',', ' ') }} FCFA</div>
        </div>
        @endif
        <div class="grand-total">
            <div class="total-row" style="border: none;">
                <div class="total-label">MONTANT À PAYER :</div>
                <div class="total-value">{{ number_format($invoice->due_amount, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>

    {{-- Payment Summary --}}
    <div class="payment-summary">
        <h3>📊 Résumé de la situation financière</h3>
        <div class="payment-summary-grid">
            <div class="payment-summary-row">
                <div class="payment-summary-label">Total facturé :</div>
                <div class="payment-summary-value">{{ $invoice->formatted_total_amount }}</div>
            </div>
            @if($invoice->discount_amount > 0)
            <div class="payment-summary-row">
                <div class="payment-summary-label">Réductions accordées :</div>
                <div class="payment-summary-value" style="color: #27ae60;">{{ number_format($invoice->discount_amount, 0, ',', ' ') }} FCFA</div>
            </div>
            @endif
            <div class="payment-summary-row">
                <div class="payment-summary-label">Montant payé :</div>
                <div class="payment-summary-value" style="color: #3498db;">{{ $invoice->formatted_paid_amount }}</div>
            </div>
            <div class="payment-summary-row">
                <div class="payment-summary-label">Taux de paiement :</div>
                <div class="payment-summary-value">{{ $invoice->payment_progress }}%</div>
            </div>
            <div class="payment-summary-row" style="border-top: 2px solid #ffc107; padding-top: 10px; margin-top: 5px;">
                <div class="payment-summary-label" style="font-size: 12pt;">RESTE À PAYER :</div>  
                <div class="payment-summary-value amount-due">{{ $invoice->formatted_due_amount }}</div>
            </div>
        </div>
    </div>

    {{-- Payment Instructions --}}
    @if($invoice->due_amount > 0)
    <div class="payment-instructions">
        <h4>💳 Modalités de paiement</h4>
        <p>• <strong>Date limite :</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>
        <p>• <strong>Méthodes acceptées :</strong> Espèces, Chèque, Virement bancaire, Mobile Money, Carte bancaire</p>
        <p>• <strong>À l'ordre de :</strong> {{ $college['name'] }}</p>
        <p>• Merci de bien vouloir vous présenter à la comptabilité avec cette facture lors du paiement.</p>
    </div>
    @endif

    {{-- Notes --}}
    @if($invoice->notes)
    <div class="notes">
        <strong>Notes :</strong> {{ $invoice->notes }}
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div style="text-align: center;">
            <p><strong>Merci pour votre confiance !</strong></p>
            <p style="margin-top: 5px;">
                En cas de question, veuillez contacter notre service comptabilité au {{ $college['phone'] }}
            </p>
            <p style="margin-top: 10px; font-size: 7pt; color: #999;">
                Document généré automatiquement le {{ $generated_at->format('d/m/Y à H:i') }}
            </p>
            <p style="margin-top: 3px; font-size: 7pt; color: #999;">
                {{ $college['name'] }} - Système de gestion scolaire
            </p>
        </div>
    </div>
</body>
</html>
