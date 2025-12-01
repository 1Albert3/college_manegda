# Plan de Développement - Application Angular College ABC

## Vue d'ensemble

Ce document présente le plan de développement pour la partie frontend Angular de l'application de gestion scolaire College ABC. L'application frontend communiquera avec l'API Laravel existante via des requêtes HTTP authentifiées (Sanctum).

---

## 1. Architecture et Structure du Projet

### 1.1 Stack Technique Recommandée

- **Framework**: Angular 17+ (standalone components)
- **State Management**: NgRx ou RxJS avec Services
- **HTTP Client**: HttpClient d'Angular avec Interceptors
- **Authentification**: JWT avec Sanctum (Laravel)
- **UI Framework**: Angular Material ou PrimeNG
- **Routing**: Angular Router avec Guards
- **Forms**: Reactive Forms
- **Charts**: Chart.js ou ngx-charts
- **Internationalization**: @angular/localize (i18n)
- **Build Tool**: Angular CLI avec Vite (optionnel)

### 1.2 Structure des Dossiers

```
college-abc-frontend/
├── src/
│   ├── app/
│   │   ├── core/                    # Services et fonctionnalités centrales
│   │   │   ├── guards/              # Route guards
│   │   │   ├── interceptors/        # HTTP interceptors
│   │   │   ├── services/            # Services partagés
│   │   │   ├── models/              # Interfaces et types
│   │   │   └── constants/           # Constantes
│   │   ├── shared/                  # Composants réutilisables
│   │   │   ├── components/          # Composants UI génériques
│   │   │   ├── directives/          # Directives personnalisées
│   │   │   ├── pipes/               # Pipes personnalisés
│   │   │   └── layout/              # Layout components
│   │   ├── features/                 # Modules fonctionnels
│   │   │   ├── auth/                # Module d'authentification
│   │   │   ├── dashboard/           # Tableau de bord
│   │   │   ├── academic/            # Gestion académique
│   │   │   ├── students/            # Gestion des étudiants
│   │   │   ├── grades/              # Gestion des notes
│   │   │   ├── attendance/          # Gestion de la présence
│   │   │   └── profile/             # Profil utilisateur
│   │   ├── config/                  # Configuration
│   │   └── app.component.ts
│   ├── assets/
│   │   ├── images/
│   │   ├── icons/
│   │   └── styles/
│   ├── environments/
│   └── styles.scss
└── angular.json
```

---

## 2. Configuration Initiale

### 2.1 Setup du Projet

1. **Créer le projet Angular**
   ```bash
   ng new college-abc-frontend --routing --style=scss
   cd college-abc-frontend
   ```

2. **Installer les dépendances**
   ```bash
   ng add @angular/material
   npm install @ngrx/store @ngrx/effects @ngrx/store-devtools
   npm install chart.js ng2-charts
   npm install @angular/localize
   ```

3. **Configuration de l'environnement**
   - Créer `environments/environment.ts` et `environment.prod.ts`
   - Définir l'URL de l'API: `http://localhost:8000/api`
   - Configuration CORS côté Laravel

### 2.2 Services Core

#### 2.2.1 Service API
- `api.service.ts`: Service de base pour les appels HTTP
- Configuration du baseURL
- Gestion des erreurs globales

#### 2.2.2 Service d'Authentification
- `auth.service.ts`: Gestion du token, login, logout
- Stockage du token (localStorage ou sessionStorage)
- Refresh token mechanism

#### 2.2.3 Interceptors
- `auth.interceptor.ts`: Ajout automatique du token Bearer
- `error.interceptor.ts`: Gestion centralisée des erreurs
- `loading.interceptor.ts`: Gestion de l'indicateur de chargement

#### 2.2.4 Guards
- `auth.guard.ts`: Protection des routes nécessitant une authentification
- `role.guard.ts`: Protection basée sur les rôles (si applicable)

---

## 3. Modules Fonctionnels

### 3.1 Module d'Authentification (`features/auth`)

#### Composants
- `login/login.component.ts`: Formulaire de connexion
- `register/register.component.ts`: Inscription
- `forgot-password/forgot-password.component.ts`: Mot de passe oublié
- `reset-password/reset-password.component.ts`: Réinitialisation

