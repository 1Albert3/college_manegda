# Module Finance - Documentation Complète

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![Laravel](https://img.shields.io/badge/Laravel-10+-red)
![Module](https://img.shields.io/badge/Module-Finance-green)

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Installation](#installation)
3. [Structure du module](#structure-du-module)
4. [Documentation détaillée](#documentation-détaillée)
5. [Guide de démarrage rapide](#guide-de-démarrage-rapide)
6. [Contribution](#contribution)

---

## 🎯 Vue d'ensemble

Le **Module Finance** est un système complet de gestion financière pour établissements scolaires. Il permet de :

-   ✅ Gérer les **types de frais** (scolarité, inscription, cantine, etc.)
-   ✅ Générer et gérer les **factures** des élèves
-   ✅ Enregistrer et suivre les **paiements**
-   ✅ Gérer les **bourses et réductions**
-   ✅ Créer des **rappels de paiement** automatiques
-   ✅ Générer des **reçus** et **factures** PDF professionnels
-   ✅ Fournir des **statistiques** et **rapports** financiers
-   ✅ API REST complète avec **27 endpoints**

---

## 🚀 Installation

### Prérequis

-   Laravel 10+
-   PHP 8.1+
-   MySQL/PostgreSQL
-   Composer
-   Package `nwidart/laravel-modules`
-   Package `barryvdh/laravel-dompdf`

### Étapes d'installation

1. **Le module est déjà présent** dans `Modules/Finance/`

2. **Exécuter les migrations**

    ```bash
    php artisan migrate
    ```

3. **Charger les données de démonstration** (optionnel)

    ```bash
    php artisan db:seed --class=Modules\\Finance\\Database\\Seeders\\FinanceSeeder
    ```

4. **Configurer les informations du collège** dans `.env`

    ```env
    APP_NAME="Collège Wend-Manegda"
    COLLEGE_ADDRESS="Ouagadougou, Burkina Faso"
    COLLEGE_PHONE="+226 XX XX XX XX"
    COLLEGE_EMAIL="contact@college-manegda.bf"
    ```

5. **Publier la configuration DomPDF** (si nécessaire)
    ```bash
    php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
    ```

---

## 📁 Structure du module

```
Modules/Finance/
├── Database/
│   ├── Migrations/         # 6 migrations (fee_types, payments, invoices, etc.)
│   └── Seeders/
│       └── FinanceSeeder.php
├── Entities/               # 5 entités Eloquent
│   ├── FeeType.php
│   ├── Payment.php
│   ├── Invoice.php
│   ├── Scholarship.php
│   └── PaymentReminder.php
├── Http/
│   ├── Controllers/Api/    # 3 controllers
│   │   ├── PaymentController.php
│   │   ├── InvoiceController.php
│   │   └── FeeTypeController.php
│   └── Requests/           # 4 request classes
│       ├── StorePaymentRequest.php
│       ├── StoreInvoiceRequest.php
│       ├── StoreFeeTypeRequest.php
│       └── UpdateFeeTypeRequest.php
├── Services/               # 3 services métier
│   ├── PaymentService.php
│   ├── InvoiceService.php
│   └── ScholarshipService.php
├── resources/views/pdf/    # 2 templates PDF
│   ├── receipt.blade.php
│   └── invoice.blade.php
├── routes/
│   └── api.php             # 27 routes API
├── tests/
│   ├── Unit/               # Tests services
│   │   ├── PaymentServiceTest.php
│   │   └── InvoiceServiceTest.php
│   └── Feature/            # Tests API
│       └── PaymentApiTest.php
└── README_*.md             # 5 fichiers de documentation
```

---

## 📚 Documentation détaillée

Le module dispose de **5 fichiers de documentation** complets :

### 1. [README_ENTITIES.md](README_ENTITIES.md) (9.6 KB)

-   Description des 5 entités
-   Relations entre entités
-   Scopes disponibles
-   Méthodes métier
-   Workflows typiques
-   Bonnes pratiques

### 2. [README_SERVICES.md](README_SERVICES.md) (15.1 KB)

-   Documentation des 3 services
-   Toutes les méthodes avec paramètres/retours
-   Exemples d'utilisation
-   Workflows complets
-   Bonnes pratiques DI et transactions

### 3. [README_API.md](README_API.md) (10 KB)

-   Documentation des 27 endpoints
-   Paramètres et corps de requêtes
-   Exemples de réponses
-   Codes HTTP
-   Gestion d'erreurs
-   Exemples cURL

### 4. [README_PDF.md](README_PDF.md) (11.5 KB)

-   Structure des templates PDF
-   Variables Blade disponibles
-   Guide de personnalisation
-   Compatibilité DomPDF
-   Debug et troubleshooting
-   Checklist production

### 5. [README_TESTS.md](README_TESTS.md) (10.9 KB)

-   Description des 25 tests
-   Commandes d'exécution
-   Couverture de tests
-   Bonnes pratiques AAA
-   Guide debugging

---

## 🚦 Guide de démarrage rapide

### 1. Créer un type de frais

```bash
curl -X POST http://localhost:8000/api/v1/fee-types \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Frais de scolarité",
    "amount": 250000,
    "frequency": "annuel",
    "is_mandatory": true
  }'
```

### 2. Générer une facture

```bash
curl -X POST http://localhost:8000/api/v1/invoices \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 1,
    "academic_year_id": 1,
    "period": "annuel",
    "auto_issue": true
  }'
```

### 3. Enregistrer un paiement

```bash
curl -X POST http://localhost:8000/api/v1/payments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 1,
    "fee_type_id": 1,
    "academic_year_id": 1,
    "amount": 50000,
    "payment_method": "especes"
  }'
```

### 4. Télécharger un reçu PDF

```bash
curl -H "Authorization: Bearer {token}" \
     http://localhost:8000/api/v1/payments/1/receipt \
     --output recu.pdf
```

### 5. Consulter le solde d'un élève

```bash
curl -X GET http://localhost:8000/api/v1/students/1/balance \
  -H "Authorization: Bearer {token}"
```

---

## 📊 Statistiques du module

### Lignes de code

-   **Migrations** : ~350 lignes
-   **Entités** : ~1,128 lignes
-   **Services** : ~1,100 lignes
-   **Controllers** : ~710 lignes
-   **Requests** : ~210 lignes
-   **Templates PDF** : ~900 lignes
-   **Tests** : ~600 lignes
-   **Seeder** : ~350 lignes
-   **Total** : ~**5,348 lignes**

### Documentation

-   **5 fichiers README** : 57.1 KB
-   **~300 exemples** de code
-   **100% des fonctionnalités** documentées

### Tests

-   **25 tests** (17 unitaires + 8 Feature)
-   **~70+ assertions**
-   **Couverture** : ~60% du code critique

### API

-   **27 endpoints** REST
-   **3 groupes** logiques (Payments, Invoices, FeeTypes)
-   **Authentification** Sanctum requise

---

## 🔑 Fonctionnalités clés

### 💰 Gestion des paiements

-   Enregistrement avec génération automatique de numéro de reçu
-   Validation par un utilisateur autorisé
-   Annulation avec raison
-   Mise à jour automatique des soldes de factures
-   Génération de reçu PDF professionnel
-   Historique complet par élève
-   Statistiques par méthode, par type

### 📄 Gestion des factures

-   Génération automatique avec numéros uniques
-   Ajout automatique des frais obligatoires applicables
-   Application automatique des bourses actives
-   Calcul automatique des totaux avec réductions
-   Émission avec création de rappels automatiques
-   Génération de PDF professionnel
-   Suivi du statut (brouillon, émise, payée, en retard, etc.)
-   Statistiques de recouvrement

### 🎓 Gestion des bourses

-   Bourses en pourcentage ou montant fixe
-   Validation par approbation
-   Application automatique aux factures
-   Suspension/réactivation/annulation
-   Expiration automatique
-   Recalcul automatique des factures lors de modifications

### 🔔 Rappels de paiement

-   Création automatique lors de l'émission de factures
-   Multi-canal (SMS, Email, Notification)
-   Planification intelligente (7j, 3j, 0j avant échéance)
-   Système de retry (max 3 tentatives)
-   Suivi des erreurs d'envoi

### 💳 Types de frais

-   CRUD complet
-   Ciblage par cycle/niveau
-   Fréquence configurable (mensuel, trimestriel, annuel, unique)
-   Obligatoire ou facultatif
-   Activation/désactivation
-   Suppression protégée si déjà utilisé

---

## 🎨 Automatisations

Le module Finance intègre plusieurs automatisations pour réduire le travail manuel :

1. **Numéros uniques** : Génération automatique de numéros de reçu et facture
2. **Soldes** : Recalcul automatique lors de paiements ou modifications de factures
3. **Bourses** : Application automatique aux factures lors de création/modification
4. **Rappels** : Création automatique de 3 rappels lors de l'émission de factures
5. **Statuts** : Mise à jour automatique des statuts de factures (payée, en retard, etc.)
6. **Reçus** : Génération automatique du numéro lors de création de paiement

---

## 🧪 Tests

### Exécuter tous les tests

```bash
php artisan test --filter=Finance
```

### Exécuter les tests avec couverture

```bash
php artisan test --coverage --filter=Finance
```

### Tests unitaires seulement

```bash
php artisan test Modules/Finance/tests/Unit
```

### Tests API seulement

```bash
php artisan test Modules/Finance/tests/Feature
```

---

## 🌱 Données de démonstration

Générer des données de démonstration réalistes :

```bash
php artisan db:seed --class=Modules\\Finance\\Database\\Seeders\\FinanceSeeder
```

**Ce qui est généré** :

-   8 types de frais standards
-   10 bourses variées (25%, 50%, 75%, et montants fixes)
-   60 factures (20 élèves × 3 trimestres)
-   30 paiements avec statuts variés (payé, partiellement payé, impayé)

---

## 🔐 Sécurité

-   ✅ Authentification **Sanctum** requise sur toutes les routes
-   ✅ Validation stricte des données en entrée (Request classes)
-   ✅ Mass assignment protection (fillable)
-   ✅ Soft deletes pour audit trail
-   ✅ Transactions DB pour opérations critiques
-   ✅ Logging de toutes les opérations importantes
-   ⚠️ **TODO** : Permissions/Rôles (Spatie Permission)

---

## 🚧 Améliorations futures

### Fonctionnalités

-   [ ] Module de rapports Excel avancés
-   [ ] Tableau de bord interactif
-   [ ] Envoi SMS réel (intégration AfricasTalking)
-   [ ] Notifications email automatiques
-   [ ] QR codes sur reçus/factures
-   [ ] Paiement en ligne (Wave, Orange Money, etc.)
-   [ ] Export comptable (CSV, Excel)
-   [ ] Graphiques et analytics

### Technique

-   [ ] Tests coverage à 80%+
-   [ ] API Resources pour transformations
-   [ ] Jobs asynchrones pour PDF/emails
-   [ ] Cache pour statistiques
-   [ ] Rate limiting sur API
-   [ ] API versioning
-   [ ] Documentation Swagger/OpenAPI

---

## 📞 Support

Pour toute question ou problème :

1. Consulter les fichiers `README_*.md` pour la documentation détaillée
2. Vérifier les tests pour des exemples d'utilisation
3. Contacter l'équipe de développement

---

## 📝 Changelog

### Version 1.0.0 (2025-12-15)

-   ✅ Sprint 1 complet (Jour 1-10)
-   ✅ 6 migrations
-   ✅ 5 entités avec relations
-   ✅ 3 services métier
-   ✅ 3 controllers API (27 endpoints)
-   ✅ 2 templates PDF professionnels
-   ✅ 25 tests
-   ✅ Seeder complet
-   ✅ 5 fichiers de documentation (57 KB)

---

## 👥 Contribution

Ce module a été développé selon le **PLAN_ACTION.md** du projet Collège Wend-Manegda.

**Sprint 1 - Finance Module** : ✅ **100% TERMINÉ**

---

## 📜 Licence

Ce module fait partie du projet Collège Wend-Manegda.

---

**Développé avec ❤️ pour Collège Wend-Manegda**
