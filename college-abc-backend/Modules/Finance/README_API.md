# Module Finance - Documentation des Routes API

## Base URL

```
/api/v1/
```

Toutes les routes sont protégées par l'authentification **Sanctum** et nécessitent un token Bearer.

---

## 📋 PAYMENTS (Paiements)

### 1. Liste des paiements

```http
GET /api/v1/payments
```

**Query Parameters** :

-   `student_id` (int) - Filtrer par élève
-   `academic_year_id` (int) - Filtrer par année académique
-   `status` (string) - Filtrer parstatut (en_attente, valide, annule)
-   `payment_method` (string) - Filtrer par méthode
-   `start_date` (date) - Date début
-   `end_date` (date) - Date fin
-   `per_page` (int) - Nombre par page (défaut: 15)

**Réponse** : Pagination Laravel avec les paiements

---

### 2. Créer un paiement

```http
POST /api/v1/payments
```

**Body** (JSON) :

```json
{
    "student_id": 1,
    "fee_type_id": 2,
    "academic_year_id": 1,
    "amount": 50000,
    "payment_method": "especes",
    "payment_date": "2025-12-15",
    "reference": "CHQ123456",
    "payer_name": "Jean Dupont",
    "notes": "Paiement premier trimestre",
    "status": "valide"
}
```

**Champs requis** :

-   `student_id`, `fee_type_id`, `academic_year_id`, `amount`, `payment_method`

**payment_method** : `especes`, `cheque`, `virement`, `mobile_money`, `carte`

---

### 3. Détails d'un paiement

```http
GET /api/v1/payments/{id}
```

---

### 4. Valider un paiement

```http
POST /api/v1/payments/{id}/validate
```

Change le statut de `en_attente` à `valide`.

---

### 5. Annuler un paiement

```http
POST /api/v1/payments/{id}/cancel
```

**Body** (JSON) :

```json
{
    "reason": "Erreur de saisie"
}
```

---

### 6. Télécharger un reçu PDF

```http
GET /api/v1/payments/{id}/receipt
```

**Réponse** : Fichier PDF

---

### 7. Historique paiements d'un élève

```http
GET /api/v1/students/{studentId}/payments
```

**Query Parameters** : Mêmes filtres que liste générale

---

### 8. Solde d'un élève

```http
GET /api/v1/students/{studentId}/balance
```

**Query Parameters** :