#### Routes
```
/auth/login
/auth/register
/auth/forgot-password
/auth/reset-password
```

#### Fonctionnalités
- Validation des formulaires (Reactive Forms)
- Gestion des erreurs d'authentification
- Redirection après connexion réussie
- Mémorisation de session

**API Endpoints à utiliser:**
- `POST /api/v1/login`
- `POST /api/v1/register`
- `POST /api/v1/forgot-password`
- `POST /api/v1/reset-password`
- `POST /api/v1/logout`

---

### 3.2 Module Dashboard (`features/dashboard`)

#### Composants
- `dashboard/dashboard.component.ts`: Vue principale du tableau de bord

#### Fonctionnalités
- Statistiques générales:
  - Nombre total d'étudiants
  - Nombre de classes
  - Nombre de matières
  - Taux de présence moyen
  - Performances académiques
- Graphiques (Chart.js):
  - Évolution des notes par période
  - Répartition des classes
  - Taux de présence par classe
- Actions rapides:
  - Créer une nouvelle année académique
  - Ajouter un étudiant
  - Créer une évaluation
- Notifications et alertes

**API Endpoints à utiliser:**
- `GET /api/v1/me`
- Stats depuis différents modules

---

### 3.3 Module Académique (`features/academic`)

#### 3.3.1 Gestion des Années Académiques

**Composants:**
- `academic-years/academic-years-list.component.ts`
- `academic-years/academic-year-form.component.ts`
- `academic-years/academic-year-detail.component.ts`

**Fonctionnalités:**
- Liste des années académiques avec pagination et recherche
- Création/édition d'une année académique
- Définir année courante
- Génération automatique des semestres
- Statistiques d'une année académique
- Vue détaillée avec semestres

**API Endpoints:**
- `GET /api/v1/academic-years`
- `POST /api/v1/academic-years`
- `GET /api/v1/academic-years/current`
- `GET /api/v1/academic-years/{id}`
- `PUT /api/v1/academic-years/{id}`
- `DELETE /api/v1/academic-years/{id}`
- `POST /api/v1/academic-years/{id}/set-current`
- `POST /api/v1/academic-years/{id}/generate-semesters`
- `GET /api/v1/academic-years/stats`

#### 3.3.2 Gestion des Matières

**Composants:**
- `subjects/subjects-list.component.ts`
- `subjects/subject-form.component.ts`
- `subjects/subject-detail.component.ts`

**Fonctionnalités:**
- Liste des matières avec filtres (catégorie, niveau)
- Création/édition d'une matière
- Attribution à une classe
- Attribution d'un enseignant
- Gestion des coefficients
- Activation/désactivation en masse
- Statistiques par matière

**API Endpoints:**
- `GET /api/v1/subjects`
- `POST /api/v1/subjects`
- `GET /api/v1/subjects/{id}`
- `PUT /api/v1/subjects/{id}`
- `DELETE /api/v1/subjects/{id}`
- `POST /api/v1/subjects/{id}/assign-to-class`
- `POST /api/v1/subjects/{id}/assign-teacher`
- `GET /api/v1/subjects/by-category/{category}`
- `GET /api/v1/subjects/by-level/{level}`
- `GET /api/v1/subjects/stats`

#### 3.3.3 Gestion des Classes

**Composants:**
- `classes/classes-list.component.ts`
- `classes/class-form.component.ts`
- `classes/class-detail.component.ts`

**Fonctionnalités:**
- Liste des classes avec filtres (niveau, série)
- Création/édition d'une classe
- Inscription d'étudiants à une classe
- Attribution de matières
- Statistiques de présence par classe
- Liste des étudiants d'une classe
- Mise à jour du nombre d'étudiants

**API Endpoints:**
- `GET /api/v1/classes`
- `POST /api/v1/classes`
- `GET /api/v1/classes/{id}`
- `PUT /api/v1/classes/{id}`
- `DELETE /api/v1/classes/{id}`
- `POST /api/v1/classes/{id}/enroll-student`
- `POST /api/v1/classes/{id}/assign-subject`
- `GET /api/v1/classes/{id}/students`
- `GET /api/v1/classes/{id}/subjects`
- `GET /api/v1/classes/{id}/attendance-stats`

