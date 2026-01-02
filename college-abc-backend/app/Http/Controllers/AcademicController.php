<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolYear;
use App\Models\MP\ClassMP;
use App\Models\College\ClassCollege;
use App\Models\Lycee\ClassLycee;

/**
 * Controller to aggregate academic data across all cycles.
 */
class AcademicController extends Controller
{
    /**
     * Get all active classrooms across MP, College, and Lycee.
     */
    public function getAllClassrooms()
    {
        $year = SchoolYear::where('is_current', true)->first();
        if (!$year) {
            $year = SchoolYear::latest()->first();
        }
        $yearId = $year ? $year->id : null;

        if (!$yearId) {
            return response()->json([], 200);
        }

        // MP Classes
        $mpClasses = ClassMP::where('school_year_id', $yearId)
            ->where('is_active', true)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->nom,
                    'level' => $c->niveau,
                    'cycle' => 'mp',
                    'capacity' => $c->seuil_maximum ?? 50,
                    'currentCount' => $c->effectif_actuel ?? 0,
                    'mainTeacher' => $c->teacher ? ($c->teacher->first_name . ' ' . $c->teacher->last_name) : 'Non assigné'
                ];
            });

        // College Classes
        $collegeClasses = ClassCollege::where('school_year_id', $yearId)
            ->where('is_active', true)
            ->with('profPrincipal.user')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->nom,
                    'level' => $c->niveau,
                    'cycle' => 'college',
                    'capacity' => $c->seuil_maximum ?? 50,
                    'currentCount' => $c->effectif_actuel ?? 0,
                    'mainTeacher' => $c->profPrincipal && $c->profPrincipal->user ? ($c->profPrincipal->user->first_name . ' ' . $c->profPrincipal->user->last_name) : 'Non assigné'
                ];
            });

        // Lycee Classes
        $lyceeClasses = ClassLycee::where('school_year_id', $yearId)
            ->where('is_active', true)
            ->with('profPrincipal.user')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->nom,
                    'level' => $c->niveau,
                    'cycle' => 'lycee',
                    'capacity' => $c->seuil_maximum ?? 50,
                    'currentCount' => $c->effectif_actuel ?? 0,
                    'mainTeacher' => $c->profPrincipal && $c->profPrincipal->user ? ($c->profPrincipal->user->first_name . ' ' . $c->profPrincipal->user->last_name) : 'Non assigné'
                ];
            });

        $allClasses = $mpClasses->merge($collegeClasses)->merge($lyceeClasses);

        return response()->json($allClasses);
    }
}
