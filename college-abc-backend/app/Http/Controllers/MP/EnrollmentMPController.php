<?php

namespace App\Http\Controllers\MP;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MP\ClassMP;
use App\Models\MP\EnrollmentMP;
use App\Models\MP\GuardianMP;
use App\Models\MP\StudentMP;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Contrôleur des inscriptions Maternelle/Primaire
 * 
 * Workflow:
 * 1. Secrétariat saisit la demande → Statut: "En attente"
 * 2. Système vérifie places disponibles
 * 3. Direction valide → Statut: "Validée"
 * 4. Comptabilité génère facture (automatique)
 * 5. Création accès Parents + Élève
 * 6. Notification SMS/Email
 */
class EnrollmentMPController extends Controller
{
    /**
     * Liste des inscriptions
     */
    public function index(Request $request)
    {
        $query = EnrollmentMP::with(['student', 'class', 'schoolYear', 'validator']);

        // Filtres
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        } else {
            $query->currentYear();
        }

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenoms', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $enrollments = $query->orderByDesc('created_at')->paginate($request->per_page ?? 15);

        return response()->json($enrollments);
    }

    /**
     * Inscriptions en attente
     */
    public function pending(Request $request)
    {
        $enrollments = EnrollmentMP::with(['student', 'class'])
            ->pending()
            ->currentYear()
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $enrollments,
            'count' => $enrollments->count(),
        ]);
    }

    /**
     * Créer une nouvelle inscription (Secrétariat)
     */
    public function store(Request $request)
    {
        // Validation complète selon cahier des charges
        $validated = $request->validate([
            // INFORMATIONS ÉLÈVE
            'nom' => 'required|string|max:100|min:2',
            'prenoms' => 'required|string|max:150|min:2',
            'date_naissance' => 'required|date|before:today',
            'lieu_naissance' => 'required|string|max:100',
            'sexe' => 'required|in:M,F',
            'nationalite' => 'nullable|string|max:50',
            'photo_identite' => 'nullable|image|max:2048',
            'extrait_naissance' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'statut_inscription' => 'required|in:nouveau,ancien,transfert',
            'etablissement_origine' => 'nullable|required_if:statut_inscription,transfert|string|max:200',
            'groupe_sanguin' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'allergies' => 'nullable|string',
            'vaccinations' => 'nullable|array',

            // AFFECTATION SCOLAIRE
            'school_year_id' => 'required|uuid',
            'class_id' => 'required|uuid|exists:school_mp.classes_mp,id',
            'regime' => 'required|in:interne,demi_pensionnaire,externe',

            // INFORMATIONS PÈRE
            'pere.nom_complet' => 'nullable|string|max:200',
            'pere.profession' => 'nullable|string|max:100',
            'pere.telephone_1' => 'nullable|string|max:20',
            'pere.telephone_2' => 'nullable|string|max:20',
            'pere.email' => 'nullable|email|max:191',
            'pere.adresse_physique' => 'nullable|string',
            'pere.est_contact_urgence' => 'nullable|boolean',

            // INFORMATIONS MÈRE
            'mere.nom_complet' => 'nullable|string|max:200',
            'mere.profession' => 'nullable|string|max:100',
            'mere.telephone_1' => 'nullable|string|max:20',
            'mere.telephone_2' => 'nullable|string|max:20',
            'mere.email' => 'nullable|email|max:191',
            'mere.adresse_physique' => 'nullable|string',
            'mere.est_contact_urgence' => 'nullable|boolean',

            // TUTEUR LÉGAL
            'tuteur.nom_complet' => 'nullable|string|max:200',
            'tuteur.profession' => 'nullable|string|max:100',
            'tuteur.telephone_1' => 'nullable|string|max:20',
            'tuteur.telephone_2' => 'nullable|string|max:20',
            'tuteur.email' => 'nullable|email|max:191',
            'tuteur.adresse_physique' => 'nullable|string',
            'tuteur.lien_parente' => 'nullable|string|max:50',
            'tuteur.est_contact_urgence' => 'nullable|boolean',

            // INFORMATIONS FINANCIÈRES
            'mode_paiement' => 'required|in:comptant,tranches_3',
            'frais_cantine' => 'nullable|numeric|min:0',
            'frais_activites' => 'nullable|numeric|min:0',
            'a_bourse' => 'nullable|boolean',
            'montant_bourse' => 'nullable|numeric|min:0',
            'pourcentage_bourse' => 'nullable|numeric|min:0|max:100',
            'type_bourse' => 'nullable|string|max:100',
        ], [
            'nom.required' => 'Le nom de l\'élève est obligatoire.',
            'nom.min' => 'Le nom doit contenir au moins 2 caractères.',
            'prenoms.required' => 'Le(s) prénom(s) sont obligatoires.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'lieu_naissance.required' => 'Le lieu de naissance est obligatoire.',
            'sexe.required' => 'Le sexe est obligatoire.',
            'class_id.required' => 'La classe est obligatoire.',
            'class_id.exists' => 'La classe sélectionnée n\'existe pas.',
            'etablissement_origine.required_if' => 'L\'établissement d\'origine est obligatoire pour un transfert.',
        ]);

        // Vérifier disponibilité de places
        $class = ClassMP::findOrFail($validated['class_id']);

        if ($class->isFull()) {
            throw ValidationException::withMessages([
                'class_id' => ['Cette classe a atteint sa capacité maximale (' . $class->seuil_maximum . ' élèves).'],
            ]);
        }

        // Calculer les frais selon le niveau
        $fraisScolarite = $this->getFraisScolarite($class->niveau);

        DB::beginTransaction();
        try {
            // Uploader les fichiers
            $photoPath = null;
            $extraitPath = null;

            if ($request->hasFile('photo_identite')) {
                $photoPath = $request->file('photo_identite')->store('students/photos', 'public');
            }

            if ($request->hasFile('extrait_naissance')) {
                $extraitPath = $request->file('extrait_naissance')->store('students/documents', 'public');
            }

            // Créer l'élève
            $student = StudentMP::create([
                'nom' => $validated['nom'],
                'prenoms' => $validated['prenoms'],
                'date_naissance' => $validated['date_naissance'],
                'lieu_naissance' => $validated['lieu_naissance'],
                'sexe' => $validated['sexe'],
                'nationalite' => $validated['nationalite'] ?? 'Burkinabè',
                'photo_identite' => $photoPath,
                'extrait_naissance' => $extraitPath,
                'statut_inscription' => $validated['statut_inscription'],
                'etablissement_origine' => $validated['etablissement_origine'],
                'groupe_sanguin' => $validated['groupe_sanguin'],
                'allergies' => $validated['allergies'],
                'vaccinations' => $validated['vaccinations'],
            ]);

            // Créer les tuteurs
            $this->createGuardians($student, $validated);

            // Créer l'inscription
            $enrollment = EnrollmentMP::create([
                'student_id' => $student->id,
                'class_id' => $validated['class_id'],
                'school_year_id' => $validated['school_year_id'],
                'regime' => $validated['regime'],
                'date_inscription' => now(),
                'statut' => 'en_attente',
                'frais_scolarite' => $fraisScolarite,
                'frais_cantine' => $validated['frais_cantine'] ?? 0,
                'frais_activites' => $validated['frais_activites'] ?? 0,
                'frais_inscription' => 10000, // 10,000 FCFA
                'mode_paiement' => $validated['mode_paiement'],
                'a_bourse' => $validated['a_bourse'] ?? false,
                'montant_bourse' => $validated['montant_bourse'],
                'pourcentage_bourse' => $validated['pourcentage_bourse'],
                'type_bourse' => $validated['type_bourse'],
            ]);

            // Journaliser
            AuditLog::log('enrollment_created', EnrollmentMP::class, $enrollment->id, null, [
                'student_matricule' => $student->matricule,
                'class' => $class->nom,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Inscription enregistrée avec succès. En attente de validation.',
                'enrollment' => $enrollment->load(['student', 'class']),
                'student' => $student,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Valider une inscription (Direction)
     */
    public function validate(Request $request, string $id)
    {
        $enrollment = EnrollmentMP::with(['student', 'class'])->findOrFail($id);

        if (!$enrollment->isPending()) {
            return response()->json([
                'message' => 'Cette inscription a déjà été traitée.',
            ], 422);
        }

        // Vérifier à nouveau les places
        $class = $enrollment->class;
        if ($class->isFull()) {
            return response()->json([
                'message' => 'La classe est désormais complète. Inscription impossible.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Valider l'inscription
            $enrollment->validate($request->user());

            // Créer le compte utilisateur pour l'élève
            $studentUser = $this->createStudentAccount($enrollment->student);

            // Créer les comptes pour les parents
            $this->createParentAccounts($enrollment->student);

            // TODO: Générer la facture automatiquement
            // TODO: Envoyer notifications SMS/Email

            // Journaliser
            AuditLog::log('enrollment_validated', EnrollmentMP::class, $enrollment->id, null, [
                'student_matricule' => $enrollment->student->matricule,
                'validated_by' => $request->user()->full_name,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Inscription validée avec succès.',
                'enrollment' => $enrollment->fresh(['student', 'class', 'validator']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Refuser une inscription (Direction)
     */
    public function reject(Request $request, string $id)
    {
        $request->validate([
            'motif' => 'required|string|max:500',
        ], [
            'motif.required' => 'Le motif du refus est obligatoire.',
        ]);

        $enrollment = EnrollmentMP::with(['student'])->findOrFail($id);

        if (!$enrollment->isPending()) {
            return response()->json([
                'message' => 'Cette inscription a déjà été traitée.',
            ], 422);
        }

        $enrollment->reject($request->motif);

        // Journaliser
        AuditLog::log('enrollment_rejected', EnrollmentMP::class, $enrollment->id, null, [
            'student_matricule' => $enrollment->student->matricule,
            'motif' => $request->motif,
        ]);

        return response()->json([
            'message' => 'Inscription refusée.',
            'enrollment' => $enrollment->fresh(['student', 'class']),
        ]);
    }

    /**
     * Afficher une inscription
     */
    public function show(string $id)
    {
        $enrollment = EnrollmentMP::with([
            'student.guardians',
            'class.teacher',
            'schoolYear',
            'validator'
        ])->findOrFail($id);

        return response()->json($enrollment);
    }

    /**
     * Mettre à jour une inscription
     */
    public function update(Request $request, string $id)
    {
        $enrollment = EnrollmentMP::findOrFail($id);

        // Seules les inscriptions en attente peuvent être modifiées
        if (!$enrollment->isPending()) {
            return response()->json([
                'message' => 'Seules les inscriptions en attente peuvent être modifiées.',
            ], 422);
        }

        $validated = $request->validate([
            'class_id' => 'sometimes|uuid|exists:school_mp.classes_mp,id',
            'regime' => 'sometimes|in:interne,demi_pensionnaire,externe',
            'mode_paiement' => 'sometimes|in:comptant,tranches_3',
            'frais_cantine' => 'sometimes|numeric|min:0',
            'frais_activites' => 'sometimes|numeric|min:0',
            'a_bourse' => 'sometimes|boolean',
            'montant_bourse' => 'nullable|numeric|min:0',
            'pourcentage_bourse' => 'nullable|numeric|min:0|max:100',
        ]);

        $enrollment->update($validated);

        return response()->json([
            'message' => 'Inscription mise à jour.',
            'enrollment' => $enrollment->fresh(['student', 'class']),
        ]);
    }

    /**
     * Supprimer une inscription
     */
    public function destroy(string $id)
    {
        $enrollment = EnrollmentMP::findOrFail($id);

        if (!$enrollment->isPending()) {
            return response()->json([
                'message' => 'Seules les inscriptions en attente peuvent être supprimées.',
            ], 422);
        }

        $enrollment->delete();

        return response()->json([
            'message' => 'Inscription supprimée.',
        ]);
    }

    // ===== MÉTHODES PRIVÉES =====

    /**
     * Obtenir les frais de scolarité selon le niveau
     */
    private function getFraisScolarite(string $niveau): float
    {
        $tarifs = [
            'PS' => 150000, // Maternelle
            'MS' => 150000,
            'GS' => 150000,
            'CP' => 200000, // Primaire
            'CE1' => 200000,
            'CE2' => 200000,
            'CM1' => 200000,
            'CM2' => 200000,
        ];

        return $tarifs[$niveau] ?? 200000;
    }

    /**
     * Créer les tuteurs
     */
    private function createGuardians(StudentMP $student, array $data): void
    {
        // Père
        if (!empty($data['pere']['nom_complet'])) {
            GuardianMP::create([
                'student_id' => $student->id,
                'type' => 'pere',
                'nom_complet' => $data['pere']['nom_complet'],
                'profession' => $data['pere']['profession'] ?? null,
                'telephone_1' => $data['pere']['telephone_1'] ?? '',
                'telephone_2' => $data['pere']['telephone_2'] ?? null,
                'email' => $data['pere']['email'] ?? null,
                'adresse_physique' => $data['pere']['adresse_physique'] ?? '',
                'est_contact_urgence' => $data['pere']['est_contact_urgence'] ?? false,
            ]);
        }

        // Mère
        if (!empty($data['mere']['nom_complet'])) {
            GuardianMP::create([
                'student_id' => $student->id,
                'type' => 'mere',
                'nom_complet' => $data['mere']['nom_complet'],
                'profession' => $data['mere']['profession'] ?? null,
                'telephone_1' => $data['mere']['telephone_1'] ?? '',
                'telephone_2' => $data['mere']['telephone_2'] ?? null,
                'email' => $data['mere']['email'] ?? null,
                'adresse_physique' => $data['mere']['adresse_physique'] ?? '',
                'est_contact_urgence' => $data['mere']['est_contact_urgence'] ?? false,
            ]);
        }

        // Tuteur
        if (!empty($data['tuteur']['nom_complet'])) {
            GuardianMP::create([
                'student_id' => $student->id,
                'type' => 'tuteur',
                'nom_complet' => $data['tuteur']['nom_complet'],
                'profession' => $data['tuteur']['profession'] ?? null,
                'telephone_1' => $data['tuteur']['telephone_1'] ?? '',
                'telephone_2' => $data['tuteur']['telephone_2'] ?? null,
                'email' => $data['tuteur']['email'] ?? null,
                'adresse_physique' => $data['tuteur']['adresse_physique'] ?? '',
                'lien_parente' => $data['tuteur']['lien_parente'] ?? null,
                'est_contact_urgence' => $data['tuteur']['est_contact_urgence'] ?? true,
            ]);
        }
    }

    /**
     * Créer le compte utilisateur élève
     */
    private function createStudentAccount(StudentMP $student): User
    {
        $email = Str::slug($student->prenoms) . '.' . Str::slug($student->nom) . '@eleve.ecole.bf';
        $password = $student->matricule; // Mot de passe = matricule

        $user = User::create([
            'email' => $email,
            'password' => Hash::make($password),
            'first_name' => $student->prenoms,
            'last_name' => $student->nom,
            'role' => 'eleve',
            'is_active' => true,
        ]);

        // Lier l'utilisateur à l'élève
        $student->user_id = $user->id;
        $student->save();

        return $user;
    }

    /**
     * Créer les comptes utilisateurs parents
     */
    private function createParentAccounts(StudentMP $student): void
    {
        $guardians = $student->guardians()->whereNotNull('email')->get();

        foreach ($guardians as $guardian) {
            // Vérifier si un compte existe déjà
            $existingUser = User::where('email', $guardian->email)->first();

            if ($existingUser) {
                $guardian->user_id = $existingUser->id;
                $guardian->save();
                continue;
            }

            // Créer un nouveau compte
            $password = Str::random(8);

            $user = User::create([
                'email' => $guardian->email,
                'password' => Hash::make($password),
                'first_name' => explode(' ', $guardian->nom_complet)[0] ?? 'Parent',
                'last_name' => explode(' ', $guardian->nom_complet)[1] ?? '',
                'phone' => $guardian->telephone_1,
                'role' => 'parent',
                'is_active' => true,
            ]);

            $guardian->user_id = $user->id;
            $guardian->save();

            // TODO: Envoyer le mot de passe par SMS/Email
        }
    }
}
