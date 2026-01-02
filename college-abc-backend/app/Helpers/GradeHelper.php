<?php

namespace App\Helpers;

class GradeHelper
{
    /**
     * Retourne la mention correspondant à une moyenne
     */
    public static function getMention(float $moyenne): string
    {
        if ($moyenne >= 16) return 'Très bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Insuffisant';
    }

    /**
     * Retourne la couleur associée à une moyenne (pour le web)
     */
    public static function getGradeColor(float $moyenne): string
    {
        if ($moyenne >= 14) return '#10B981'; // Green
        if ($moyenne >= 10) return '#F59E0B'; // Amber
        return '#EF4444'; // Red
    }
}
