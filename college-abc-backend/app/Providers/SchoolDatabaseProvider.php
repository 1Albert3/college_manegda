<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * SchoolDatabaseProvider (Nouveau Core v2)
 * 
 * Ce provider est le gardien de la cohérence des données multi-bases.
 * Il applique strictement les règles de gestion burkinabè :
 * 1. Séparation stricte des données (MP, Collège, Lycée)
 * 2. Validation centralisée des seuils de classe (15-40 élèves)
 * 3. Calculs de notes conformes (Arrondis 2 décimales, Coeffs entiers)
 */
class SchoolDatabaseProvider extends ServiceProvider
{
    /**
     * Mapping Niveau -> Connexion DB
     * Source de vérité pour le routing des données
     */
    const DB_MAP = [
        // Maternelle / Primaire
        'PS' => 'school_mp',
        'MS' => 'school_mp',
        'GS' => 'school_mp',
        'CP1' => 'school_mp',
        'CP2' => 'school_mp',
        'CP' => 'school_mp',
        'CE1' => 'school_mp',
        'CE2' => 'school_mp',
        'CM1' => 'school_mp',
        'CM2' => 'school_mp',

        // Collège
        '6ème' => 'school_college',
        '5ème' => 'school_college',
        '4ème' => 'school_college',
        '3ème' => 'school_college',
        '6eme' => 'school_college',
        '5eme' => 'school_college',
        '4eme' => 'school_college',
        '3eme' => 'school_college',

        // Lycée
        '2nde' => 'school_lycee',
        '1ère' => 'school_lycee',
        '1ere' => 'school_lycee',
        'Tle' => 'school_lycee'
    ];

    public function register()
    {
        // Service de Calcul des Moyennes "Burkina Standard"
        // Injecte ce service via app('burkina.grading')
        $this->app->singleton('burkina.grading', function () {
            return new class {
                public function calculateAverage(array $notes, array $coeffs): float
                {
                    $totalPoints = 0;
                    $totalCoeffs = 0;

                    foreach ($notes as $index => $note) {
                        $coeff = $coeffs[$index] ?? 1;

                        // Règle: Coefficients entiers uniquement
                        if (!is_int($coeff) && !ctype_digit((string)$coeff)) {
                            throw new \InvalidArgumentException("Le coefficient doit être un entier strict (Reçu: $coeff).");
                        }
                        $coeff = (int)$coeff;

                        $totalPoints += ($note * $coeff);
                        $totalCoeffs += $coeff;
                    }

                    if ($totalCoeffs === 0) return 0.0;

                    // Règle: Arrondi strict à 2 décimales
                    return round($totalPoints / $totalCoeffs, 2);
                }
            };
        });
    }

    public function boot()
    {
        // 1. Validateur Personnalisé : SEUIL DE CLASSE (15-40)
        // Utilisation dans controller: 'effectif' => 'class_threshold'
        Validator::extend('class_threshold', function ($attribute, $value, $parameters, $validator) {
            return $value >= 15 && $value <= 40;
        }, 'L\'effectif de la classe doit être impérativement compris entre 15 et 40 élèves (Norme Ministère).');

        // 2. Validateur Personnalisé : COEFFICIENT ENTIER
        // Utilisation: 'coefficient' => 'integer_coefficient'
        Validator::extend('integer_coefficient', function ($attribute, $value) {
            return is_int($value) || (is_numeric($value) && floor($value) == $value);
        }, 'Les coefficients doivent être des nombres entiers.');

        // 3. Macro DB pour obtenir la connexion selon le niveau
        // Usage: DB::forLevel('6ème')->table('...')
        DB::macro('forLevel', function (string $level) {
            $connection = SchoolDatabaseProvider::DB_MAP[$level] ?? 'school_core';
            return DB::connection($connection);
        });

        // Helper global pour obtenir le nom de la connexion
        if (!function_exists('school_db')) {
            require_once __DIR__ . '/../Helpers/SchoolHelpers.php';
        }
    }
}
