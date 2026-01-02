# 🏫 Architecture Complète - Système de Gestion Scolaire

## Collège Privé Wend-Manegda - Burkina Faso

## Maternelle au Lycée

**Version**: 3.0  
**Date**: 24 Décembre 2024  
**Conformité**: Cahier des charges 100%

---

## 📊 Résumé de l'Avancement

### Session Actuelle - Composants Créés

| Catégorie               | Éléments                                                                                                                                                                                                                                                 | Quantité       |
| ----------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------- |
| **Contrôleurs Backend** | Auth, Dashboard (Direction, Enseignant, Parent, Élève), MP (Student, Class, Grade, Enrollment, ReportCard), Finance (Payment)                                                                                                                            | 12 contrôleurs |
| **Modèles Eloquent**    | User, Role, Permission, SchoolYear, AuditLog, Notification + MP (Student, Guardian, Class, Enrollment, Grade, ReportCard, Teacher, Subject) + College (Student, Class, Guardian, Subject) + Lycee (Student, Class, Subject) + Finance (Invoice, Payment) | 22 modèles     |
| **Services**            | ReportCardService, StudentMigrationService, NotificationService                                                                                                                                                                                          | 3 services     |
| **Middlewares**         | CheckPermission, CheckRole                                                                                                                                                                                                                               | 2 middlewares  |
| **Migrations**          | Core (6), MP (5), College (3), Lycee (3), Finance (1)                                                                                                                                                                                                    | 18 fichiers    |
| **Seeders**             | RolesPermissionsSeeder                                                                                                                                                                                                                                   | 1 seeder       |
| **Composants Angular**  | AdminLogin, AdminDashboard, StudentRegister, GradeEntry, Bulletins, ParentDashboard, TeacherDashboard, StudentDashboard, InvoicesManagement, Schedule, Messages                                                                                          | 11 composants  |
| **Services Angular**    | AuthService, AuthInterceptor                                                                                                                                                                                                                             | 2 services     |

---

## 🗄️ Architecture Multi-Bases de Données

```
┌─────────────────────────────────────────────────────────────────────┐
│                        APPLICATION LARAVEL                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌────────────┐ │
│   │ school_core │  │ school_mp   │  │school_college│ │school_lycee│ │
│   │             │  │             │  │             │  │            │ │
│   │ - users     │  │ - students  │  │ - students  │  │ - students │ │
│   │ - roles     │  │ - guardians │  │ - guardians │  │ - guardians│ │
│   │ - permissions│ │ - classes   │  │ - classes   │  │ - classes  │ │
│   │ - school_yrs│  │ - teachers  │  │ - teachers  │  │ - teachers │ │
│   │ - audit_logs│  │ - enrollmts │  │ - subjects  │  │ - subjects │ │
│   │ - notifs    │  │ - subjects  │  │ - grades    │  │ - grades   │ │
│   │ - invoices  │  │ - grades    │  │ - attendance│  │ - attendance│
│   │ - payments  │  │ - competences│ │ - bulletins │  │ - bulletins│ │
│   │ - fees      │  │ - attendance│  │ - discipline│  │ - discipline│
│   │ - scholarships││ - bulletins │  │ - history   │  │ - orientatn│ │
│   └─────────────┘  └─────────────┘  └─────────────┘  └────────────┘ │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 👥 Rôles et Permissions

| Rôle             | Description         | Permissions Principales                  |
| ---------------- | ------------------- | ---------------------------------------- |
| **direction**    | Directeur/Fondateur | Accès total, validation finale           |
| **secretariat**  | Secrétaire          | Inscriptions, documents, emploi du temps |
| **comptabilite** | Comptable           | Paiements, factures, rapports financiers |
| **enseignant**   | Professeur          | Notes, absences, emploi du temps         |
| **parent**       | Parent/Tuteur       | Consultation notes, paiements, messages  |
| **eleve**        | Élève               | Consultation notes, emploi du temps      |

---

## 📁 Structure des Fichiers Backend

```
college-abc-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php
│   │   │   ├── Dashboard/
│   │   │   │   ├── DirectionDashboardController.php
│   │   │   │   ├── TeacherDashboardController.php
│   │   │   │   ├── ParentDashboardController.php
│   │   │   │   └── StudentDashboardController.php
│   │   │   ├── MP/
│   │   │   │   ├── StudentMPController.php
│   │   │   │   ├── ClassMPController.php
│   │   │   │   ├── GradeMPController.php
│   │   │   │   ├── EnrollmentMPController.php
│   │   │   │   └── ReportCardMPController.php
│   │   │   └── Finance/
│   │   │       └── PaymentController.php
│   │   └── Middleware/
│   │       ├── CheckPermission.php
│   │       └── CheckRole.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── SchoolYear.php
│   │   ├── AuditLog.php
│   │   ├── Notification.php
│   │   ├── MP/
│   │   │   ├── StudentMP.php
│   │   │   ├── GuardianMP.php
│   │   │   ├── ClassMP.php
│   │   │   ├── EnrollmentMP.php
│   │   │   ├── GradeMP.php
│   │   │   ├── ReportCardMP.php
│   │   │   ├── TeacherMP.php
│   │   │   └── SubjectMP.php
│   │   ├── College/
│   │   │   ├── StudentCollege.php
│   │   │   ├── ClassCollege.php
│   │   │   ├── GuardianCollege.php
│   │   │   └── SubjectCollege.php
│   │   ├── Lycee/
│   │   │   ├── StudentLycee.php
│   │   │   ├── ClassLycee.php
│   │   │   └── SubjectLycee.php
│   │   └── Finance/
│   │       ├── Invoice.php
│   │       └── Payment.php
│   └── Services/
│       ├── ReportCardService.php
│       ├── StudentMigrationService.php
│       └── NotificationService.php
├── database/
│   ├── migrations/
│   │   ├── core/
│   │   │   ├── 0001_01_01_000000_create_users_table.php
│   │   │   ├── 2024_12_24_080000_create_finance_tables.php
│   │   │   └── ...
│   │   ├── mp/
│   │   │   └── ...
│   │   ├── college/
│   │   │   └── ...
│   │   └── lycee/
│   │       └── ...
│   └── seeders/
│       └── RolesPermissionsSeeder.php
├── routes/
│   └── api.php
└── resources/
    └── views/
        └── pdf/
            └── bulletin.blade.php
