Tu es un développeur expert chargé de créer un système de gestion scolaire complet pour une école catholique moderne au Burkina Faso, couvrant Maternelle, Primaire, Collège et Lycée.
Le système doit être robuste, sans erreurs de calcul, scalable et respecter strictement les spécifications techniques et fonctionnelles définies ci-dessous.

🏗️ STACK TECHNIQUE IMPOSÉE
Backend

Framework : Laravel (dernière version LTS)
Base de données : MySQL 8.0+
Environnement dev : XAMPP (local)
Architecture : API RESTful

Frontend

Angular (dernière version stable)
Responsive (Mobile-first)


Structure Base de Données
4 bases de données MySQL distinctes :

school_core (Base centrale partagée)

users - Tous les utilisateurs du système
roles - Rôles (direction, secretariat, comptabilite, enseignant, parent, eleve)
permissions - Permissions granulaires
audit_logs - Traçabilité complète (qui, quoi, quand)
school_years - Années scolaires
configurations - Paramètres système
notifications - Notifications système


school_maternelle_primaire

students_mp - Élèves maternelle/primaire
classes_mp - Classes (PS, MS, GS, CP, CE1, CE2, CM1, CM2)
teachers_mp - Enseignants (1 titulaire par classe)
grades_mp - Notes (évaluation compétences maternelle, notes primaire)
attendance_mp - Absences et retards
report_cards_mp - Bulletins
student_history_mp - Historique parcours


school_college

students_college - Élèves collège
classes_college - Classes (6ème, 5ème, 4ème, 3ème)
teachers_college - Enseignants
subjects_college - Matières avec coefficients (entiers uniquement)
teacher_subject_assignments - Attribution prof-matière-classe
grades_college - Notes sur /20
attendance_college - Absences et retards
report_cards_college - Bulletins
discipline_college - Sanctions et encouragements
student_history_college - Historique parcours


school_lycee

students_lycee - Élèves lycée
classes_lycee - Classes (2nde, 1ère, Terminale) avec séries (A, C, D...)
teachers_lycee - Enseignants
subjects_lycee - Matières avec coefficients
teacher_subject_assignments - Attribution prof-matière-classe
grades_lycee - Notes sur /20
attendance_lycee - Absences et retards
report_cards_lycee - Bulletins
orientation_lycee - Orientation post-bac
student_history_lycee - Historique parcours

