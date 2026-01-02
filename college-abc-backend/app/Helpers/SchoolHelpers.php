<?php

if (!function_exists('school_db')) {
    /**
     * Helper pour obtenir le nom de la base de données selon le niveau scolaire.
     * Utilise le mapping défini dans SchoolDatabaseProvider.
     *
     * @param string $level Niveau scolaire (ex: '6ème', '2nde', 'CP')
     * @return string Nom de la connexion (ex: 'school_college')
     */
    function school_db(string $level)
    {
        return \App\Providers\SchoolDatabaseProvider::DB_MAP[$level] ?? 'school_core';
    }
}
