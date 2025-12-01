<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        return response()->json([
            [
                'id' => 1,
                'title' => 'Célébration de l\'Excellence 2024',
                'date' => '15 Mai 2024',
                'category' => 'Vie Scolaire',
                'imageUrl' => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=2070&auto=format&fit=crop',
                'excerpt' => 'Retour en images sur la cérémonie de remise des prix aux meilleurs élèves de l\'année.'
            ],
            [
                'id' => 2,
                'title' => 'Sortie Pédagogique au Musée',
                'date' => '10 Avril 2024',
                'category' => 'Sorties',
                'imageUrl' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=2132&auto=format&fit=crop',
                'excerpt' => 'Les élèves de 3ème ont découvert l\'histoire culturelle du Burkina Faso.'
            ],
            [
                'id' => 3,
                'title' => 'Compétition Sportive Inter-Etablissements',
                'date' => '22 Mars 2024',
                'category' => 'Sport',
                'imageUrl' => 'https://images.unsplash.com/photo-1577896335477-2858506f9796?q=80&w=2070&auto=format&fit=crop',
                'excerpt' => 'Nos équipes de football et de basketball ont brillé lors du tournoi régional.'
            ]
        ]);
    }

    public function officialDocs()
    {
        return response()->json([
            [
                'id' => 1,
                'title' => 'Calendrier Scolaire 2024-2025',
                'date' => '01 Sept 2024',
                'type' => 'PDF',
                'size' => '1.2 MB',
                'downloadUrl' => '#'
            ],
            [
                'id' => 2,
                'title' => 'Règlement Intérieur',
                'date' => '01 Sept 2024',
                'type' => 'PDF',
                'size' => '850 KB',
                'downloadUrl' => '#'
            ],
            [
                'id' => 3,
                'title' => 'Liste des Fournitures - 6ème',
                'date' => '15 Juil 2024',
                'type' => 'PDF',
                'size' => '450 KB',
                'downloadUrl' => '#'
            ]
        ]);
    }
}