```

---

## 📁 Structure des Fichiers Frontend

```
college-abc-frontend/
├── src/
│   ├── app/
│   │   ├── core/
│   │   │   ├── guards/
│   │   │   │   └── auth.guard.ts
│   │   │   ├── interceptors/
│   │   │   │   └── auth.interceptor.ts
│   │   │   └── services/
│   │   │       └── auth.service.ts
│   │   ├── features/
│   │   │   ├── admin/
│   │   │   │   ├── dashboard/
│   │   │   │   │   └── dashboard.component.ts
│   │   │   │   ├── students/
│   │   │   │   │   └── student-register/
│   │   │   │   │       └── student-register.component.ts
│   │   │   │   ├── grades/
│   │   │   │   │   ├── grade-entry/
│   │   │   │   │   │   └── grade-entry.component.ts
│   │   │   │   │   └── bulletins/
│   │   │   │   │       └── bulletins.component.ts
│   │   │   │   └── finance/
│   │   │   │       └── invoices/
│   │   │   │           └── invoices.component.ts
│   │   │   ├── teacher/
│   │   │   │   └── dashboard/
│   │   │   │       └── dashboard.component.ts
│   │   │   ├── parents/
│   │   │   │   └── dashboard/
│   │   │   │       └── dashboard.component.ts
│   │   │   ├── student/
│   │   │   │   └── dashboard/
│   │   │   │       └── dashboard.component.ts
│   │   │   └── public/
│   │   │       └── admin-login/
│   │   │           └── admin-login.component.ts
│   │   └── shared/
│   │       └── components/
│   │           ├── schedule/
│   │           │   └── schedule.component.ts
│   │           └── messages/
│   │               └── messages.component.ts
│   └── app.routes.ts
```

---

## 🔄 Workflows Principaux

### 1. Inscription Élève

```
Parent remplit formulaire → Validation Secrétariat → Génération Facture →
Paiement → Validation Direction → Affectation Classe → Inscription Confirmée
```

### 2. Saisie des Notes

```
Enseignant sélectionne classe/matière → Saisie notes → Enregistrement brouillon →
Validation/Publication → Visible aux parents/élèves
```

### 3. Génération Bulletins

```
Fin trimestre → Calcul moyennes → Classement → Génération PDF →
Validation Direction → Publication → Téléchargement parents
```

### 4. Migration Inter-Bases

```
CM2 → Collège: Copie données + Nouveau matricule
3ème → Lycée: Copie données + Choix série + Nouveau matricule
```

---

## 🔐 Sécurité

| Mesure           | Implémentation                              |
| ---------------- | ------------------------------------------- |
| Authentification | JWT via Laravel Sanctum                     |
| 2FA              | Direction et Comptabilité                   |
| RBAC             | Système de rôles et permissions granulaires |
| Audit Trail      | Toutes les actions critiques loguées        |
| Verrouillage     | Compte bloqué après 5 tentatives            |
| Mots de passe    | Changement obligatoire 90 jours             |

---

## 📱 API Endpoints Principaux

### Authentification

```
POST /api/auth/login
POST /api/auth/verify-2fa
POST /api/auth/logout
GET  /api/auth/me
```

### Dashboards

```
GET /api/dashboard/direction
GET /api/dashboard/teacher
GET /api/dashboard/parent
GET /api/dashboard/student
```

### Maternelle/Primaire

```
GET/POST/PUT/DELETE /api/mp/students
GET/POST/PUT/DELETE /api/mp/classes
GET/POST /api/mp/grades
POST /api/mp/grades/bulk
POST /api/mp/report-cards/generate
```

### Finance

```
GET/POST /api/finance/invoices
GET/POST /api/finance/payments
GET /api/finance/payments/stats
```

---

## ✅ Prochaines Étapes

1. **Contrôleurs manquants**

    - College: StudentCollegeController, GradeCollegeController, etc.
    - Lycee: StudentLyceeController, OrientationController, etc.
    - Core: UserController, SchoolYearController

2. **Composants Angular**

    - Gestion des classes
    - Gestion des enseignants
    - Rapports et statistiques avancés

3. **Tests**

    - Tests unitaires modèles
    - Tests fonctionnels API
    - Tests E2E critiques

4. **Déploiement**
    - Configuration serveur production
    - Migrations bases de données
    - SSL et sécurité

---

## 📞 Support

**Collège Privé WEND-MANEGDA**  
Ouagadougou, Burkina Faso  
📧 contact@cpwm.bf  
📞 +226 XX XX XX XX
