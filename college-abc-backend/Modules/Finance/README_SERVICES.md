# Module Finance - Documentation des Services

## Vue d'ensemble

Les services contiennent toute la logique métier du module Finance. Ils orchestrent les opérations sur les entités, gèrent les transactions, la validation et le logging.

## Architecture

-   **Séparation des responsabilités** : Les controllers appellent les services, ne contiennent pas de logique métier
-   **Transactions DB** : Toutes les opérations critiques utilisent `DB::transaction()`
-   **Logging** : Toutes les opérations importantes sont loggées
-   **Validation** : Chaque service valide ses données en entrée
-   **Exceptions** : Les erreurs sont propagées avec des messages clairs

---

## Services

### 1. PaymentService

**Responsabilité** : Gestion complète des paiements

#### Méthodes principales

##### `recordPayment(array $data): Payment`

Enregistre un nouveau paiement.

**Paramètres** :

```php
[
    'student_id' => int,          // Requis
    'fee_type_id' => int,         // Requis
    'academic_year_id' => int,    // Requis
    'amount' => float,            // Requis, > 0
    'payment_method' => string,   // Requis: especes|cheque|virement|mobile_money|carte
    'payment_date' => date,       // Optionnel (défaut: now())
    'reference' => string,        // Optionnel (numéro chèque, transaction, etc.)
    'payer_name' => string,       // Optionnel
    'notes' => string,            // Optionnel
    'status' => string,           // Optionnel (défaut: 'valide')
]
```

**Comportement** :

-   Valide les données
-   Vérifie que l'élève, le type de frais et l'année académique existent
-   Génère automatiquement un `receipt_number` unique
-   Met à jour automatiquement le solde des factures concernées
-   Logge l'opération
-   Utilise une transaction DB

**Exemple** :

```php
$paymentService = app(PaymentService::class);
$payment = $paymentService->recordPayment([
    'student_id' => 1,
    'fee_type_id' => 2,
    'academic_year_id' => 1,
    'amount' => 50000,
    'payment_method' => 'especes',
]);
```

---

##### `generateReceipt(Payment $payment)`

Génère un reçu PDF pour un paiement.

**Retour** : Stream PDF

**Exemple** :

```php
return $paymentService->generateReceipt($payment);
```

---

##### `calculateBalance(int $studentId, ?int $academicYearId = null): array`

Calcule le solde complet d'un élève pour une année académique.

**Retour** :

```php
[
    'student_id' => int,
    'academic_year_id' => int,
    'summary' => [
        'total_due' => float,
        'total_discount' => float,
        'total_paid' => float,
        'total_remaining' => float,
        'payment_progress' => float (%)
    ],
    'invoices_count' => int,
    'payments_count' => int,
    'scholarships_count' => int,
    'invoices' => Collection,
    'payments' => Collection,
    'scholarships' => Collection,
]
```

---

##### `getStudentPaymentHistory(int $studentId, array $filters = [])`

Récupère l'historique des paiements avec filtres.

**Filtres disponibles** :

-   `academic_year_id`
-   `status`
-   `payment_method`
-   `start_date` et `end_date`
-   `fee_type_id`
-   `sort_by` et `sort_order`

---

##### Autres méthodes

-   `validatePayment(Payment $payment)` : Valide un paiement en attente
-   `cancelPayment(Payment $payment, ?string $reason)` : Annule un paiement
-   `getPaymentStatistics(array $filters)` : Statistiques de paiements

---

### 2. InvoiceService

**Responsabilité** : Gestion complète des factures

#### Méthodes principales

##### `generateInvoice(array $data): Invoice`

Génère une nouvelle facture pour un élève.

**Paramètres** :

```php
[
    'student_id' => int,          // Requis
    'academic_year_id' => int,    // Requis
    'period' => string,           // Requis: annuel|trimestriel_1|trimestriel_2|trimestriel_3|mensuel
    'due_date' => date,           // Optionnel (défaut: +30 jours)
    'issue_date' => date,         // Optionnel (défaut: now())
    'notes' => string,            // Optionnel
    'fee_types' => array,         // Optionnel (sinon auto)
    'auto_issue' => bool,         // Optionnel (défaut: false)
]
```

**Avec fee_types spécifiques** :

```php
'fee_types' => [
    [
        'fee_type_id' => 1,
        'quantity' => 1,      // Optionnel
        'discount' => 0,      // Optionnel
    ],
    // ...
]
```

**Comportement** :

-   Vérifie que l'élève est inscrit
-   Empêche la duplication (même période pour même élève)
-   Ajoute automatiquement les frais obligatoires si `fee_types` non fourni
-   Applique automatiquement les bourses actives
-   Crée automatiquement 3 rappels de paiement si `auto_issue = true`
-   Génère un `invoice_number` unique
-   Utilise une transaction DB

