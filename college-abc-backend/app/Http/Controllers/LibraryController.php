<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * LibraryController - Gestion de la bibliothèque
 */
class LibraryController extends Controller
{
    /**
     * Liste des livres avec recherche et filtres
     */
    public function index(Request $request)
    {
        $query = Book::query();

        if ($request->has('search')) {
            $query->where('title', 'like', "%{$request->search}%")
                ->orWhere('author', 'like', "%{$request->search}%")
                ->orWhere('isbn', 'like', "%{$request->search}%");
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $books = $query->orderBy('title')->paginate(20);

        return response()->json($books);
    }

    /**
     * Ajouter un livre
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|unique:books,isbn',
            'category' => 'required|string',
            'total_copies' => 'required|integer|min:1',
            'location' => 'nullable|string',
        ]);

        $validated['available_copies'] = $validated['total_copies'];
        $book = Book::create($validated);

        return response()->json($book, 201);
    }

    /**
     * Gérer un emprunt
     */
    public function loan(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'user_id' => 'required|exists:users,id',
            'days' => 'required|integer|min:1|max:30',
        ]);

        $book = Book::find($validated['book_id']);
        if ($book->available_copies <= 0) {
            return response()->json(['message' => 'Aucun exemplaire disponible'], 422);
        }

        DB::beginTransaction();
        try {
            $loan = BookLoan::create([
                'book_id' => $book->id,
                'user_id' => $validated['user_id'],
                'loan_date' => now(),
                'due_date' => now()->addDays($validated['days']),
                'status' => 'active',
            ]);

            $book->decrement('available_copies');
            DB::commit();

            return response()->json($loan, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur'], 500);
        }
    }

    /**
     * Retour d'un livre
     */
    public function returnBook($loanId)
    {
        $loan = BookLoan::findOrFail($loanId);
        if ($loan->status === 'returned') {
            return response()->json(['message' => 'Déjà retourné'], 422);
        }

        DB::beginTransaction();
        try {
            $loan->update([
                'return_date' => now(),
                'status' => 'returned'
            ]);

            $loan->book->increment('available_copies');
            DB::commit();

            return response()->json($loan);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur'], 500);
        }
    }
}