## CONFIGURATION LARAVEL
// config/database.php - Connexions multiples
'connections' => [
    'mysql' => [...], // school_core
    'mysql_mp' => [...], // school_maternelle_primaire
    'mysql_college' => [...], // school_college
    'mysql_lycee' => [...], // school_lycee
]
```

---

## 👥 ACTEURS DU SYSTÈME & LEURS ACCÈS

### 1. 🎩 DIRECTION / ADMINISTRATION

**Connexion** : Email(directeur@test.com) + Mot de passe(directeur)

**Pages et fonctionnalités** :

#### Dashboard Stratégique
- Indicateurs clés en temps réel :
  - Effectif total (maternelle → lycée) avec évolution
  - Taux de réussite global par niveau
  - Budget mensuel vs réalisé
  - Alertes importantes (incidents, absences critiques, impayés majeurs)
- Statistiques tous niveaux avec graphiques interactifs
- Rapports comparatifs multi-années
- Calendrier événements et réunions

#### Gestion du Personnel
- **CRUD complet** : Enseignants et staff administratif
- Consultation dossiers (diplômes, contrats, évaluations)
- Visualisation emplois du temps de tous les enseignants
- Gestion absences et remplacements
- Attribution classes et matières (validation finale)
- Évaluation performance (optionnel)

#### Gestion Académique
- **Validation bulletins** : Approbation avant distribution
- **Décisions de passage** : Validation conseil de classe (passage/redoublement)
- Suivi résultats par classe, niveau, matière (tableaux et graphiques)
- Organisation conseils de classe et examens
- Gestion calendrier scolaire (trimestres, vacances, compositions)
- Validation projets pédagogiques

#### Vue Financière Globale
- Budget annuel vs réalisé (graphiques)
- Taux de recouvrement par niveau
- Impayés > 50,000 FCFA (alertes critiques)
- Approbation dépenses > seuil défini
- Rapports financiers pour conseil d'administration (export PDF/Excel)

#### Communication
- **Messagerie complète** : Tous acteurs (enseignants, parents, personnel)
- Diffusion circulaires générales
- Gestion événements (messes, réunions....)
- Coordination activités pastorales (calendrier catéchisme, célébrations)

#### Administration Système
- Paramétrage global (tarifs, mentions, seuils classes)
- **Gestion droits d'accès** : Attribution rôles et permissions
- Consultation audit trail complet
- Configuration notifications automatiques
- Archivage et conformité RGPD

**Permissions** :
- ✅ Lecture/Écriture/Modification/Suppression : TOUT
- ✅ Validation finale décisions importantes
- ✅ Accès données sensibles (salaires, évaluations personnels)

---

### 2. 📋 SECRÉTARIAT

**Connexion** : Email(secretariat@test.com)+ Mot de passe(secretariat)

**Pages et fonctionnalités** :

#### Dashboard Opérationnel
- Inscriptions en attente (nombre + liste)
- Documents à traiter (certificats, attestations)
- Tâches du jour (priorités)
- Statistiques effectifs par classe

#### Gestion des Inscriptions
- **Formulaire inscription complet** :
```
  ÉLÈVE : Nom, Prénom(s), Date naissance, Lieu, Sexe, Nationalité
  DOCUMENTS : Photo (upload), Extrait naissance (upload), Certificat médical(optionnel)
  MÉDICAL(Si certificat médical) : Groupe sanguin, Allergies, Vaccinations
  AFFECTATION : Année scolaire, Niveau, Classe, Régime (interne/externe)
  STATUT : Nouveau/Ancien/Transfert (établissement origine si transfert)
```
```
  PÈRE : Nom, Profession, Tél 1 (obligatoire), Tél 2, Email(optionnel), Adresse
  MÈRE : [Mêmes champs]
  TUTEUR : [Mêmes champs] + Lien de parenté (si différent)
```
```
  FINANCIER : Frais scolarité (selon niveau), Cantine,
  MODE PAIEMENT : Comptant/3 Tranches/Mensuel
