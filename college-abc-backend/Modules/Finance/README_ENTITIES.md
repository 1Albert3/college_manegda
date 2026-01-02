# Module Finance - Documentation des Entités

## Vue d'ensemble

Le module Finance gère tous les aspects financiers du collège : frais scolaires, paiements, factures, bourses et rappels automatiques.

## Entités

### 1. FeeType (Types de Frais)

**Table** : `fee_types`

**Description** : Définit les différents types de frais scolaires (scolarité, inscription, cantine, etc.)

**Attributs clés** :

-   `name` : Nom du frais
-   `amount` : Montant de base
-   `frequency` : mensuel, trimestriel, annuel, unique
-   `cycle_id`, `level_id` : Optionnels, pour cibler des cycles/niveaux spécifiques
-   `is_mandatory` : Obligatoire ou facultatif
-   `is_active` : Actif ou non

**Relations** :

-   `belongsTo` Cycle
-   `belongsTo` Level
-   `hasMany` Payment
-   `belongsToMany` Invoice (via pivot `invoice_fee_types`)

**Méthodes importantes** :

-   `isApplicableToStudent($student)` : Vérifie si le frais s'applique à un élève
-   `calculateAmountForPeriod($period, $months)` : Calcule le montant pour une période donnée

---

### 2. Payment (Paiements)

**Table** : `payments`

**Description** : Enregistre tous les paiements effectués par les élèves/parents

**Attributs clés** :

-   `receipt_number` : Numéro de reçu unique (auto-généré)
-   `student_id` : Élève concerné
-   `fee_type_id` : Type de frais payé
-   `amount` : Montant payé
-   `payment_date` : Date du paiement
-   `payment_method` : especes, cheque, virement, mobile_money, carte
-   `status` : en_attente, valide, annule

**Relations** :

-   `belongsTo` Student
-   `belongsTo` FeeType
-   `belongsTo` AcademicYear
-   `belongsTo` User (validator)

**Événements** :

-   À la création/modification : met à jour automatiquement le solde des factures concernées

**Méthodes importantes** :

-   `validate($user)` : Valide le paiement
-   `generateReceiptNumber()` : Génère un numéro de reçu unique
-   `updateInvoiceBalance()` : Recalcule le solde des factures

---

### 3. Invoice (Factures)

**Table** : `invoices`

**Description** : Factures émises aux élèves pour leurs frais scolaires

**Attributs clés** :

-   `invoice_number` : Numéro de facture unique (auto-généré)
-   `student_id` : Élève concerné
-   `period` : annuel, trimestriel_1, trimestriel_2, trimestriel_3, mensuel
-   `total_amount` : Montant total
-   `discount_amount` : Réductions (bourses)
-   `paid_amount` : Montant payé
-   `due_amount` : Reste à payer
-   `status` : brouillon, emise, partiellement_payee, payee, en_retard, annulee

**Relations** :

-   `belongsTo` Student
-   `belongsTo` AcademicYear
-   `belongsTo` User (generator)
-   `belongsToMany` FeeType (via pivot `invoice_fee_types`)
-   `hasMany` PaymentReminder

**Méthodes importantes** :

-   `recalculateBalance()` : Recalcule tous les montants (total, réductions, payé, dû)
-   `calculateScholarshipDiscount()` : Calcule les réductions de bourses applicables
-   `updateStatus()` : Met à jour le statut selon les montants
-   `addFeeType($feeType, $quantity, $discount)` : Ajoute une ligne de frais
-   `issue()` : Émet la facture
-   `cancel()` : Annule la facture

**Logique métier** :

-   Le statut est automatiquement mis à jour lors des modifications
-   Les bourses actives sont automatiquement appliquées
-   Le `due_amount` est recalculé à chaque changement

---

### 4. Scholarship (Bourses/Réductions)

**Table** : `scholarships`

**Description** : Gère les bourses, réductions et aides sociales accordées aux élèves

**Attributs clés** :

-   `student_id` : Élève bénéficiaire
-   `name` : Nom de la bourse
-   `type` : bourse, reduction, exoneration, aide_sociale
-   `percentage` : Pourcentage de réduction (0-100) OU
-   `fixed_amount` : Montant fixe de réduction
-   `start_date`, `end_date` : Période de validité
-   `status` : en_attente, active, suspendue, expiree, annulee

**Relations** :

-   `belongsTo` Student
-   `belongsTo` AcademicYear
-   `belongsTo` User (approver)

**Événements** :

-   À la création/modification : recalcule automatiquement les factures de l'élève

**Méthodes importantes** :

-   `approve($user)` : Approuve la bourse
-   `suspend()`, `reactivate()`, `cancel()`, `expire()` : Gestion du cycle de vie
-   `calculateDiscountAmount($totalAmount)` : Calcule le montant de la réduction
-   `applyToInvoice($invoice)` : Applique la bourse à une facture
-   `checkExpiration()` : Vérifie et expire automatiquement

---

### 5. PaymentReminder (Rappels de Paiement)

**Table** : `payment_reminders`

**Description** : Gère les rappels automatiques de paiement par SMS/Email