**Exemple** :

```php
$invoiceService = app(InvoiceService::class);
$invoice = $invoiceService->generateInvoice([
    'student_id' => 1,
    'academic_year_id' => 1,
    'period' => 'trimestriel_1',
    'auto_issue' => true,
]);
```

---

##### `calculateTotalDue(int $studentId, int $academicYearId, ?string $period = null): array`

Calcule le total dû pour un élève (simulation avant génération de facture).

**Retour** :

```php
[
    'student_id' => int,
    'academic_year_id' => int,
    'period' => string,
    'total_amount' => float,
    'total_discount' => float,
    'net_amount' => float,
    'total_paid' => float,
    'remaining_due' => float,
    'fee_breakdown' => [...],
    'scholarship_breakdown' => [...],
]
```

---

##### `applyScholarship(Invoice $invoice, Scholarship $scholarship): Invoice`

Applique (recalcule) une bourse sur une facture.

**Validations** :

-   La bourse doit appartenir au même élève
-   La bourse doit être pour la même année académique
-   La bourse doit être active

---

##### `getUnpaidInvoices(array $filters = [])`

Récupère toutes les factures impayées avec filtres.

**Filtres disponibles** :

-   `academic_year_id`
-   `class_id`
-   `status`
-   `period`
-   `overdue_only` (bool)
-   `due_soon_days` (int)
-   `sort_by` et `sort_order`

---

##### Autres méthodes

-   `issueInvoice(Invoice $invoice, bool $createReminders)` : Émet une facture
-   `generateInvoicePDF(Invoice $invoice)` : Génère le PDF
-   `cancelInvoice(Invoice $invoice, ?string $reason)` : Annule une facture
-   `getInvoiceStatistics(array $filters)` : Statistiques de facturation

---

### 3. ScholarshipService

**Responsabilité** : Gestion complète des bourses et réductions

#### Méthodes principales

##### `createScholarship(array $data): Scholarship`

Crée une nouvelle bourse.

**Paramètres** :

```php
[
    'student_id' => int,          // Requis
    'academic_year_id' => int,    // Requis
    'name' => string,             // Requis
    'type' => string,             // Requis: bourse|reduction|exoneration|aide_sociale
    'percentage' => float,        // Requis SI fixed_amount non fourni (0-100)
    'fixed_amount' => float,      // Requis SI percentage non fourni
    'reason' => string,           // Optionnel
    'conditions' => string,       // Optionnel
    'start_date' => date,         // Requis
    'end_date' => date,           // Requis
    'status' => string,           // Optionnel (défaut: 'en_attente')
    'notes' => string,            // Optionnel
]
```

**Validations** :

-   Un seul type de réduction : soit `percentage`, soit `fixed_amount`, pas les deux
-   `percentage` entre 0 et 100
-   `fixed_amount` positif
-   `start_date` < `end_date`
-   Empêche la duplication (même nom pour même élève)

**Exemple** :

```php
$scholarshipService = app(ScholarshipService::class);

// Bourse en pourcentage
$scholarship = $scholarshipService->createScholarship([
    'student_id' => 1,
    'academic_year_id' => 1,
    'name' => 'Bourse d\'excellence',
    'type' => 'bourse',
    'percentage' => 50,
    'reason' => 'Classé premier',
    'start_date' => '2025-01-01',
    'end_date' => '2025-06-30',
]);

// Réduction en montant fixe
$scholarship = $scholarshipService->createScholarship([
    'student_id' => 2,
    'academic_year_id' => 1,
    'name' => 'Réduction famille nombreuse',
    'type' => 'reduction',
    'fixed_amount' => 25000,
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
]);
```

---

##### `approveScholarship(Scholarship $scholarship): Scholarship`

Approuve une bourse en attente.

**Comportement** :

-   Change le statut à `active`
-   Enregistre l'utilisateur approbateur et la date
-   Déclenche le recalcul des factures de l'élève

---

##### `applyScholarship(Scholarship $scholarship): array`

Applique une bourse à toutes les factures d'un élève.

**Retour** :

```php
[
    'scholarship_id' => int,
    'invoices_updated' => int,
    'total_discount_applied' => float,
    'invoices' => Collection,
]
```

---

##### `calculateDiscount(Scholarship $scholarship, float $amount): float`

Calcule la réduction pour un montant donné.

**Exemple** :

```php
// Pourcentage
$discount = $scholarshipService->calculateDiscount($scholarship, 100000);
// Si percentage = 30%, retourne 30000

// Montant fixe
$discount = $scholarshipService->calculateDiscount($scholarship, 100000);
// Si fixed_amount = 25000, retourne 25000
```

---

##### Autres méthodes

