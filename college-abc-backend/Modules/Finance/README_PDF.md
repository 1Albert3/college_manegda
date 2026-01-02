# Module Finance - Documentation Templates PDF

## Vue d'ensemble

Le module Finance génère automatiquement des documents PDF professionnels pour :

-   **Reçus de paiement** (receipt.blade.php)
-   **Factures** (invoice.blade.php)

Les PDF sont générés avec **DomPDF** et utilisent des templates Blade.

---

## 📄 Template 1 : Reçu de paiement (receipt.blade.php)

### Format

-   **Taille** : A5 portrait
-   **Police** : DejaVu Sans (compatible DomPDF)
-   **Couleur principale** : Bleu (#3498db)

### Structure

#### 1. Header

-   Logo et nom du collège
-   Adresse, téléphone, email

#### 2. Titre

-   Bandeau bleu "REÇU DE PAIEMENT"
-   Numéro de reçu en rouge (ex: REC2025000001)

#### 3. Informations élève

-   Nom complet
-   Matricule
-   Classe actuelle
-   Année académique

#### 4. Détails du paiement

-   Type de frais payé
-   Date du paiement
-   Nom du payeur
-   Référence (chèque, virement, etc.)

#### 5. Méthode de paiement

-   Badge avec la méthode utilisée

#### 6. Montant

-   Grande zone jaune avec le montant payé en grand

#### 7. Statut

-   Badge de statut (Validé / En attente / Annulé)
-   Informations de validation (par qui, quand)

#### 8. Notes

-   Notes additionnelles si présentes

#### 9. Signatures

-   Espace pour signature du payeur
-   Espace pour signature du caissier

#### 10. Footer

-   Message de conservation
-   Date de génération
-   Mention système

### Variables Blade disponibles

```php
$payment        // Entité Payment
$student        // Entité Student
$feeType        // Entité FeeType
$academicYear   // Entité AcademicYear
$college        // Array avec infos collège
$generated_at   // DateTime de génération
```

### Exemples d'utilisation dans les services

```php
// Dans PaymentService::generateReceipt()
$data = [
    'payment' => $payment,
    'student' => $payment->student,
    'feeType' => $payment->feeType,
    'academicYear' => $payment->academicYear,
    'college' => [
        'name' => config('app.name'),
        'address' => config('college.address'),
        'phone' => config('college.phone'),
        'email' => config('college.email'),
    ],
    'generated_at' => now(),
];

$pdf = Pdf::loadView('finance::pdf.receipt', $data);
$pdf->setPaper('a5', 'portrait');
return $pdf->stream("recu_{$payment->receipt_number}.pdf");
```

### Watermarks

Le template affiche automatiquement un watermark "ANNULÉ" en diagonale si le paiement est annulé.

---

## 📄 Template 2 : Facture (invoice.blade.php)

### Format

-   **Taille** : A4 portrait
-   **Police** : DejaVu Sans
-   **Couleur principale** : Bleu (#3498db)
-   **Couleur accent** : Rouge (#e74c3c)

### Structure

#### 1. Header (deux colonnes)

-   **Gauche** : Logo et infos collège
-   **Droite** : FACTURE, numéro, dates, statut

#### 2. Informations détaillées (deux boîtes)

-   **Gauche** : Infos élève + parent/tuteur
-   **Droite** : Infos académiques + générateur

#### 3. Tableau des frais

-   Colonnes : #, Désignation, Quantité, Prix unitaire, Montant
-   Affichage des réductions par ligne si applicable
-   Descriptions des frais

#### 4. Section bourses (si applicable)

-   Liste des bourses/réductions appliquées
-   Type et montant/pourcentage

#### 5. Totaux (colonne droite)

-   Sous-total
-   Réductions (en vert)
-   Total après réductions
-   Déjà payé (en bleu)
-   **MONTANT À PAYER** (bandeau noir)

#### 6. Résumé financier (grande zone jaune)

-   Total facturé
-   Réductions accordées
-   Montant payé
-   Taux de paiement (%)
-   **RESTE À PAYER** (en grand, rouge)

#### 7. Modalités de paiement (si reste à payer)

-   Date limite
-   Méthodes acceptées
-   Instructions

#### 8. Notes

-   Notes additionnelles si présentes

#### 9. Footer

-   Message de remerciement
-   Contact comptabilité
-   Date de génération

### Variables Blade disponibles

```php
$invoice        // Entité Invoice
$student        // Entité Student
$academicYear   // Entité AcademicYear
$invoice->feeTypes  // Collection de FeeType avec pivot
$invoice->scholarships  // Collection de Scholarship
$college        // Array avec infos collège
$generated_at   // DateTime de génération
```

### Exemples d'utilisation dans les services

```php
// Dans InvoiceService::generateInvoicePDF()
$invoice->load(['student', 'academicYear', 'feeTypes', 'scholarships']);

$data = [
    'invoice' => $invoice,
    'student' => $invoice->student,
    'academicYear' => $invoice->academicYear,
    'college' => [
        'name' => config('app.name', 'Collège Wend-Manegda'),
        'address' => config('college.address'),
        'phone' => config('college.phone'),
        'email' => config('college.email'),
    ],
    'generated_at' => now(),
];

$pdf = Pdf::loadView('finance::pdf.invoice', $data);
$pdf->setPaper('a4', 'portrait');
return $pdf->stream("facture_{$invoice->invoice_number}.pdf");
```

### Watermarks

Le template affiche automatiquement des watermarks selon le statut :

-   **ANNULÉE** (rouge) si status = annulee
-   **PAYÉE** (vert) si status = payee
-   **BROUILLON** (gris) si status = brouillon

---

## 🎨 Design et mise en page

### Couleurs utilisées

| Couleur        | Code    | Usage                          |
| -------------- | ------- | ------------------------------ |
| Bleu principal | #3498db | Headers, titres, badges        |
| Rouge accent   | #e74c3c | Alertes, montants dus, facture |
| Vert           | #27ae60 | Réductions, payé               |
| Jaune          | #ffc107 | Zones importantes (montants)   |
| Gris foncé     | #2c3e50 | Textes, totaux                 |

### Badges de statut

Les statuts sont affichés avec des badges colorés :

**Paiements** :

-   Validé : Vert
-   En attente : Jaune
-   Annulé : Rouge

**Factures** :

-   Émise : Bleu clair
-   Payée : Vert
-   Partiellement payée : Jaune
-   En retard : Rouge
-   Brouillon : Gris
-   Annulée : Rouge

### Compatibilité DomPDF

#### ✅ Supporté

-   Tables (avec `border-collapse: collapse`)
-   Couleurs (hex, rgb)
-   Borders, padding, margins
-   Background colors
-   Text-align, font-weight, font-size
-   Display: table, table-cell, table-row
-   Page breaks

#### ❌ Non supporté

-   Flexbox
-   Grid
-   CSS externe (lien)
-   JavaScript
-   Web fonts (sauf DejaVu)
-   Transform (limité)
-   Box-shadow
-   Gradients avancés

#### 💡 Astuce : Display table

Pour créer des layouts en colonnes compatibles DomPDF :

```css
.row {
    display: table;
    width: 100%;
}

.col {
    display: table-cell;
    width: 50%;
}
```

---

## 📋 Configuration

### Polices

DomPDF supporte nativement **DejaVu Sans**. Pour d'autres polices :

1. Convertir la police en format compatible
2. Placer dans `storage/fonts/`
3. Configurer dompdf dans `config/dompdf.php`

### Taille de page

```php
// A4 portrait (défaut facture)
$pdf->setPaper('a4', 'portrait');

// A5 portrait (défaut reçu)
$pdf->setPaper('a5', 'portrait');

// Custom
$pdf->setPaper([0, 0, 595, 842], 'portrait'); // A4 en points
```

### Marges

Configurées dans le CSS via `@page` :

```css
@page {
    margin: 15px;
}
```

---

## 🧪 Tester les templates

### En développement

```php
// Dans tinker ou un controller de test
$payment = Payment::with(['student', 'feeType', 'academicYear'])->first();
$service = app(PaymentService::class);
return $service->generateReceipt($payment);
```

```php
$invoice = Invoice::with(['student', 'academicYear', 'feeTypes', 'scholarships'])->first();
$service = app(InvoiceService::class);
return $service->generateInvoicePDF($invoice);
```

### Via les routes API

```bash
# Télécharger un reçu
curl -H "Authorization: Bearer {token}" \
     http://localhost:8000/api/v1/payments/1/receipt

# Télécharger une facture
curl -H "Authorization: Bearer {token}" \
     http://localhost:8000/api/v1/invoices/1/pdf
```

---

## 🔧 Personnalisation

### Modifier le logo

Le logo est actuellement en texte. Pour utiliser une image :

```blade
{{-- Remplacer --}}
<div class="logo">{{ $college['name'] }}</div>

{{-- Par --}}
<img src="{{ public_path('images/logo.png') }}" alt="Logo" style="max-width: 150px;">
```

**⚠️ Important** : Utiliser `public_path()` et non `asset()` pour les PDF.

### Ajouter un QR code

Installer le package :

```bash
composer require simplesoftwareio/simple-qrcode
```

Dans le template :

```blade
@php
    $qrCode = base64_encode(QrCode::format('png')->size(100)->generate($payment->receipt_number));
@endphp
<img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code">
```

### Traduction

Les templates sont en français. Pour internationaliser :

```blade
{{-- Avant --}}
<div class="invoice-title">FACTURE</div>

{{-- Après --}}
<div class="invoice-title">{{ __('finance::pdf.invoice_title') }}</div>
```

Créer `Modules/Finance/lang/fr/pdf.php` :

```php
return [
    'invoice_title' => 'FACTURE',
    // ...
];
```

---

## 📱 Impression

### Recommandations

-   **Reçus** : Imprimer au format A5 (demi A4)
-   **Factures** : Imprimer au format A4
-   **Qualité** : 300 DPI minimum pour un rendu professionnel
-   **Couleur** : Les templates supportent noir & blanc mais sont optimisés pour couleur

### Options d'impression navigateur

Les PDF générés peuvent être directement imprimés depuis le navigateur avec `Ctrl+P`.

---

## 🐛 Debugging

### Le PDF ne s'affiche pas

1. Vérifier que DomPDF est installé : `composer show barryvdh/laravel-dompdf`
2. Vérifier les logs Laravel : `storage/logs/laravel.log`
3. Tester le template Blade seul sans PDF

### Le style ne s'affiche pas correctement

1. Vérifier que le CSS est inline (pas de `<link>`)
2. Éviter les propriétés CSS non supportées
3. Utiliser `display: table` au lieu de flexbox
4. Tester avec une table simple d'abord

### Les images ne s'affichent pas

1. Utiliser `public_path()` et non `asset()`
2. Vérifier que l'image existe
3. Utiliser des chemins absolus

### Les polices ne s'affichent pas

1. Utiliser DejaVu Sans (par défaut)
2. Pour d'autres polices, configurer dompdf
3. Vérifier l'encodage UTF-8

---

## 📚 Ressources

-   **Documentation DomPDF** : https://github.com/dompdf/dompdf
-   **Laravel DomPDF** : https://github.com/barryvdh/laravel-dompdf
-   **CSS supporté** : https://github.com/dompdf/dompdf/wiki/CSSCompatibility
-   **Exemples** : Dans les templates `receipt.blade.php` et `invoice.blade.php`

---

## ✅ Checklist avant mise en production

-   [ ] Tester génération reçu avec données réelles
-   [ ] Tester génération facture avec données réelles
-   [ ] Vérifier toutes les traductions
-   [ ] Configurer les informations du collège (nom, adresse, etc.)
-   [ ] Ajouter le logo (si image)
-   [ ] Tester impression physique
-   [ ] Vérifier performance (temps de génération)
-   [ ] Vérifier que tous les champs s'affichent correctement
-   [ ] Tester avec différents statuts
-   [ ] Tester avec/sans bourses
-   [ ] Tester avec/sans notes
