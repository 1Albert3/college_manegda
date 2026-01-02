# Identifiants de Connexion - Guide de Récupération

Il semble que plusieurs configurations de "seeding" (initialisation) existent dans le projet, chacune avec ses propres identifiants. Voici les 4 combinaisons possibles. Essayez-les dans l'ordre.

## 1. Configuration "Demo" (Actuelle - Modifiée par vous)

_Basée sur vos modifications récentes dans `DemoSeeder.php`._

| Rôle            | Email                   | Mot de passe   |
| --------------- | ----------------------- | -------------- |
| **Super Admin** | `admin@manegda.bf`      | `Password123!` |
| **Directeur**   | `directeur@manegda.bf`  | `Password123!` |
| **Secrétaire**  | `secretaire@manegda.bf` | `Password123!` |
| **Enseignant**  | `enseignant@manegda.bf` | `Password123!` |
| **Parent 1**    | `parent1@manegda.bf`    | `Password123!` |
| **Parent 2**    | `parent2@manegda.bf`    | `Password123!` |

## 2. Configuration par Défaut "Laravel"

_Utilisé par le `DatabaseSeeder.php` standard._

| Rôle            | Email                   | Mot de passe  |
| --------------- | ----------------------- | ------------- |
| **Super Admin** | `admin@college-abc.com` | `password123` |
| **Parent**      | `parent@test.com`       | `password`    |

## 3. Configuration "Test Users"

_Utilisé par `TestUsersSeeder.php`._

| Rôle           | Email                   | Mot de passe  |
| -------------- | ----------------------- | ------------- |
| **Admin**      | `admin@college.bf`      | `password123` |
| **Secrétaire** | `secretaire@college.bf` | `password123` |
| **Comptable**  | `comptable@college.bf`  | `password123` |

## 4. Configuration "Complete School" (ACTIVE ✅)

_Utilisée par `CompleteSchoolSeeder.php`. C'est la configuration actuelle du système suite à la réinitialisation multi-base de données._

| Rôle             | Email                            | Mot de passe        |
| :--------------- | :------------------------------- | :------------------ |
| **Direction**    | `direction@wend-manegda.bf`      | `Direction@2024`    |
| **Secrétariat**  | `secretariat@wend-manegda.bf`    | `Secretariat@2024`  |
| **Comptabilité** | `comptabilite@wend-manegda.bf`   | `Comptabilite@2024` |
| **Enseignant**   | `prof.kabore@wend-manegda.bf`    | `Enseignant@2024`   |
| **Parent**       | `parent.sawadogo@gmail.com`      | `Parent@2024`       |
| **Tuteur (MP)**  | `ouedraogo.a@wend-manegda.bf`    | `password123`       |
| **Tuteur (COL)** | `diallo.mamadou@college-abc.com` | `password123`       |

---

## 🆘 Commande de Réinitialisation Correcte

La commande `migrate:fresh` ne supporte pas l'option `--class`. Vous devez utiliser `--seeder` ou séparer les commandes.

Pour appliquer votre configuration **Demo** modifiée :

```bash
php artisan migrate:fresh && php artisan db:seed --class=DemoSeeder
```

Ou, si votre version de Laravel le permet :

```bash
php artisan migrate:fresh --seed --seeder=DemoSeeder
```