**Attributs clés** :

-   `invoice_id` : Facture concernée
-   `type` : sms, email, notification
-   `message` : Contenu du rappel
-   `reminder_date` : Date d'envoi planifiée
-   `status` : planifie, envoye, echoue, annule
-   `attempt_count` : Nombre de tentatives

**Relations** :

-   `belongsTo` Invoice
-   `belongsTo` Student

**Méthodes importantes** :

-   `send()` : Envoie le rappel (délègue à sendSMS, sendEmail ou sendNotification)
-   `markAsSent()`, `markAsFailed($error)` : Mise à jour du statut
-   `retry()` : Réessaye l'envoi (max 3 tentatives)
-   `reschedule($newDate)` : Reprogramme l'envoi
-   `createForInvoice($invoice, $type, $daysBeforeDue)` : Crée un rappel pour une facture

**Logique métier** :

-   Maximum 3 tentatives d'envoi
-   Message auto-généré avec les détails de la facture
-   S'intègre avec le service SMS du module Communication

---

## Relations entre Entités

```
FeeType
  ├── hasMany → Payment
  └── belongsToMany → Invoice (via invoice_fee_types)

Payment
  ├── belongsTo → Student
  ├── belongsTo → FeeType
  ├── belongsTo → AcademicYear
  └── belongsTo → User (validator)

Invoice
  ├── belongsTo → Student
  ├── belongsTo → AcademicYear
  ├── belongsTo → User (generator)
  ├── belongsToMany → FeeType (via invoice_fee_types)
  └── hasMany → PaymentReminder

Scholarship
  ├── belongsTo → Student
  ├── belongsTo → AcademicYear
  └── belongsTo → User (approver)

PaymentReminder
  ├── belongsTo → Invoice
  └── belongsTo → Student
```

## Workflow Typique

### 1. Configuration initiale

1. Créer les `FeeType` (types de frais) pour l'année scolaire

### 2. Génération de factures

1. Créer une `Invoice` pour un élève
2. Ajouter les `FeeType` applicables avec `addFeeType()`
3. Le système calcule automatiquement les bourses actives
4. Émettre la facture avec `issue()`
5. Des `PaymentReminder` sont automatiquement créés

### 3. Enregistrement de paiements

1. Créer un `Payment`
2. Le numéro de reçu est auto-généré
3. Le système met à jour automatiquement le solde de la facture
4. Le statut de la facture change automatiquement

### 4. Gestion des bourses

1. Créer une `Scholarship` pour un élève
2. Approuver avec `approve($user)`
3. Le système recalcule automatiquement toutes les factures de l'élève

### 5. Rappels automatiques

1. Les `PaymentReminder` sont créés automatiquement
2. Un job cron envoie les rappels à la date prévue
3. Le système gère automatiquement les échecs et les retries

## Scopes Utiles

### FeeType

-   `active()`, `mandatory()`, `optional()`
-   `byFrequency($frequency)`, `byCycle($id)`, `byLevel($id)`

### Payment

-   `validated()`, `pending()`, `cancelled()`
-   `byStudent($id)`, `byAcademicYear($id)`, `byFeeType($id)`
-   `thisMonth()`, `thisYear()`, `byDateRange($start, $end)`

### Invoice

-   `draft()`, `issued()`, `paid()`, `unpaid()`, `overdue()`
-   `byStudent($id)`, `byAcademicYear($id)`, `byPeriod($period)`
-   `dueSoon($days)`

### Scholarship

-   `active()`, `pending()`, `suspended()`, `expired()`
-   `byStudent($id)`, `byAcademicYear($id)`, `byType($type)`
-   `percentageBased()`, `fixedAmount()`

### PaymentReminder

-   `scheduled()`, `sent()`, `failed()`, `cancelled()`
-   `dueToday()`, `dueSoon($days)`
-   `byType($type)`, `byStudent($id)`, `byInvoice($id)`

## Événements et Automatisations

### Payment

-   **Création/Modification** → Recalcule le solde des factures concernées

### Invoice

-   **Création** → Génère un numéro de facture unique

### Scholarship

-   **Création/Modification** → Recalcule les factures de l'élève pour l'année académique

## Bonnes Pratiques

1. **Toujours utiliser les méthodes métier** plutôt que modifier les attributs directement

    ```php
    // ✅ Bon
    $payment->validate($user);

    // ❌ Mauvais
    $payment->status = 'valide';
    $payment->save();
    ```

2. **Laisser les numéros se générer automatiquement**

    ```php
    // ✅ Bon
    $payment = Payment::create([...]);

    // ❌ Ne pas spécifier receipt_number manuellement
    ```

3. **Toujours recalculer après modification**

    ```php
    $invoice->addFeeType($feeType);
    // Le recalcul est fait automatiquement
    ```

4. **Utiliser les scopes pour les requêtes**

    ```php
    // ✅ Bon
    Invoice::unpaid()->dueSoon(7)->get();

    // ❌ Moins lisible
    Invoice::whereIn('status', [...])
          ->where('due_date', '<=', ...)
          ->get();
    ```
