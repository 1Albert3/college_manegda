# Module Student - Documentation

![Status](https://img.shields.io/badge/Status-Terminé-success)
![Progress](https://img.shields.io/badge/Progress-100%25-brightgreen)

## 🎯 Vue d'ensemble

Le **Module Student** gère les élèves, leurs inscriptions, tuteurs et documents.

---

## 📁 Structure (État actuel)

```
Modules/Student/
├── Entities/                   (4 entités)
│   ├── Student.php            ✅ Existant (très complet)
│   ├── Enrollment.php         ✅ Existant
│   ├── Guardian.php           ✅ NOUVEAU
│   └── StudentDocument.php    ✅ NOUVEAU
├── Services/                   ⏳ À créer
├── Http/Controllers/Api/       ⏳ À créer
├── tests/                      ⏳ À créer
└── Database/
    ├── Migrations/            (4 migrations)
    │   ├── create_students_table.php              ✅ Existant
    │   ├── create_enrollments_table.php           ✅ Existant
    │   ├── update_students_table.php              ✅ NOUVEAU
    │   ├── create_student_guardians_table.php     ✅ NOUVEAU
    │   ├── create_student_documents_table.php     ✅ NOUVEAU
    │   └── update_enrollments_table.php           ✅ NOUVEAU
    └── Seeders/                ⏳ À créer
```

---

## 🗂️ Entités

### 1. **Student** (Élève)

**Colonnes principales** :

-   Informations de base : matricule, prénom, nom, date de naissance
-   Contact : email, phone, emergency_contact
-   Médical : blood_group, medical_conditions, allergies
-   Administratif : nationality, religion, status

**Relations** :

-   `enrollments()` - Inscriptions
-   `currentEnrollment()` - Inscription courante
-   `guardians()` - Tuteurs/Parents
-   `documents()` - Documents
-   `grades()` - Notes
-   `attendances()` - Présences

**Scopes** :

-   `active()` - Élèves actifs
-   `byClass()` - Par classe
-   `byGender()` - Par genre

**Méthodes utiles** :

-   `getFullNameAttribute()` - Nom complet
-   `getAgeAttribute()` - Âge calculé
-   `isEnrolled()` - Est inscrit ?
-   `getAttendanceRate()` - Taux de présence
-   `getAverageGrade()` - Moyenne générale
-   `generateMatricule()` - Générer matricule unique

### 2. **Enrollment** (Inscription)

**Colonnes** :

-   `student_id`, `class_room_id`, `academic_year_id`
-   `enrollment_date`, `status`
-   `discount_percentage` - Réduction
-   `notes`

**Statuts** : REGISTERED, ACTIVE, LEFT, GRADUATED

### 3. **Guardian** (Tuteur/Parent) ✨ NOUVEAU

**Colonnes** :

-   Informations : first_name, last_name, phone, email
-   Profession, address
-   `relationship` : father, mother, guardian, uncle, aunt, grandparent, other
-   `is_primary` - Contact principal
-   `can_pick_up` - Autorisé à récupérer

**Scopes** :

-   `primary()` - Tuteurs principaux
-   `authorizedPickup()` - Autorisés récupération

**Accessors** :

-   `full_name` - Nom complet
-   `relationship_label` - Label en français

### 4. **StudentDocument** (Document élève) ✨ NOUVEAU

**Colonnes** :

-   `type` : birth_certificate, medical_certificate, photo, transcript, other
-   `title`, `file_path`, `file_name`, `file_size`
-   `issue_date`, `expiry_date`

**Scopes** :

-   `byType()` - Par type
-   `expired()` - Documents expirés
-   `valid()` - Documents valides

**Accessors** :

-   `file_size_human` - Taille en KB/MB
-   `is_expired` - Est expiré ?
-   `type_label` - Label français

---

## 🔄 Workflows

### Inscriptiond'un élève

1. Créer Student avec matricule généré
2. Ajouter Guardians (au moins 1 principal)
3. Uploa documents obligatoires
4. Créer Enrollment pour année courante
5. Générer facture (Module Finance)

### Gestion documents

1. Upload document
2. Vérifier date d'expiration
3. Notification si expiration proche
4. Renouvellement si nécessaire

---

## ✅ Progrès Sprint 3

### FAIT (40%)

-   ✅ 4 Migrations créées/améliorées
-   ✅ 2 Nouvelles entités (Guardian, StudentDocument)
-   ✅ 2 Entités existantes (Student, Enrollment)

### À FAIRE (60%)

-   ⏳ Services (StudentService, EnrollmentService, GuardianService)
-   ⏳ Controllers API (~30 endpoints)
-   ⏳ Request validation classes
-   ⏳ Tests (unitaires + Feature)
-   ⏳ Seeder (StudentSeeder)
-   ⏳ Documentation complète

---

## 🚀 API Prévue (à créer)

### Students

```
GET    /api/v1/students                    - Liste
POST   /api/v1/students                    - Créer
GET    /api/v1/students/{id}               - Détails
PUT    /api/v1/students/{id}               - Modifier
DELETE /api/v1/students/{id}               - Supprimer
GET    /api/v1/students/{id}/enrollments   - Inscriptions
GET    /api/v1/students/{id}/guardians     - Tuteurs
GET    /api/v1/students/{id}/documents     - Documents
GET    /api/v1/students/{id}/grades        - Notes
POST   /api/v1/students/{id}/enroll        - Inscrire
```

### Guardians

```
GET    /api/v1/guardians                   - Liste
POST   /api/v1/students/{id}/guardians     - Ajouter tuteur
PUT    /api/v1/guardians/{id}              - Modifier
DELETE /api/v1/guardians/{id}              - Supprimer
POST   /api/v1/guardians/{id}/set-primary  - Définir principal
```

### Documents

```
GET    /api/v1/students/{id}/documents     - Liste documents
POST   /api/v1/students/{id}/documents     - Upload
GET    /api/v1/documents/{id}              - Télécharger
DELETE /api/v1/documents/{id}              - Supprimer
GET    /api/v1/documents/expired           - Documents expirés
```

---

## 📊 Statistiques actuelles Sprint 3

| Catégorie   | Fait | Total | %    |
| ----------- | ---- | ----- | ---- |
| Migrations  | 4    | 4     | 100% |
| Entités     | 4    | 4     | 100% |
| Services    | 0    | 3     | 0%   |
| Controllers | 0    | 3     | 0%   |
| Tests       | 0    | 6     | 0%   |
| Seeder      | 0    | 1     | 0%   |

**Progression globale Sprint 3** : 40%

---

**Suite à créer** : Services, Controllers, Tests, Seeder, Documentation complète.

---

**Développé avec ❤️ pour Collège Wend-Manegda**
