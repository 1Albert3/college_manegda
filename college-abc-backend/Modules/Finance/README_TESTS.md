# Module Finance - Documentation Tests

## Vue d'ensemble

Le module Finance dispose d'une suite de tests complète couvrant :

-   **Tests unitaires** (Services)
-   **Tests Feature** (API)
-   **Seeder** pour données de démonstration

---

## 🧪 Tests Unitaires (Unit Tests)

### PaymentServiceTest.php

**Chemin** : `Modules/Finance/tests/Unit/PaymentServiceTest.php`

**Tests implémentés** :

1. **`it_can_record_a_payment`**

    - Vérifie l'enregistrement d'un paiement
    - Assertions : Instance Payment, numéro de reçu généré, montant correct, présence en DB

2. **`it_generates_unique_receipt_numbers`**

    - Vérifie l'unicité des numéros de reçu
    - Crée 2 paiements et compare les numéros

3. **`it_validates_payment_amount`**

    - Vérifie la validation du montant (> 0)
    - Attend une exception pour montant = 0

4. **`it_calculates_student_balance_correctly`**

    - Vérifie le calcul du solde d'un élève
    - Assertions : montant payé, nombre de paiements

5. **`it_can_validate_a_pending_payment`**

    - Vérifie la validation d'un paiement en attente
    - Assertions : statut changé, validateur enregistré, date de validation

6. **`it_cannot_validate_an_already_validated_payment`**

    - Vérifie qu'un paiement déjà validé ne peut être re-validé
    - Attend une exception

7. **`it_can_cancel_a_payment`**
    - Vérifie l'annulation d'un paiement
    - Assertions : statut annulé, raison dans les notes

---

### InvoiceServiceTest.php

**Chemin** : `Modules/Finance/tests/Unit/InvoiceServiceTest.php`

**Tests implémentés** :

1. **`it_can_generate_an_invoice`**

    - Vérifie la génération d'une facture
    - Assertions : Instance Invoice, numéro généré, période correcte

2. **`it_generates_unique_invoice_numbers`**

    - Vérifie l'unicité des numéros de facture
    - Crée 2 factures et compare les numéros

3. **`it_prevents_duplicate_invoices_for_same_period`**

    - Vérifie la prévention de doublons
    - Attend une exception lors de la 2ᵉ tentative

4. **`it_calculates_total_due_correctly`**

    - Vérifie le calcul du montant total dû
    - Assertions : total, payé, restant

5. **`it_applies_scholarships_to_calculation`**

    - Vérifie l'application des bourses au calcul
    - Assertions : montant total, réduction, montant net

6. **`it_can_issue_an_invoice`**

    - Vérifie l'émission d'une facture
    - Assertions : statut changé à "emise", date d'émission

7. **`it_cannot_issue_an_already_issued_invoice`**

    - Vérifie qu'une facture émise ne peut être ré-émise
    - Attend une exception

8. **`it_can_get_unpaid_invoices`**

    - Vérifie la récupération des factures impayées
    - Crée 3 impayées et 2 payées, vérifie qu'on récupère 3

9. **`it_can_cancel_an_invoice`**

    - Vérifie l'annulation d'une facture
    - Assertions : statut annulé, raison dans les notes

10. **`it_cannot_cancel_partially_paid_invoice`**
    - Vérifie qu'une facture partiellement payée ne peut être annulée
    - Attend une exception

---

## 🌐 Tests Feature (API Tests)

### PaymentApiTest.php

**Chemin** : `Modules/Finance/tests/Feature/PaymentApiTest.php`

**Tests implémentés** :

1. **`it_can_list_payments`**

    - **Endpoint** : `GET /api/v1/payments`
    - Vérifie la liste paginée des paiements
    - Assertions : status 200, structure JSON

2. **`it_can_create_a_payment`**

    - **Endpoint** : `POST /api/v1/payments`
    - Vérifie la création d'un paiement
    - Assertions : status 201, structure JSON, présence en DB

