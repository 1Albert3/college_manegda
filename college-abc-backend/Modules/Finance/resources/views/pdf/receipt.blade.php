<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - {{ $payment->receipt_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            color: #333;
            line-height: 1.4;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
        }

        .logo {
            font-size: 24pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .college-info {
            font-size: 9pt;
            color: #666;
            margin-top: 5px;
        }

        .receipt-title {
            background-color: #3498db;
            color: white;
            padding: 12px;
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 20px 0;
            border-radius: 3px;
        }

        .receipt-number {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 20px;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-section h3 {
            background-color: #ecf0f1;
            padding: 8px 10px;
            font-size: 11pt;
            color: #2c3e50;
            margin-bottom: 10px;
            border-left: 4px solid #3498db;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            padding: 6px 10px;
            font-weight: bold;
            width: 40%;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .info-value {
            display: table-cell;
            padding: 6px 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .amount-section {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }

        .amount-label {
            font-size: 10pt;
            color: #856404;
            margin-bottom: 5px;
        }

        .amount-value {
            font-size: 20pt;
            font-weight: bold;
            color: #856404;
        }

        .payment-method {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 10px;
            margin: 15px 0;
            border-radius: 3px;
        }

        .notes {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 10px;
            margin: 15px 0;
            font-style: italic;
            font-size: 9pt;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }

        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 10px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 9pt;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72pt;
            color: rgba(52, 152, 219, 0.1);
            font-weight: bold;
            z-index: -1;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
        }

        .status-valide {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-attente {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        @page {
            margin: 20px;
        }
    </style>
</head>
<body>
    {{-- Watermark --}}
    @if($payment->status === 'annule')
        <div class="watermark">ANNULÉ</div>
    @endif

    {{-- Header --}}
    <div class="header">
        <div class="logo">{{ $college['name'] }}</div>
        <div class="college-info">
            {{ $college['address'] }}<br>
            Tél: {{ $college['phone'] }} | Email: {{ $college['email'] }}
        </div>
    </div>

    {{-- Receipt Title --}}
    <div class="receipt-title">
        REÇU DE PAIEMENT
    </div>

    <div class="receipt-number">
        N° {{ $payment->receipt_number }}
    </div>

    {{-- Student Information --}}
    <div class="info-section">
        <h3>Informations de l'élève</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom complet :</div>
                <div class="info-value">{{ $student->full_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Matricule :</div>
                <div class="info-value">{{ $student->matricule }}</div>
            </div>
            @if($student->currentEnrollment)
            <div class="info-row">
                <div class="info-label">Classe :</div>
                <div class="info-value">{{ $student->currentEnrollment->classRoom->name ?? 'N/A' }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Année académique :</div>
                <div class="info-value">{{ $academicYear->name }}</div>
            </div>
        </div>
    </div>

    {{-- Payment Information --}}
    <div class="info-section">
        <h3>Détails du paiement</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Type de frais :</div>
                <div class="info-value">{{ $feeType->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de paiement :</div>
                <div class="info-value">{{ $payment->payment_date->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Payé par :</div>
                <div class="info-value">{{ $payment->payer_name ?? 'Parent/Tuteur' }}</div>
            </div>
            @if($payment->reference)
            <div class="info-row">
                <div class="info-label">Référence :</div>
                <div class="info-value">{{ $payment->reference }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Payment Method --}}
    <div class="payment-method">
        <strong>Méthode de paiement :</strong> {{ $payment->payment_method_label }}
    </div>

    {{-- Amount --}}
    <div class="amount-section">
        <div class="amount-label">Montant payé</div>
        <div class="amount-value">{{ $payment->formatted_amount }}</div>
    </div>

    {{-- Status --}}
    <div style="text-align: center; margin: 15px 0;">
        <span class="status-badge status-{{ $payment->status }}">
            {{ $payment->status_label }}
        </span>
        @if($payment->status === 'valide' && $payment->validator)
            <div style="margin-top: 10px; font-size: 9pt; color: #666;">
                Validé par {{ $payment->validator->name }} le {{ $payment->validated_at->format('d/m/Y à H:i') }}
            </div>
        @endif
    </div>

    {{-- Notes --}}
    @if($payment->notes)
    <div class="notes">
        <strong>Notes :</strong> {{ $payment->notes }}
    </div>
    @endif

    {{-- Signature Section --}}
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                Signature du payeur
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                Signature du caissier
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>Ce reçu fait foi de paiement. Veuillez le conserver précieusement.</p>
        <p style="margin-top: 5px; font-size: 8pt;">
            Document généré le {{ $generated_at->format('d/m/Y à H:i') }}
        </p>
        <p style="margin-top: 5px; font-size: 8pt; color: #999;">
            {{ $college['name'] }} - Gestion scolaire informatisée
        </p>
    </div>
</body>
</html>