**Routes:**
```
/academic/years
/academic/years/new
/academic/years/:id
/academic/subjects
/academic/subjects/new
/academic/subjects/:id
/academic/classes
/academic/classes/new
/academic/classes/:id
```

---

### 3.4 Module Étudiants (`features/students`)

#### Composants
- `students/students-list.component.ts`: Liste avec recherche et filtres
- `students/student-form.component.ts`: Formulaire création/édition
- `students/student-detail.component.ts`: Vue détaillée d'un étudiant
- `students/student-import.component.ts`: Import Excel/CSV
- `students/student-photo-upload.component.ts`: Upload de photo

#### Fonctionnalités
- CRUD complet des étudiants
- Recherche par matricule, nom, prénom
- Filtres (classe, niveau, statut)
- Upload et gestion de photos
- Attribution de parent/tuteur
- Import/Export (Excel, CSV)
- Bulletin de notes
- Statistiques par étudiant
- Historique académique

**API Endpoints:**
- `GET /api/v1/students`
- `POST /api/v1/students`
- `GET /api/v1/students/{id}`
- `PUT /api/v1/students/{id}`
- `DELETE /api/v1/students/{id}`
- `GET /api/v1/students/matricule/{matricule}`
- `POST /api/v1/students/{student}/upload-photo`
- `POST /api/v1/students/{student}/attach-parent`
- `GET /api/v1/students/{student}/report-card`
- `POST /api/v1/students/import`
- `GET /api/v1/students/export`
- `GET /api/v1/students/stats`

**Routes:**
```
/students
/students/new
/students/:id
/students/:id/report-card
```

---

### 3.5 Module Notes (`features/grades`)

#### 3.5.1 Gestion des Évaluations

**Composants:**
- `evaluations/evaluations-list.component.ts`
- `evaluations/evaluation-form.component.ts`
- `evaluations/evaluation-detail.component.ts`

**Fonctionnalités:**
- Liste des évaluations avec filtres (classe, matière, date)
- Création/édition d'une évaluation
- Création en masse d'évaluations
- Démarrage/Completion/Annulation d'une évaluation
- Génération de rapports PDF
- Statistiques par évaluation

**API Endpoints:**
- `GET /api/v1/evaluations`
- `POST /api/v1/evaluations`
- `GET /api/v1/evaluations/{id}`
- `PUT /api/v1/evaluations/{id}`
- `DELETE /api/v1/evaluations/{id}`
- `POST /api/v1/evaluations/{id}/start`
- `POST /api/v1/evaluations/{id}/complete`
- `GET /api/v1/evaluations/{id}/report`
- `POST /api/v1/evaluations/{id}/pdf`
- `GET /api/v1/evaluations/by-teacher/{teacherId}`
- `GET /api/v1/evaluations/by-subject/{subjectId}`
- `GET /api/v1/evaluations/by-class/{classId}`

#### 3.5.2 Gestion des Notes

**Composants:**
- `grades/grades-list.component.ts`
- `grades/grade-entry.component.ts`: Saisie des notes
- `grades/bulk-grade-entry.component.ts`: Saisie en masse
- `grades/grade-report.component.ts`: Rapport de notes
- `grades/report-card.component.ts`: Bulletin de notes

**Fonctionnalités:**
- Saisie individuelle de notes
- Saisie en masse (par évaluation)
- Édition/suppression de notes
- Rapports par étudiant, classe, enseignant
- Génération de bulletins PDF
- Statistiques globales
- Calcul automatique des moyennes

**API Endpoints:**
- `GET /api/v1/grades`
- `POST /api/v1/grades/record`
- `POST /api/v1/grades/bulk-record`
- `GET /api/v1/grades/{id}`
- `PUT /api/v1/grades/{id}`
- `DELETE /api/v1/grades/{id}`
- `GET /api/v1/grades/by-student/{studentId}`
- `GET /api/v1/grades/by-evaluation/{evaluationId}`
- `GET /api/v1/grades/student/{studentId}/report`
- `GET /api/v1/grades/class/{classId}/report`
- `POST /api/v1/grades/student/{studentId}/report-card`
- `GET /api/v1/grades/statistics`
- `GET /api/v1/grades/school-stats`