3. **`it_validates_required_fields_when_creating_payment`**

    - **Endpoint** : `POST /api/v1/payments`
    - Vérifie la validation des champs requis
    - Assertions : status 422, erreurs de validation

4. **`it_validates_payment_amount_is_positive`**

    - **Endpoint** : `POST /api/v1/payments`
    - Vérifie la validation du montant > 0
    - Assertions : status 422, erreur sur amount

5. **`it_can_show_a_payment`**

    - **Endpoint** : `GET /api/v1/payments/{id}`
    - Vérifie l'affichage d'un paiement
    - Assertions : status 200, structure avec relations

6. **`it_can_validate_a_pending_payment`**

    - **Endpoint** : `POST /api/v1/payments/{id}/validate`
    - Vérifie la validation d'un paiement
    - Assertions : status 200, message, statut en DB

7. **`it_can_cancel_a_payment`**

    - **Endpoint** : `POST /api/v1/payments/{id}/cancel`
    - Vérifie l'annulation d'un paiement
    - Assertions : status 200, message, statut en DB

8. **`it_requires_authentication`**
    - **Endpoint** : `GET /api/v1/payments`
    - Vérifie que l'authentification est requise
    - Assertions : status 401 sans token

---

## 📊 Seeder

### FinanceSeeder.php

**Chemin** : `Modules/Finance/Database/Seeders/FinanceSeeder.php`

**Données générées** :

#### 1. Fee Types (7 types + 1 optionnel selon cycle)

-   Frais de scolarité (250 000 FCFA, annuel, obligatoire)
-   Frais d'inscription (50 000 FCFA, annuel, obligatoire)
-   Frais de cantine (30 000 FCFA, mensuel, optionnel)
-   Frais de bibliothèque (15 000 FCFA, annuel, optionnel)
-   Frais de sport (20 000 FCFA, annuel, optionnel)
-   Frais de transport (25 000 FCFA, mensuel, optionnel)
-   Frais d'examen (35 000 FCFA, unique, obligatoire)
-   Frais de laboratoire - Collège uniquement (40 000 FCFA, annuel, obligatoire)

#### 2. Scholarships (10 élèves aléatoires)

-   **1/3** : Bourse d'excellence (25%, 50% ou 75%)
-   **1/3** : Réduction famille nombreuse (50 000 FCFA fixe)
-   **1/3** : Pas de bourse

#### 3. Invoices (20 élèves × 3 trimestres = 60 factures)

-   Génère 3 factures par élève (trimestriel_1, 2, 3)
-   Attache tous les frais obligatoires applicables
-   Applique automatiquement les bourses actives
-   Statut : "émise"

#### 4. Payments (30 paiements aléatoires)

-   **30%** : Impayé (aucun paiement)
-   **40%** : Partiellement payé (50% du montant dû)
-   **30%** : Totalement payé (100% du montant dû)
-   Méthodes variées : espèces, chèque, virement, mobile money, carte
-   Statut : "valide"

---

## 🚀 Exécuter les tests

### Tous les tests du module

```bash
php artisan test --filter=Finance
```

### Tests unitaires seulement

```bash
php artisan test Modules/Finance/tests/Unit
```

### Tests Feature seulement

```bash
php artisan test Modules/Finance/tests/Feature
```

### Test spécifique

```bash
php artisan test --filter=PaymentServiceTest
php artisan test --filter=it_can_record_a_payment
```

### Avec couverture de code

```bash
php artisan test --coverage --filter=Finance
```

---

## 🌱 Exécuter le seeder

### Seeder Finance uniquement

```bash
php artisan db:seed --class=Modules\\Finance\\Database\\Seeders\\FinanceSeeder
```

### Tous les seeders (incluant Finance)

```bash
php artisan db:seed
```

**Note** : Assurez-vous d'ajouter le FinanceSeeder au DatabaseSeeder principal :

