<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\BookLoan;
use App\Models\User;

class LibraryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            [
                'title' => 'L\'Enfant Noir',
                'author' => 'Camara Laye',
                'isbn' => '978-2266',
                'category' => 'Littérature Africaine',
                'total_copies' => 15,
                'available_copies' => 12,
                'location' => 'Rayon A1',
            ],
            [
                'title' => 'Sous l\'orage',
                'author' => 'Seydou Badian',
                'isbn' => '978-2708',
                'category' => 'Littérature Africaine',
                'total_copies' => 10,
                'available_copies' => 10,
                'location' => 'Rayon A2',
            ],
            [
                'title' => 'Physique-Chimie 3ème',
                'author' => 'Collectif',
                'isbn' => '978-201-3',
                'category' => 'Scolaire',
                'total_copies' => 50,
                'available_copies' => 45,
                'location' => 'Rayon SC1',
            ],
            [
                'title' => 'Mathématiques 6ème (CIAM)',
                'author' => 'Collectif CIAM',
                'isbn' => '978-284-1',
                'category' => 'Scolaire',
                'total_copies' => 40,
                'available_copies' => 38,
                'location' => 'Rayon SC2',
            ],
            [
                'title' => 'Dictionnaire Petit Larousse 2024',
                'author' => 'Larousse',
                'isbn' => '978-203-5',
                'category' => 'Outils de langue',
                'total_copies' => 5,
                'available_copies' => 5,
                'location' => 'Rayon R1',
            ],
            [
                'title' => 'Excellence en Français 3ème',
                'author' => 'Hachette',
                'isbn' => '978-201-16',
                'category' => 'Scolaire',
                'total_copies' => 30,
                'available_copies' => 28,
                'location' => 'Rayon SC3',
            ],
            [
                'title' => 'Les Soleils des Indépendances',
                'author' => 'Ahmadou Kourouma',
                'isbn' => '978-202-00',
                'category' => 'Littérature Africaine',
                'total_copies' => 8,
                'available_copies' => 8,
                'location' => 'Rayon A3',
            ],
            [
                'title' => 'Le Petit Prince',
                'author' => 'Antoine de Saint-Exupéry',
                'isbn' => '978-207-04',
                'category' => 'Classiques',
                'total_copies' => 15,
                'available_copies' => 14,
                'location' => 'Rayon C1',
            ],
            [
                'title' => 'Histoire-Géographie Terminale',
                'author' => 'Nathan',
                'isbn' => '978-209-17',
                'category' => 'Scolaire',
                'total_copies' => 25,
                'available_copies' => 25,
                'location' => 'Rayon SC4',
            ],
            [
                'title' => 'Introduction à l\'Informatique',
                'author' => 'K. Diallo',
                'isbn' => '978-291-55',
                'category' => 'Technologie',
                'total_copies' => 12,
                'available_copies' => 10,
                'location' => 'Rayon T1',
            ],
        ];

        foreach ($books as $b) {
            Book::updateOrCreate(
                ['isbn' => $b['isbn'], 'title' => $b['title']],
                $b
            );
        }

        // Créer quelques emprunts si possible
        $student = User::where('role', 'eleve')->first();
        if ($student) {
            $book = Book::first();

            $existingLoan = BookLoan::where('book_id', $book->id)
                ->where('user_id', $student->id)
                ->where('status', 'active')
                ->exists();

            if (!$existingLoan) {
                BookLoan::create([
                    'book_id' => $book->id,
                    'user_id' => $student->id,
                    'loan_date' => now()->subDays(5),
                    'due_date' => now()->addDays(9),
                    'status' => 'active',
                ]);
            }
        }
    }
}