**Routes:**
```
/grades/evaluations
/grades/evaluations/new
/grades/evaluations/:id
/grades/entry
/grades/reports
/grades/reports/student/:id
/grades/reports/class/:id
```

---

### 3.6 Module Présence (`features/attendance`)

#### Composants
- `attendance/attendance-list.component.ts`
- `attendance/attendance-form.component.ts`: Saisie de présence
- `attendance/attendance-calendar.component.ts`: Vue calendrier
- `attendance/attendance-stats.component.ts`: Statistiques

#### Fonctionnalités
- Saisie de présence (présent/absent/justifié)
- Vue calendrier avec indicateurs
- Liste avec filtres (date, classe, étudiant)
- Statistiques par période
- Export des données
- Alertes pour absences répétées

**API Endpoints:**
- `GET /api/v1/attendances`
- `POST /api/v1/attendances`
- `GET /api/v1/attendances/{id}`
- `PUT /api/v1/attendances/{id}`
- `DELETE /api/v1/attendances/{id}`

**Routes:**
```
/attendance
/attendance/new
/attendance/calendar
/attendance/stats
```

---

### 3.7 Module Profil (`features/profile`)

#### Composants
- `profile/profile.component.ts`: Vue et édition du profil
- `profile/change-password.component.ts`: Changement de mot de passe

#### Fonctionnalités
- Affichage des informations utilisateur
- Édition du profil (nom, email, téléphone)
- Changement de mot de passe
- Historique des activités (optionnel)

**API Endpoints:**
- `GET /api/v1/me`
- `PUT /api/v1/profile`
- `POST /api/v1/change-password`

**Routes:**
```
/profile
/profile/change-password
```

---

## 4. Composants Partagés (`shared`)

### 4.1 Composants UI
- `data-table`: Tableau de données réutilisable avec pagination, tri, recherche
- `confirm-dialog`: Modal de confirmation
- `file-upload`: Composant d'upload de fichiers
- `search-filter`: Barre de recherche et filtres
- `loading-spinner`: Indicateur de chargement
- `empty-state`: État vide avec message
- `breadcrumb`: Fil d'Ariane
- `stats-card`: Carte de statistiques
- `form-field`: Champ de formulaire personnalisé

### 4.2 Layout Components
- `header/header.component.ts`: En-tête avec navigation
- `sidebar/sidebar.component.ts`: Menu latéral
- `footer/footer.component.ts`: Pied de page
- `main-layout/main-layout.component.ts`: Layout principal

### 4.3 Directives
- `has-role.directive.ts`: Affichage conditionnel basé sur les rôles
- `auto-focus.directive.ts`: Focus automatique

### 4.4 Pipes
- `format-date.pipe.ts`: Formatage des dates
- `format-grade.pipe.ts`: Formatage des notes (ex: 15.5/20)
- `truncate.pipe.ts`: Troncature de texte
- `filter.pipe.ts`: Filtrage de listes

---

## 5. Modèles et Interfaces

### 5.1 Modèles à créer

```typescript
// Core
interface User
interface AuthResponse
interface ApiResponse<T>

// Academic
interface AcademicYear
interface Semester
interface Subject
interface ClassRoom

// Student
interface Student
interface Parent

// Grade
interface Evaluation
interface Grade

// Attendance
interface Attendance

// Common
interface PaginatedResponse<T>
interface Stats
```

---

## 6. Gestion d'État (State Management)

### Option 1: NgRx (Recommandé pour grandes applications)

#### Stores
- `auth.store`: État d'authentification
- `academic.store`: Données académiques
- `students.store`: Liste et état des étudiants
- `grades.store`: Évaluations et notes

#### Actions, Reducers, Effects
- Actions pour chaque opération CRUD
- Reducers pour mettre à jour l'état
- Effects pour les effets de bord (appels API)

### Option 2: Services avec RxJS (Plus simple)

