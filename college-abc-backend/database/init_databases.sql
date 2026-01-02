-- ============================================================================
-- SCRIPT D'INITIALISATION DES BASES DE DONNÉES
-- Système de Gestion Scolaire - Burkina Faso
-- ============================================================================
-- Ce script crée les 4 bases de données nécessaires au système
-- Exécuter dans phpMyAdmin ou MySQL Workbench
-- ============================================================================

-- Définir le jeu de caractères par défaut
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ============================================================================
-- BASE 1: school_core (Base Centrale)
-- ============================================================================
-- Contient: users, roles, permissions, audit_logs, configurations, school_years

DROP DATABASE IF EXISTS school_core;
CREATE DATABASE school_core
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON school_core.* TO 'root'@'localhost';

-- ============================================================================
-- BASE 2: school_maternelle_primaire
-- ============================================================================
-- Contient: students_mp, guardians_mp, classes_mp, teachers_mp, grades_mp, etc.

DROP DATABASE IF EXISTS school_maternelle_primaire;
CREATE DATABASE school_maternelle_primaire
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON school_maternelle_primaire.* TO 'root'@'localhost';

-- ============================================================================
-- BASE 3: school_college
-- ============================================================================
-- Contient: students_college, classes_college, teachers_college, subjects_college, etc.

DROP DATABASE IF EXISTS school_college;
CREATE DATABASE school_college
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON school_college.* TO 'root'@'localhost';

-- ============================================================================
-- BASE 4: school_lycee
-- ============================================================================
-- Contient: students_lycee, classes_lycee (avec séries A,C,D), orientation_lycee, etc.

DROP DATABASE IF EXISTS school_lycee;
CREATE DATABASE school_lycee
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON school_lycee.* TO 'root'@'localhost';

-- ============================================================================
-- Actualiser les privilèges
-- ============================================================================
FLUSH PRIVILEGES;

-- ============================================================================
-- Vérification
-- ============================================================================
SELECT 'Bases de données créées avec succès!' AS message;

SHOW DATABASES LIKE 'school%';

-- ============================================================================
-- FIN DU SCRIPT
-- ============================================================================
-- Ensuite, exécutez les migrations Laravel:
-- php artisan migrate --database=school_core --path=database/migrations/core
-- php artisan migrate --database=school_mp --path=database/migrations/mp
-- php artisan migrate --database=school_college --path=database/migrations/college
-- php artisan migrate --database=school_lycee --path=database/migrations/lycee
-- ============================================================================