-   `academic_year_id` (int) - Optionnel (utilise l'année courante par défaut)

**Réponse** :

```json
{
    "data": {
        "student_id": 1,
        "academic_year_id": 1,
        "summary": {
            "total_due": 500000,
            "total_discount": 50000,
            "total_paid": 250000,
            "total_remaining": 200000,
            "payment_progress": 55.56
        },
        "invoices_count": 3,
        "payments_count": 5,
        "scholarships_count": 1,
        "invoices": [...],
        "payments": [...],
        "scholarships": [...]
    }
}
```

---

### 9. Statistiques de paiements

```http
GET /api/v1/payments/statistics/summary
```

**Query Parameters** :

-   `academic_year_id` (int)
-   `start_date` (date)
-   `end_date` (date)

---

## 📄 INVOICES (Factures)

### 1. Liste des factures

```http
GET /api/v1/invoices
```

**Query Parameters** :

-   `student_id` (int)
-   `academic_year_id` (int)
-   `status` (string) - brouillon, emise, partiellement_payee, payee, en_retard, annulee
-   `period` (string) - annuel, trimestriel_1, trimestriel_2, trimestriel_3, mensuel
-   `per_page` (int)

---

### 2. Générer une facture

```http
POST /api/v1/invoices
```

**Body** (JSON) :

```json
{
    "student_id": 1,
    "academic_year_id": 1,
    "period": "trimestriel_1",
    "due_date": "2025-02-15",
    "issue_date": "2025-01-15",
    "notes": "Facture premier trimestre",
    "auto_issue": true,
    "fee_types": [
        {
            "fee_type_id": 1,
            "quantity": 1,
            "discount": 0
        },
        {
            "fee_type_id": 2,
            "quantity": 3,
            "discount": 5000
        }
    ]
}
```

**Champs requis** : `student_id`, `academic_year_id`, `period`

**Note** : Si `fee_types` n'est pas fourni, tous les frais obligatoires applicables sont ajoutés automatiquement.

---

### 3. Détails d'une facture

```http
GET /api/v1/invoices/{id}
```

---

### 4. Émettre une facture

```http
POST /api/v1/invoices/{id}/issue
```

Change le statut de `brouillon` à `emis` et crée les rappels de paiement.

**Body** (JSON) :

```json
{
    "create_reminders": true
}
```

---

### 5. Annuler une facture

```http
POST /api/v1/invoices/{id}/cancel
```

**Body** (JSON) :

```json
{
    "reason": "Erreur de génération"
}
```

---

### 6. Télécharger une facture PDF

```http
GET /api/v1/invoices/{id}/pdf
```

**Réponse** : Fichier PDF

---

### 7. Liste des factures impayées

```http
GET /api/v1/invoices/unpaid/list
```

**Query Parameters** :

-   `academic_year_id` (int)
-   `class_id` (int)
-   `status` (string) - emise, partiellement_payee, en_retard
-   `period` (string)
-   `overdue_only` (bool)
-   `due_soon_days` (int) - Ex: 7 pour factures dues dans 7 jours
-   `sort_by` (string)
-   `sort_order` (string)

---

### 8. Calculer le montant dû

```http
POST /api/v1/invoices/calculate-due
```

Simulation avant génération de facture.

**Body** (JSON) :

```json
{
    "student_id": 1,
    "academic_year_id": 1,
    "period": "annuel"
}
```

**Réponse** :

```json
{
    "data": {
        "student_id": 1,
        "academic_year_id": 1,
        "period": "annuel",
        "total_amount": 500000,
        "total_discount": 50000,
        "net_amount": 450000,
        "total_paid": 0,
        "remaining_due": 450000,
        "fee_breakdown": [...],
        "scholarship_breakdown": [...]
    }
}
```

---

### 9. Export factures par classe

```http
GET /api/v1/invoices/class/{classId}/export
```

**Query Parameters** :

-   `academic_year_id` (int) - Requis
-   `period` (string) - Requis

---

### 10. Statistiques de facturation

```http
GET /api/v1/invoices/statistics/summary
```

**Query Parameters** :

-   `academic_year_id` (int)

---

## 💰 FEE TYPES (Types de frais)

### 1. Liste des types de frais

```http
GET /api/v1/fee-types
```

**Query Parameters** :

-   `is_active` (bool)
-   `is_mandatory` (bool)
-   `frequency` (string) - mensuel, trimestriel, annuel, unique
-   `cycle_id` (int)
-   `level_id` (int)
-   `search` (string) - Recherche dans nom/description
-   `sort_by` (string) - Défaut: name
-   `sort_order` (string) - asc/desc
-   `paginate` (bool) - Défaut: true
-   `per_page` (int)

---

### 2. Créer un type de frais

```http
POST /api/v1/fee-types
```

**Body** (JSON) :

```json
{
    "name": "Frais de scolarité",
    "description": "Frais de scolarité annuels",
    "amount": 250000,
    "frequency": "annuel",
    "cycle_id": null,
    "level_id": null,
    "is_mandatory": true,
    "is_active": true
}
```

**Champs requis** : `name`, `amount`, `frequency`

---

### 3. Détails d'un type de frais

```http
GET /api/v1/fee-types/{id}
```

---

### 4. Modifier un type de frais

```http
PUT /api/v1/fee-types/{id}
```

**Body** (JSON) : Mêmes champs que création, tous optionnels

---

### 5. Supprimer un type de frais

```http
DELETE /api/v1/fee-types/{id}
```

**Note** : Impossible de supprimer si déjà utilisé. Utiliser désactivation à la place.

---

### 6. Activer un type de frais

```http
POST /api/v1/fee-types/{id}/activate
```

---

### 7. Désactiver un type de frais

```http
POST /api/v1/fee-types/{id}/deactivate
```

---

### 8. Types de frais applicables à un élève

```http
GET /api/v1/fee-types/student/{studentId}/applicable
```

Retourne seulement les frais actifs applicables au cycle/niveau de l'élève.

---

## 🔐 Authentification

### Headers requis

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Exemple avec cURL

```bash
curl -X GET "http://localhost:8000/api/v1/payments" \
  -H "Authorization: Bearer your-token-here" \
  -H "Accept: application/json"
```

---

## ❌ Gestion des erreurs

### Codes HTTP

-   `200` - Succès
-   `201` - Créé
-   `400` - Erreur de validation ou logique métier
-   `404` - Ressource non trouvée
-   `409` - Conflit (ex: suppression impossible)
-   `500` - Erreur serveur

### Format des erreurs

```json
{
    "message": "Description de l'erreur",
    "error": "Détails techniques"
}
```

### Erreurs de validation

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "amount": ["Le montant doit être supérieur à 0."],
        "student_id": ["L'élève sélectionné n'existe pas."]
    }
}
```

---

## 📝 Notes importantes

1. **Pagination** : La plupart des listes retournent une pagination Laravel standard avec `data`, `links`, `meta`

2. **Filtres** : Tous les filtres sont optionnels

3. **Dates** : Format `YYYY-MM-DD`

4. **Montants** : En FCFA (nombre entier ou décimal)

5. **Relations** : Les relations sont chargées automatiquement (eager loading) dans la plupart des endpoints

6. **Soft Delete** : Les ressources supprimées ne sont pas réellement effacées mais marquées comme supprimées

7. **Transactions** : Toutes les opérations critiques (création paiement, facture) utilisent des transactions DB pour garantir l'intégrité

8. **Automatisations** :
    - Les numéros de reçu/facture sont générés automatiquement
    - Les soldes sont recalculés automatiquement
    - Les bourses sont appliquées automatiquement aux factures
    - Les rappels de paiement sont créés automatiquement

---

## 🧪 Tests avec Postman

Une collection Postman est disponible avec tous les endpoints et exemples de requêtes.

**TODO** : Générer la collection Postman automatiquement avec Scribe.
