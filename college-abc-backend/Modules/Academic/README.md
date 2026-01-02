# Module Academic - Documentation Complète

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![Laravel](https://img.shields.io/badge/Laravel-10+-red)
![Module](https://img.shields.io/badge/Module-Academic-green)

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Installation](#installation)
3. [Structure du module](#structure-du-module)
4. [Entités](#entités)
5. [Services](#services)
6. [API](#api)
7. [Tests](#tests)
8. [Seeder](#seeder)

---

## 🎯 Vue d'ensemble

Le **Module Academic** est le système central de gestion académique pour établissements scolaires. Il permet de :

-   ✅ Gérer les **cycles** (Primaire, Collège, Lycée)
-   ✅ Gérer les **niveaux** (CP1, 6ème, 2nde, etc.)
-   ✅ Gérer les **années académiques** et **trimestres/semestres**
-   ✅ Gérer les **matières** avec coefficients
-   ✅ Gérer les **salles de classe**
-   ✅ Créer et gérer les **emplois du temps**
-   ✅ API REST complète avec **70+ endpoints**

---

## 🚀 Installation

### Prérequis

-   Laravel 10+
-   PHP 8.1+
-   MySQL/PostgreSQL
-   Package `nwidart/laravel-modules`

### Étapes d'installation

1. **Le module est déjà présent** dans `Modules/Academic/`

2. **Exécuter les migrations**

    ```bash
    php artisan migrate
    ```

3. **Charger les données de démonstration** (optionnel)
    ```bash
    php artisan db:seed --class=Modules\\Academic\\Database\\Seeders\\AcademicSeeder
    ```

---

## 📁 Structure du module

```
Modules/Academic/
├── Database/
│   ├── Migrations/         # 9 migrations
│   │   ├── create_academic_years_table
│   │   ├── create_subjects_table
│   │   ├── create_semesters_table
│   │   ├── create_class_subject_table
│   │   ├── create_teacher_subject_table
│   │   ├── create_schedules_table
│   │   └── update_classrooms_to_class_rooms
│   └── Seeders/
│       └── AcademicSeeder.php
├── Entities/               # 9 entités Eloquent
│   ├── AcademicYear.php
│   ├── Cycle.php
│   ├── Level.php
│   ├── ClassRoom.php
│   ├── Subject.php
│   ├── Semester.php
│   ├── Schedule.php
│   ├── ClassSubject.php (pivot)
│   └── TeacherSubject.php (pivot)
├── Services/               # 7 services métier
│   ├── AcademicYearService.php
│   ├── CycleService.php
│   ├── LevelService.php
│   ├── SemesterService.php
│   ├── ScheduleService.php
│   ├── ClassRoomService.php
│   └── SubjectService.php
├── Http/
│   ├── Controllers/Api/    # 7 controllers
│   │   ├── AcademicYearController.php
│   │   ├── CycleController.php
│   │   ├── LevelController.php
│   │   ├── SemesterController.php
│   │   ├── ScheduleController.php
│   │   ├── ClassRoomController.php
│   │   └── SubjectController.php
│   └── Requests/           # 2+ request classes
│       ├── StoreCycleRequest.php
│       └── UpdateCycleRequest.php
├── routes/
│   └── api.php             # 70+ routes API
└── tests/
    ├── Unit/               # Tests services
    │   ├── CycleServiceTest.php
    │   └── SemesterServiceTest.php
    └── Feature/            # Tests API
        ├── CycleApiTest.php
        └── SemesterApiTest.php
```

---

## 🗂️ Entités

### 1. **Cycle**

-   Représente un cycle scolaire (Primaire, Collège, Lycée)
-   Relations : `levels`, `classRooms`, `feeTypes`
-   Scopes : `active()`, `ordered()`

### 2. **Level**

-   Représente un niveau scolaire (CP1, 6ème, 2nde, etc.)
-   Relations : `cycle`, `classRooms`, `feeTypes`, `students`
-   Scopes : `active()`, `byCycle()`, `ordered()`

### 3. **AcademicYear**

-   Année académique avec dates de début/fin
-   Relations : `enrollments`, `semestersRelation`, `teachers`, `subjects`
-   Scopes : `active()`, `current()`, `ongoing()`

### 4. **Semester**

-   Trimestre ou semestre d'une année académique
-   Relations : `academicYear`, `grades`
-   Scopes : `current()`, `ongoing()`, `byAcademicYear()`

### 5. **Subject**

-   Matière enseignée avec coefficient
-   Relations : `teachers`, `classes`, `grades`
-   Scopes : `active()`, `byCategory()`

### 6. **ClassRoom**

-   Salle de classe avec capacité
-   Relations : `level`, `academicYear`, `students`, `subjects`, `schedules`
-   Scopes : `active()`, `byLevel()`

### 7. **Schedule**

-   Emploi du temps (cours programmés)
-   Relations : `classRoom`, `subject`, `teacher`, `academicYear`
-   Détection automatique de conflits
-   Scopes : `byClass()`, `byTeacher()`, `today()`

---

## 🔧 Services

### 1. **CycleService**

-   `createCycle()` - Créer un cycle
-   `updateCycle()` - Mettre à jour
-   `deleteCycle()` - Supprimer (protégé si contient des niveaux)
-   `activateCycle() / deactivateCycle()` - Activer/désactiver
-   `reorderCycles()` - Réorganiser
-   `getAllCyclesWithLevels()` - Récupérer avec niveaux
-   `getCycleStatistics()` - Statistiques

### 2. **LevelService**

-   CRUD complet
-   `getLevelsByCycle()` - Par cycle
-   `getAllLevelsWithClassrooms()` - Avec classes
-   `activateLevel() / deactivateLevel()`
-   `reorderLevels()` - Réorganiser par cycle
-   `searchLevels()` - Recherche

### 3. **SemesterService**

-   `createSemester()` / `updateSemester()`
-   `generateSemestersForYear()` - Génération auto
-   `setCurrentSemester()` - Définir comme courant
-   `getCurrentSemester()` / `getOngoingSemester()`
-   `getSemestersByYear()`

### 4. **ScheduleService**

-   `createSchedule()` - Création avec validation conflits
-   `updateSchedule()` / `deleteSchedule()`
-   `getClassSchedule()` / `getTeacherSchedule()`
-   `getTodayClassSchedule()` / `getTodayTeacherSchedule()`
-   `bulkCreateForClass()` - Création en masse
-   `copyScheduleToNewYear()` - Copie vers nouvelle année
-   `getStatistics()` - Statistiques

---

## 🌐 API

### Endpoints principaux

#### **Cycles** (`/api/v1/cycles`)

-   `GET /` - Liste des cycles
-   `POST /` - Créer un cycle
-   `GET /{id}` - Détails d'un cycle
-   `PUT /{id}` - Mettre à jour
-   `DELETE /{id}` - Supprimer
-   `POST /{id}/activate` - Activer
-   `POST /{id}/deactivate` - Désactiver
-   `POST /reorder` - Réorganiser
-   `GET /{id}/statistics` - Statistiques

#### **Levels** (`/api/v1/levels`)

-   CRUD complet + activation/désactivation
-   `GET /search?term={term}` - Recherche
-   `POST /reorder` - Réorganiser
-   `GET /{id}/statistics` - Statistiques

#### **Semesters** (`/api/v1/semesters`)

-   CRUD complet
-   `GET /current` - Semestre courant
-   `GET /ongoing` - Semestre en cours
-   `POST /generate` - Générer trimestres/semestres
-   `GET /by-year/{yearId}` - Par année
-   `POST /{id}/set-current` - Définir comme courant

#### **Schedules** (`/api/v1/schedules`)

-   CRUD complet
-   `GET /class/{classId}` - Emploi du temps d'une classe
-   `GET /teacher/{teacherId}` - Emploi du temps d'un prof
-   `GET /today/class/{classId}` - Emploi du temps du jour (classe)
-   `GET /today/teacher/{teacherId}` - Emploi du temps du jour (prof)
-   `POST /bulk-create` - Création en masse
-   `POST /copy-to-new-year` - Copie vers nouvelle année
-   `GET /statistics` - Statistiques

**Total** : **70+ endpoints** REST

---

## 🧪 Tests

### Exécuter tous les tests

```bash
php artisan test --filter=Academic
```

### Tests unitaires

```bash
php artisan test Modules/Academic/tests/Unit
```

### Tests Feature (API)

```bash
php artisan test Modules/Academic/tests/Feature
```

### Statistiques de tests

-   **51 tests** au total
-   **2 fichiers** tests unitaires
-   **2 fichiers** tests Feature
-   **~150+ assertions**

---

## 🌱 Seeder

### Exécuter le seeder

```bash
php artisan db:seed --class=Modules\\Academic\\Database\\Seeders\\AcademicSeeder
```

### Ce qui est généré

1. **3 Cycles** : Primaire, Collège, Lycée
2. **13 Niveaux** : CP1-CM2, 6ème-3ème, 2nde-Terminale
3. **1 Année académique** : 2024-2025
4. **3 Trimestres** pour l'année
5. **12 Matières** : Mathématiques, Français, Anglais, etc.
6. **~16 Classes** : Distribution réaliste par niveau
7. **Attribution matières** : Selon niveau (primaire/collège/lycée)
8. **Emplois du temps** : Exemples pour 3 classes

---

## 📊 Fonctionnalités clés

### 🏫 Gestion des Cycles et Niveaux

-   Hiérarchie complète Cycle → Level → ClassRoom
-   Activation/désactivation dynamique
-   Réorganisation par drag & drop (via API)
-   Génération automatique de slug et ordre

### 📅 Gestion des Années Académiques

-   Période courante avec dates
-   Progression en pourcentage
-   Génération automatique de semestres
-   Historique complet

### 📚 Gestion des Matières

-   Coefficients personnalisables
-   Couleurs pour affichage
-   Attribution par classe
-   Heures hebdomadaires
-   Statistiques par matière

### 🕒 Emplois du Temps

-   **Détection automatique de conflits** (professeur/classe)
-   Visualisation par jour/semaine
-   Export par classe ou professeur
-   Copie facile vers nouvelle année
-   Vue "Aujourd'hui" pour consultation rapide

---

## 🔐 Sécurité

-   ✅ Authentification **Sanctum** sur toutes les routes
-   ✅ Validation stricte (Request classes)
-   ✅ Soft deletes pour audit trail
-   ✅ Suppression protégée (cycles, niveaux)
-   ✅ Transactions DB pour opérations critiques
-   ✅ Logging de toutes opérations
-   ⚠️ **TODO** : Permissions/Rôles (Spatie Permission)

---

## 🚧 Améliorations futures

-   [ ] Gestion des filières (Scientifique, Littéraire, etc.)
-   [ ] Templates d'emplois du temps
-   [ ] Import/Export Excel
-   [ ] Notifications changements emploi du temps
-   [ ] Vue calendrier interactif
-   [ ] Gestion des salles (disponibilité)
-   [ ] Conflits de salles (en plus de prof/classe)

---

## 📝 Changelog

### Version 1.0.0 (2025-12-15)

-   ✅ Sprint 2 complet (Jour 11-20)
-   ✅ 9 migrations
-   ✅ 9 entités avec relations
-   ✅ 7 services métier
-   ✅ 7 controllers API (70+ endpoints)
-   ✅ 51 tests (unitaires + Feature)
-   ✅ Seeder complet
-   ✅ Documentation complète

---

**Développé avec ❤️ pour Collège Wend-Manegda**