- Services avec `BehaviorSubject` pour partager l'état
- Méthodes publiques pour mettre à jour l'état
- Observable pour s'abonner aux changements

---

## 7. Routing et Navigation

### 7.1 Structure des Routes

```typescript
const routes: Routes = [
  { path: '', redirectTo: '/dashboard', pathMatch: 'full' },
  { path: 'auth', loadChildren: () => import('./features/auth/auth.routes') },
  {
    path: '',
    component: MainLayoutComponent,
    canActivate: [AuthGuard],
    children: [
      { path: 'dashboard', component: DashboardComponent },
      { path: 'academic', loadChildren: () => import('./features/academic/academic.routes') },
      { path: 'students', loadChildren: () => import('./features/students/students.routes') },
      { path: 'grades', loadChildren: () => import('./features/grades/grades.routes') },
      { path: 'attendance', loadChildren: () => import('./features/attendance/attendance.routes') },
      { path: 'profile', component: ProfileComponent },
    ]
  },
  { path: '**', component: NotFoundComponent }
];
```

### 7.2 Guards
- `AuthGuard`: Protection des routes authentifiées
- `RoleGuard`: Protection basée sur les rôles (si nécessaire)

---

## 8. Gestion des Formulaires

### 8.1 Stratégie
- Utiliser **Reactive Forms** pour tous les formulaires
- Validateurs personnalisés pour:
  - Email unique
  - Matricule unique
  - Format de téléphone
  - Dates (année académique, évaluations)
  - Notes (min/max selon barème)

### 8.2 Composants de Formulaire
- Réutiliser les composants Material pour:
  - Input, Select, Datepicker, Checkbox, Radio
  - Messages d'erreur cohérents
  - Indicateurs de validation visuels

---

## 9. Gestion des Erreurs

### 9.1 Stratégie Globale
- Interceptor pour capturer toutes les erreurs HTTP
- Affichage de notifications (toast, snackbar)
- Logging des erreurs (console en dev, service en prod)

### 9.2 Types d'Erreurs
- Erreurs réseau (timeout, connexion)
- Erreurs de validation (400)
- Erreurs d'authentification (401)
- Erreurs d'autorisation (403)
- Erreurs serveur (500)

### 9.3 Messages Utilisateur
- Messages clairs et actionnables
- Support multilingue (français/anglais)

---

## 10. Performance et Optimisation

### 10.1 Lazy Loading
- Chargement paresseux des modules fonctionnels
- Code splitting automatique

### 10.2 Optimisation des Images
- Compression des images
- Lazy loading des images

### 10.3 Pagination
- Pagination côté serveur pour toutes les listes
- Limite par défaut: 15-20 items par page

### 10.4 Caching
- Cache des données fréquemment utilisées
- Service de cache avec TTL

### 10.5 Debouncing
- Debounce sur les champs de recherche (300ms)
- Debounce sur les filtres

---

## 11. Tests

### 11.1 Tests Unitaires
- Tests pour chaque service
- Tests pour les composants critiques
- Coverage cible: 70%+

### 11.2 Tests d'Intégration
- Tests des flux principaux
- Tests d'authentification

### 11.3 Tests E2E (optionnel)
- Cypress ou Playwright
- Tests des scénarios critiques

---

## 12. Internationalization (i18n)

### 12.1 Configuration
- Support français (par défaut) et anglais
- Fichiers de traduction par module
- Format JSON ou XLIFF

### 12.2 Implémentation
- Utiliser `@angular/localize`
- Pipes de traduction
- Sélecteur de langue dans le header

---

## 13. Sécurité

### 13.1 Bonnes Pratiques
- Stockage sécurisé du token (localStorage avec expiration)
- HTTPS en production
- Validation côté client ET serveur
- Protection XSS (Angular par défaut)
- Protection CSRF (gérée par Laravel)

### 13.2 Authentification
- Refresh token automatique
- Logout automatique en cas d'erreur 401
- Expiration de session

---

## 14. Plan d'Implémentation par Phases

### Phase 1: Fondations (Semaine 1-2)
- ✅ Configuration du projet Angular
- ✅ Setup de l'environnement
- ✅ Services core (API, Auth, Interceptors)
- ✅ Guards et routing de base
- ✅ Layout principal (Header, Sidebar, Footer)
- ✅ Module d'authentification complet