```

- **Workflow automatisé** :
  1. Secrétariat saisit → Statut "En attente"
  2. **Vérification automatique places disponibles** (seuil classe)
  3. Génération demande facturation → Comptabilité
  4. Direction valide → Statut "Validée"
  5. **Création automatique comptes** : 1 Parent + 1 Élève
  6. **Envoi notification** : SMS + Email aux parents

#### Gestion des Dossiers Élèves
- **CRUD complet** élèves (tous niveaux)
- Recherche avancée : Matricule, Nom, Classe, Niveau
- Modification informations (parents, médical, régime)
- **Affectation/Changement classe** avec vérification seuils
- Gestion transferts et radiations
- Suivi documents obligatoires (vaccinations, assurances)

#### Gestion des Classes
- **CRUD classes** :
  - Créer : Nom (ex: 5ème A), Niveau, Seuil min/max, Année, Salle
  - Modifier : Seuil, Salle, Prof principal
  - Supprimer : Uniquement si 0 élève
  - Archiver : Fin d'année (historique)
  - Dupliquer : Pour année suivante
- **Alertes automatiques** :
  - ⚠️ Classe à 90% seuil max
  - 🚫 Blocage inscription si seuil max atteint
  - 💡 Suggestion classe alternative
- Visualisation capacité actuelle (ex: 32/40)

#### Emplois du Temps
- **Création emplois du temps** :
  - Interface drag & drop (jour/heure → matière/prof)
  - **Contraintes automatiques** :
    - ❌ Pas de chevauchement enseignant
    - ❌ Pas de chevauchement salle
    - ✅ Respect pauses (récréation, déjeuner)
    - ✅ Limite horaire (primaire : fin 17h)

- Modification manuelle (swap créneaux)
- Validation Direction avant publication
- **Export multi-format** : PDF par classe, par enseignant, par salle
- Gestion salles et ressources

#### Facturation (Interface comptabilité)
- **Génération factures** (scolarité + extras)
- Suivi paiements (consultation)
- Émission reçus après validation comptabilité

#### Documents Officiels
- **Génération automatique** :
  - Certificats scolarité (template personnalisable)
  - Attestations diverses
  - Listes élèves par classe
  - Dossiers examens (CEP, BEPC, BAC)
- Constitution dossiers Parcoursup (Terminale)
- **Export massif** fin d'année

#### Communication Administrative
- Messagerie avec parents (aspects administratifs)
- Envoi circulaires et informations
- Gestion rendez-vous parents (calendrier)

#### Statistiques
- Effectifs : Par classe, niveau, option
- Taux remplissage classes
- Rapports pour autorités académiques (MENA)
- Statistiques orientation (3ème, Terminale)

**Permissions** :
- ✅ Lecture/Écriture : Dossiers élèves, Classes, Inscriptions
- ✅ Création factures (validation comptabilité)
- ❌ Pas d'accès : Données RH personnels, Validation bulletins

---

### 3. 💰 COMPTABILITÉ

**Connexion** : Email(comptabilité@test.com) + Mot de passe(comptabilité)

**Pages et fonctionnalités** :

#### Dashboard Financier
- Paiements du jour (montant + nombre transactions)
- Impayés total avec liste familles
- Taux de recouvrement (objectif vs réalisé)
- Relances à effectuer (nombre + échéances)
- Graphiques : Budget mensuel, Évolution paiements

#### Gestion des Paiements
- **Enregistrement paiements** :
  - Modes : Espèces, Mobile Money (Orange/Moov), Virement bancaire
  - Saisie manuelle ou import relevé bancaire
  - **Génération automatique reçu** (numéro unique)
  - Mise à jour solde élève en temps réel
- Validation paiements tranches (Octobre, Janvier, Avril)
- Historique complet par élève
- Rapprochement bancaire

#### Facturation
- **Validation factures** émises par secrétariat
- Structure tarifaire **CRUD** :
```
  NIVEAUX (tarif aleatoirs supceptibles d'être modifiés) :
  - Maternelle : 150,000 FCFA
  - Primaire : 200,000 FCFA
  - Collège : 250,000 FCFA
  - Lycée : 300,000 FCFA
  
  EXTRAS (succeptibles d'être modifiés):
  - Inscription : 10,000 FCFA
  - Réinscription : 5,000 FCFA
  - Cantine/mois : 15,000 FCFA
  - Transport/mois : 20,000 FCFA
  - Tenue scolaire : 25,000 FCFA
```

- Édition attestations fiscales

#### Gestion Impayés & Relances
- **Liste impayés** : Tri par montant, ancienneté, classe
- **Relances automatiques** :
  - J+7 échéance : Email rappel
  - J+15 : SMS + Email
  - J+30 : Convocation parent
  - > 50,000 FCFA : Alerte Direction
- **Actions** :
  - 🚫 Blocage réinscription si impayé > 50,000 FCFA
  - ⚠️ Rétention bulletin si solde négatif (paramétrable)
- Suivi promesses de paiement
- Échéanciers personnalisés

#### Budget & Trésorerie
- Budget prévisionnel vs réalisé
- Suivi dépenses (validation > seuil)
- Trésorerie mensuelle (entrées/sorties)
- Projections financières


#### Rapports Financiers
- **Rapports prédéfinis** :
  - Journal paiements (quotidien, mensuel, annuel)
  - État recouvrement par niveau/classe
  - Prévisionnel encaissements
  - Bilan financier trimestriel
- Export : Excel, PDF, CSV
- Rapports conseil d'administration

**Permissions** :
- ✅ Lecture/Écriture : Tout module financier
- ✅ Validation : Paiements, Factures, Relances
- ✅ Lecture : Dossiers élèves (infos contact parents)
- ❌ Pas d'accès : Notes, Emplois du temps, Discipline

---

### 4. 👨‍🏫 ENSEIGNANTS

**Connexion** : Email(enseignant1@test.com.....enseignantn@test.com) + Mot de passe(enseignant)

**Pages et fonctionnalités** :

#### Dashboard Personnel
- Cours du jour (emploi du temps)
- Notes à saisir (nombre évaluations)
- Messages parents non lus
- Devoirs à corriger (si en ligne)
- Alerte absences élèves

#### Mes Classes
- **Vue par classe** :
  - Liste élèves (photo, nom, matricule)
  - Effectif actuel
  - Professeur principal (si c'est lui)
  - Statistiques classe (moyenne, taux réussite)
- **Maternelle/Primaire** : 1 classe titulaire (toutes matières)
- **Collège/Lycée** : Plusieurs classes pour sa/ses matière(s)

#### Saisie des Notes
- **Interface intuitive** :
  - Sélection : Classe → Matière → Type évaluation → Trimestre
  - Saisie rapide : Tableau avec validation temps réel
  - Types : IE (Interro écrite), DS (Devoir surveillé), Comp (Composition), TP, CC
- **Validations automatiques** :
  - ✅ Note ≤ maximum (/10, /20,)
  - ✅ Coefficient entier uniquement (1-6)
  - ❌ Erreur si note > max avec message clair
- **Statuts** :
  - Brouillon : Modifiable à volonté
  - Publiée : Visible parents/élèves, **verrouillée**
  - Modification note publiée : Demande Direction avec justificatif
- **Calculs automatiques** :
  - Moyenne élève par matière (arrondi 2 décimales)
  - Statistiques classe (moyenne, min, max, médiane)
- Historique modifications (traçabilité)

#### Appel & Absences
- **Saisie pendant cours** :
  - Liste élèves avec cases à cocher (Présent/Absent)
  - Saisie retard (heure arrivée)
  - Motif absent si connu
- **Notification automatique** :
  - Absence → SMS parent immédiat
  - Censorat notifié en temps réel
- Consultation historique absences élève

#### Cahier de Texte Numérique
- **Saisie devoirs & leçons** :
  - Date, Matière, Classe
  - Contenu (éditeur riche)
  - Date rendu (si devoir)
  - Documents joints (PDF, images)
- Visible parents et élèves
- Modification/Suppression avant date rendu


#### Observations & Appréciations
- **Bulletins** :
  - Appréciation par matière (texte libre)
  - Appréciation générale (si prof principal)
  - Conseils personnalisés

#### Emploi du Temps
- Consultation emploi du temps personnel
- Disponibilités (créneaux indisponibles)
- Export PDF

#### Messagerie
- **Communication parents** :
  - Envoi messages individuels ou groupés
  - Réponse aux parents
  - Demandes rendez-vous
- Communication avec administration
- Notifications réponses

**Permissions** :
- ✅ Écriture : Notes (ses classes/matières), Absences (ses cours), Cahier texte, Cours
- ✅ Lecture : Dossiers élèves (ses classes), Emplois du temps
- ❌ Pas d'accès : Finances, Autres classes (sauf lecture si besoin pédagogique)

---

### 5. 👨‍👩‍👧 PARENTS

**Connexion** : **Matricule élève uniquement** (pas d'email)+ mot de passe par defaut(changer apres première connexion)

**Interface** :
- Sélection enfant (si plusieurs)
- Tableau de bord par enfant

**Pages et fonctionnalités** :

#### Dashboard Famille
- Dernière moyenne enfant (avec évolution)
- Absences du mois
- Prochaine échéance paiement
- Messages non lus (enseignants/admin)
- Devoirs à venir (3 prochains jours)

#### Notes & Bulletins
- **Consultation notes** :
  - Vue par trimestre et matière
  - Toutes notes détaillées (IE, DS, Comp...)
  - Moyenne par matière
  - Moyenne générale
  - Évolution graphique (courbes)
- **Bulletins** :
  - Téléchargement PDF (tous trimestres)
  - Rang dans classe
  - Appréciations enseignants
  - Décision conseil classe (passage/redoublement)
- **Maternelle** : Évaluation compétences (Acquis/En cours/Non acquis)

#### Absences & Retards
- **Liste complète** :
  - Date, Heure, Matière manquée
  - Statut : Justifiée / Non justifiée / En attente
- **Justification en ligne** :
  - Saisie motif
  - Upload justificatif (certificat médical, lettre)
  - Notification censorat automatique
- Statistiques : Nombre jours absents, Taux assiduité
- **Alertes** :
  - 3 absences non justifiées : Email rappel
  - 5 absences : Convocation (message + notification)

#### Devoirs & Cahier de Texte
- **Vue calendrier** : Devoirs à venir
- **Détail par matière** :
  - Énoncé devoir
  - Date à rendre
  - Documents joints (téléchargement)
- Leçons du jour

#### Emploi du Temps
- Consultation emploi du temps enfant
- Vue semaine/mois
- Export PDF/Image

#### Paiements & Factures
- **Liste factures** :
  - Montant, Date émission, Échéance, Statut (Payée/Impayée)
  - Détail : Scolarité + extras (cantine, transport...)
- **Historique paiements** :
  - Date, Montant, Mode, Numéro reçu
  - Téléchargement reçu (PDF)
- **Solde compte** : Montant restant dû
- **Paiement en ligne** (si activé) :
  - Mobile Money (Orange Money, Moov Money)
  - Carte bancaire
  - Confirmation instantanée

#### Communication
- **Messagerie** :
  - Envoi message à enseignant (par matière)
  - Envoi message à administration
  - Réception messages (enseignants, direction, comptabilité)
  - Historique conversations
- Demande rendez-vous (prof principal, direction)
- Notifications : SMS + Email + Push app mobile

#### Calendrier Scolaire
- Vacances, Compositions, Événements
- Activités pastorales (messes, retraites)
- Sorties et voyages scolaires

**Permissions** :
- ✅ Lecture : Tout concernant son/ses enfant(s) uniquement
- ✅ Écriture : Justification absences, Messages, Demandes RDV
- ✅ Paiement : Factures de son/ses enfant(s)
- ❌ Aucun accès : Autres élèves, Données financières école, Personnel

---

### 6. 🎓 ÉLÈVES

**Connexion** : **Matricule uniquement** (pas d'email)

**Pages et fonctionnalités** :

#### Dashboard Personnel
- Cours aujourd'hui (emploi du temps)
- Devoirs à rendre (urgent : dans 2 jours)
- Dernière moyenne
- Nouveaux cours en ligne (cette semaine)
- Messages enseignants

#### Mon Emploi du Temps
- **Vue hebdomadaire** : Grille complète
- **Vue quotidienne** : Détail jour (matière, prof, salle)
- Prochain cours (countdown)
- Export PDF/Image pour impression

#### Mes Notes
- **Consultation par trimestre** :
  - Notes détaillées par matière
  - Moyenne par matière
  - Moyenne générale
  - Graphiques évolution
- **Comparaison** :
  - Ma moyenne vs Moyenne classe (anonyme)
  - Classement (si activé)
- **Maternelle** : Compétences (pictogrammes : vert/orange/rouge)

#### Mes Bulletins
- Téléchargement PDF (tous trimestres)
- Appréciations enseignants
- Rang (si activé)

#### Devoirs à Faire
- **Liste devoirs** :
  - Date rendu, Matière, Énoncé
  - Documents joints (téléchargement)
  - **Marquage "Fait"** (checklist personnelle)
- **Cahier de texte** : Leçons par matière
- **Remise devoir en ligne** :
  - Upload fichier (PDF, Word, images)
  - Confirmation dépôt
  - Statut : Rendu/Non rendu/Corrigé


#### Mes Absences
- Consultation liste absences
- Statut justification
- Taux d'assiduité personnel


#### Forum
- **Espace discussion classe** :
  - Questions entre élèves (modération enseignant)
  - Partage ressources (si autorisé)
  - Projets de groupe
- **Règles strictes** :
  - Modération active
  - Respect charte
  - Signalement abus

#### Calendrier
- Compositions à venir
- Événements école
- Sorties et activités

**Permissions** :
- ✅ Lecture : Ses notes, emploi du temps, devoirs, cours en ligne
- ✅ Écriture : Remise devoirs, Messages enseignants, Forum (modéré)
- ❌ Aucun accès : Notes autres élèves, Finances, Données personnelles parents, Administration

---

## 📐 RÈGLES DE CALCUL CRITIQUES (ZÉRO ERREUR TOLÉRÉE)

### 1. Moyenne de Matière
```
Algorithme :
1. Collecter toutes les notes publiées de la matière pour le trimestre
2. Normaliser sur /20 : (note / max) × 20
3. Somme des notes normalisées / Nombre de notes
4. Arrondir à EXACTEMENT 2 décimales : round(moyenne, 2)

Exemple :
Notes : 15/20, 8/10, 90/100
Normalisées : 15, 16, 18
Moyenne : (15 + 16 + 18) / 3 = 49 / 3 = 16.33

## IMPLEMENTATION OBLIGATOIRE
public function calculateSubjectAverage(Collection $grades): float
{
    if ($grades->isEmpty()) return 0.00;
    
    $normalizedGrades = $grades->map(fn($g) => ($g->value / $g->max) * 20);
    $average = $normalizedGrades->avg();
    
    return round($average, 2); // STRICTEMENT 2 décimales
}
```

### 2. Moyenne Générale (avec coefficients)
```
Algorithme :
1. Pour chaque matière : Moyenne matière × Coefficient (ENTIER uniquement)
2. Total points = Somme(Moyenne × Coef)
3. Total coefficients = Somme(Coef)
4. Moyenne générale = Total points / Total coefficients
5. Arrondir à EXACTEMENT 2 décimales

Exemple :
Français : 13.67 × 4 = 54.68
Maths : 15.00 × 5 = 75.00
Histoire : 12.50 × 2 = 25.00
Total = 154.68 / (4+5+2) = 154.68 / 11 = 14.06
```

**Validations obligatoires** :
- ❌ Coefficients décimaux (2.5, 3.7...) → Erreur "Coefficient doit être entier"
- ❌ Note > max → Erreur "Note ne peut dépasser {max}"
- ❌ Moins de 3 notes par matière → Blocage bulletin avec message

### 3. Mentions 
```
 "Excellent"
 "Très Bien"
 "Bien"
 "Assez Bien"
 "Passable"
 "Insuffisant"
```

### 4. Décisions de Passage
```
Moyenne annuelle = (Moy Trimestre1 + Moy Trimestre2 + Moy Trimestre3) / 3

Si moyenne annuelle >= 10 : "Passage"
Si moyenne annuelle >= 9 et < 10 : "Passage conditionnel" (conseil classe)
Si moyenne annuelle < 9 : "Redoublement"
```

---

## 🛡️ SÉCURITÉ & CONFORMITÉ (NON NÉGOCIABLE)

### Authentification
```
Direction, Secrétariat, Comptabilité, Enseignants :
- Email + Mot de passe (8+ caractères, maj/min/chiffre/symbole)
- Comptabilité + Direction : 2FA obligatoire 

Parents :
- Matricule élève uniquement (ex: 2024-MP-001)
- Pas d'email requis

Élèves :
- Matricule uniquement
- Pas d'email requis
## Audit Trail (Traçabilité complète)
Table audit_logs :
CREATE TABLE audit_logs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  model_type VARCHAR(255), -- Ex: "Student", "Grade"
  model_id BIGINT,
  action VARCHAR(50), -- created, updated, deleted
  old_values JSON, -- Valeurs avant modification
  new_values JSON, -- Valeurs après modification
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP
);

Événements tracés :

✅ Toutes créations, modifications, suppressions
✅ Connexions/Déconnexions
✅ Validations (bulletins, paiements)
✅ Accès données sensibles

## Permissions :

❌ Enseignant ne peut saisir notes que pour SES classes/matières
❌ Parent ne peut voir que SES enfants
❌ Élève ne peut voir que SES données

## protection Données (RGPD adapté)

Cryptage : Données sensibles en base (AES-256)
Anonymisation : Données archivées (après 10 ans)
Consentement parents : Case à cocher inscription
Droit à l'oubli : Suppression sur demande (après période légale)
Sauvegardes : Quotidiennes automatiques (3 emplacements : local, cloud, externe)

## GÉNÉRATION BULLETINS Structure Bulletin PDF
![Base toi sur ce modele et modifie le texte](<WhatsApp Image 2025-12-27 at 22.48.46-1.jpeg>)
## Service Laravel Obligatoire

// app/Services/ReportCardService.php

public function generate($studentId, $trimester): array
{
    // 1. Vérifier minimum 3 notes par matière
    // 2. Calculer moyennes (GradeCalculationService)
    // 3. Vérifier aucune erreur calcul (tests unitaires)
    // 4. Générer PDF (DomPDF ou TCPDF)
    // 5. Enregistrer en BD (report_cards_*)
    // 6. Retourner ['pdf' => $pdf, 'data' => $data]
}
```

---

## 🚨 ALERTES AUTOMATIQUES (NOTIFICATIONS)

### SMS 

```
Absences :
- 1 absence non justifiée → SMS parent immédiat
- 3 absences → SMS rappel + convocation

Paiements :
- J+7 échéance : SMS rappel
- J+15 : SMS urgence
- Paiement reçu : SMS confirmation

Résultats :
- Publication bulletin → SMS notification
```

### Email
```
Inscriptions :
- Validation → Email bienvenue + identifiants

Paiements :
- Facture émise → Email avec PDF
- Paiement reçu → Email reçu

Académique :
- Bulletin publié → Email avec PDF
- Conseil classe → Email décision
```

### Notifications In-App
```
Tous acteurs :
- Messages reçus
- Tâches urgentes
- Validations requises

## CHECKLIST VALIDATION
Avant livraison, vérifier :
Fonctionnel

 Tous les rôles peuvent se connecter (y compris matricule pour parents/élèves)
 Calculs moyennes exacts sur 20 cas tests différents
 Bulletins PDF générés conformes programme burkinabé
 Notifications SMS/Email envoyées correctement
 Workflow inscription complet fonctionnel
 Emplois du temps sans conflits

Technique

 4 bases de données créées et connectées
 Migrations sans erreurs
 Seeders peuplent données tests
 Tests unitaires passent (80%+ coverage)
 API documentée 
 Frontend responsive (mobile/tablette/desktop)

Sécurité

 Authentification (direction, comptabilité)
 Permissions respectées (pas d'accès non autorisé)
 Audit trail enregistre toutes actions
 Données sensibles cryptées
 Protection contre injections SQL/XSS

Performance

 Temps réponse API < 500ms
 Pagination listes longues
 Pas de fuite mémoire (profiler)
 Build production optimisé

Documentation

 README installation complet
 Guides utilisateurs par rôle (PDF)
 Schéma base de données
 Documentation API
 Changelog versioning