-   `suspendScholarship(Scholarship $scholarship, ?string $reason)` : Suspend
-   `reactivateScholarship(Scholarship $scholarship)` : Réactive
-   `cancelScholarship(Scholarship $scholarship, ?string $reason)` : Annule
-   `getStudentActiveScholarships(int $studentId, ?int $academicYearId)` : Liste bourses actives
-   `getScholarshipStatistics(array $filters)` : Statistiques
-   `checkAndExpireScholarships()` : Job cron - expire les bourses automatiquement
-   `updateScholarship(Scholarship $scholarship, array $data)` : Mise à jour

---

## Bonnes Pratiques

### 1. Toujours utiliser les services dans les controllers

```php
// ✅ BON
class PaymentController extends Controller
{
    public function store(PaymentRequest $request, PaymentService $paymentService)
    {
        $payment = $paymentService->recordPayment($request->validated());
        return response()->json($payment, 201);
    }
}

// ❌ MAUVAIS - Logique métier dans le controller
class PaymentController extends Controller
{
    public function store(PaymentRequest $request)
    {
        $payment = Payment::create($request->validated());
        // ...logique métier ici...
        return response()->json($payment, 201);
    }
}
```

### 2. Gestion des erreurs

Les services lancent des exceptions en cas d'erreur. Les controllers doivent les attraper :

```php
try {
    $payment = $paymentService->recordPayment($data);
    return response()->json($payment, 201);
} catch (\Exception $e) {
    return response()->json(['error' => $e->getMessage()], 400);
}
```

### 3. Injection de dépendances

```php
// ✅ BON - Injection dans le constructeur
class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
}

// ✅ BON - Injection dans la méthode
class PaymentController extends Controller
{
    public function store(Request $request, PaymentService $paymentService)
    {
        //...
    }
}

// ❌ MAUVAIS - Instanciation manuelle
$paymentService = new PaymentService();
```

### 4. Transactions

Les services gèrent déjà les transactions. Ne pas imbriquer :

```php
// ✅ BON
$payment = $paymentService->recordPayment($data);

// ❌ MAUVAIS - Transaction déjà dans le service
DB::transaction(function() use ($paymentService, $data) {
    $payment = $paymentService->recordPayment($data);
});
```

### 5. Validation

Valider dans les Request classes, pas dans les services :

```php
// ✅ BON
class PaymentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:0',
            // ...
        ];
    }
}

public function store(PaymentRequest $request, PaymentService $service)
{
    $payment = $service->recordPayment($request->validated());
}
```

---

## Logging

Tous les services loggent les opérations importantes :

-   **Info** : Opérations réussies (création, validation, etc.)
-   **Warning** : Opérations sensibles (annulation, suspension, etc.)
-   **Error** : Échecs d'opérations

**Localisation des logs** : `storage/logs/laravel.log`

---

## Tests

Chaque service doit avoir ses tests unitaires :

```php
// tests/Unit/Services/PaymentServiceTest.php
class PaymentServiceTest extends TestCase
{
    public function test_record_payment_creates_payment()
    {
        $service = app(PaymentService::class);
        $payment = $service->recordPayment([...]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertNotNull($payment->receipt_number);
    }
}
```

---

## Workflows Typiques

### Workflow 1 : Enregistrer un paiement

```php
$paymentService = app(PaymentService::class);

// 1. Enregistrer le paiement
$payment = $paymentService->recordPayment([
    'student_id' => 1,
    'fee_type_id' => 2,
    'academic_year_id' => 1,
    'amount' => 50000,
    'payment_method' => 'especes',
]);

// 2. Générer le reçu PDF
return $paymentService->generateReceipt($payment);

// Les factures sont automatiquement mises à jour !
```

### Workflow 2 : Créer une facture

```php
$invoiceService = app(InvoiceService::class);

// 1. Générer la facture (brouillon)
$invoice = $invoiceService->generateInvoice([
    'student_id' => 1,
    'academic_year_id' => 1,
    'period' => 'trimestriel_1',
]);

// 2. Émettre la facture (crée les rappels)
$invoice = $invoiceService->issueInvoice($invoice, true);

// 3. Générer le PDF
return $invoiceService->generateInvoicePDF($invoice);
```

### Workflow 3 : Créer et approuver une bourse

```php
$scholarshipService = app(ScholarshipService::class);

// 1. Créer la bourse (en attente)
$scholarship = $scholarshipService->createScholarship([
    'student_id' => 1,
    'academic_year_id' => 1,
    'name' => 'Bourse d\'excellence',
    'type' => 'bourse',
    'percentage' => 50,
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
]);

// 2. Approuver la bourse
$scholarship = $scholarshipService->approveScholarship($scholarship);

// 3. Appliquer à toutes les factures
$result = $scholarshipService->applyScholarship($scholarship);
// Les factures sont automatiquement recalculées !
```