```php
// database/seeders/DatabaseSeeder.php
public function run()
{
    $this->call([
        // ... autres seeders
        \Modules\Finance\Database\Seeders\FinanceSeeder::class,
    ]);
}
```

---

## 🎯 Couverture des tests

### Ce qui est testé

✅ Enregistrement de paiements  
✅ Génération de numéros uniques (reçus, factures)  
✅ Validation de paiements  
✅ Annulation de paiements et factures  
✅ Calcul des soldes  
✅ Calcul des totaux dus  
✅ Application des bourses  
✅ Génération de factures  
✅ Émission de factures  
✅ Prévention de doublons  
✅ Validation des montants  
✅ Validation des champs requis (API)  
✅ Authentification requise (API)  
✅ Récupération factures impayées

### Ce qui n'est pas encore testé (TODO)

❌ Génération de PDF (receipt, invoice)  
❌ Création de rappels de paiement  
❌ Statistiques  
❌ Export Excel  
❌ Tests Scholarship entity  
❌ Tests FeeType controller  
❌ Tests Invoice controller  
❌ Tests complexes avec plusieurs bourses  
❌ Tests de performance (grandes quantités de données)

---

## 📝 Bonnes pratiques

### 1. Arrange-Act-Assert (AAA)

Tous les tests suivent le pattern AAA :

```php
/** @test */
public function it_can_do_something()
{
    // Arrange - Préparer les données
    $payment = Payment::factory()->create();

    // Act - Exécuter l'action
    $result = $this->service->doSomething($payment);

    // Assert - Vérifier les résultats
    $this->assertEquals('expected', $result);
}
```

### 2. Isolation

-   Chaque test est isolé grâce à `RefreshDatabase`
-   Pas de dépendances entre les tests
-   Ordre d'exécution n'a pas d'importance

### 3. Factories

Utiliser les factories pour générer des données :

```php
Student::factory()->create();
Payment::factory()->count(5)->create();
```

### 4. Naming

Les noms de tests doivent être descriptifs :

```php
// ✅ Bon
it_can_record_a_payment
it_validates_payment_amount

// ❌ Mauvais
test1
testPayment
```

### 5. Assertions spécifiques

```php
// ✅ Bon - Assertion précise
$this->assertEquals(50000, $payment->amount);
$this->assertDatabaseHas('payments', ['id' => $payment->id]);

// ❌ Mauvais - Trop général
$this->assertTrue($payment->amount > 0);
```

---

## 🐛 Debugging des tests

### Afficher les erreurs détaillées

```bash
php artisan test --filter=PaymentServiceTest -vvv
```

### Exécuter un seul test avec dump

```php
/** @test */
public function it_can_record_a_payment()
{
    $payment = $this->paymentService->recordPayment($data);

    dd($payment); // Dump and die
    dump($payment); // Dump and continue
}
```

### Vérifier les requêtes SQL

```php
\DB::enableQueryLog();
$result = $this->service->doSomething();
dd(\DB::getQueryLog());
```

---

## 📊 Statistiques de tests

**Tests unitaires** : 17 tests  
**Tests Feature** : 8 tests  
**Total** : 25 tests

**Assertions** : ~70+ assertions

**Couverture estimée** : ~60% du code critique

---

## ✅ Checklist avant commit

-   [ ] Tous les tests passent : `php artisan test --filter=Finance`
-   [ ] Pas de code commenté dans les tests
-   [ ] Factories nécessaires créées
-   [ ] Seeder testé en isolation
-   [ ] Migrations à jour
-   [ ] Documentation mise à jour

---

## 🔗 Ressources

-   Laravel Testing : https://laravel.com/docs/testing
-   PHPUnit Documentation : https://phpunit.de/documentation.html
-   Database Testing : https://laravel.com/docs/database-testing
-   Factories : https://laravel.com/docs/eloquent-factories