### Phase 2: Module Académique (Semaine 3-4)
- ✅ Gestion des années académiques
- ✅ Gestion des matières
- ✅ Gestion des classes
- ✅ Dashboard avec statistiques de base

### Phase 3: Module Étudiants (Semaine 5-6)
- ✅ CRUD étudiants
- ✅ Upload de photos
- ✅ Import/Export
- ✅ Recherche et filtres avancés

### Phase 4: Module Notes (Semaine 7-8)
- ✅ Gestion des évaluations
- ✅ Saisie de notes (individuelle et en masse)
- ✅ Rapports et bulletins
- ✅ Génération PDF

### Phase 5: Module Présence (Semaine 9)
- ✅ Saisie de présence
- ✅ Vue calendrier
- ✅ Statistiques de présence

### Phase 6: Finalisation (Semaine 10)
- ✅ Module profil
- ✅ Tests et corrections
- ✅ Optimisations de performance
- ✅ Documentation
- ✅ Déploiement

---

## 15. Déploiement

### 15.1 Build de Production
```bash
ng build --configuration production
```

### 15.2 Options de Déploiement
- **Vercel**: Déploiement facile pour Angular
- **Netlify**: Hébergement statique
- **Apache/Nginx**: Serveur web traditionnel
- **Docker**: Containerisation (optionnel)

### 15.3 Configuration
- Variables d'environnement pour API URL
- Configuration CORS côté Laravel
- HTTPS obligatoire en production

---

## 16. Documentation

### 16.1 Documentation à Créer
- README.md avec instructions de setup
- Documentation des composants (CompoDoc ou Storybook)
- Guide de contribution
- API integration guide

---

## 17. Outils de Développement

### 17.1 Outils Recommandés
- **Angular DevTools**: Extension Chrome
- **Redux DevTools**: Si utilisation de NgRx
- **Prettier**: Formatage de code
- **ESLint**: Linting
- **Husky**: Git hooks
- **Conventional Commits**: Standardisation des commits

### 17.2 Configuration
- `.editorconfig` pour cohérence d'édition
- `.prettierrc` pour formatage
- `.eslintrc.json` pour linting
- `commitlint.config.js` pour validation des commits

---

## 18. Checklist de Démarrage

- [ ] Cloner ou créer le repository Angular
- [ ] Installer Angular CLI globalement
- [ ] Créer le projet avec `ng new`
- [ ] Installer les dépendances (Material, NgRx, etc.)
- [ ] Configurer les environnements
- [ ] Créer la structure de dossiers
- [ ] Setup des services core
- [ ] Configuration du routing
- [ ] Setup des interceptors
- [ ] Créer le layout principal
- [ ] Implémenter l'authentification
- [ ] Tester la connexion avec l'API Laravel

---

## 19. Ressources et Références

### 19.1 Documentation Officielle
- [Angular Documentation](https://angular.io/docs)
- [Angular Material](https://material.angular.io/)
- [NgRx Documentation](https://ngrx.io/)

### 19.2 Bonnes Pratiques
- Angular Style Guide
- RxJS Best Practices
- TypeScript Best Practices

---

## 20. Notes Importantes

- **CORS**: S'assurer que Laravel autorise les requêtes depuis le domaine Angular
- **Token**: Implémenter un mécanisme de refresh token si disponible
- **Pagination**: Toutes les listes doivent utiliser la pagination côté serveur
- **Validation**: Toujours valider côté client ET serveur
- **Accessibilité**: Respecter les standards WCAG pour l'accessibilité
- **Responsive**: L'application doit être responsive (mobile-first)

---

## Conclusion

Ce plan fournit une feuille de route complète pour développer l'application Angular. Il est recommandé de suivre les phases dans l'ordre pour assurer une progression logique et éviter les dépendances circulaires.

**Durée estimée totale**: 10 semaines (avec un développeur full-time)
**Complexité**: Moyenne à Élevée

N'hésitez pas à adapter ce plan selon vos besoins spécifiques et contraintes de temps.

