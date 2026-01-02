<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder pour les rôles, permissions et utilisateurs initiaux
 * Conforme au cahier des charges Burkina Faso
 */
class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les rôles système
        $roles = $this->createRoles();

        // Créer les permissions
        $permissions = $this->createPermissions();

        // Assigner les permissions aux rôles
        $this->assignPermissions($roles, $permissions);

        // Créer les utilisateurs par défaut
        $this->createDefaultUsers($roles);
    }

    /**
     * Créer les rôles système
     */
    private function createRoles(): array
    {
        $rolesData = [
            [
                'name' => 'direction',
                'display_name' => 'Direction / Administration',
                'description' => 'Supervision, validation, décisions. Accès à tous les tableaux de bord globaux et rapports consolidés.',
                'is_system' => true,
            ],
            [
                'name' => 'secretariat',
                'display_name' => 'Secrétariat',
                'description' => 'Gestion administrative et dossiers élèves. Inscriptions, affectations, génération matricules.',
                'is_system' => true,
            ],
            [
                'name' => 'comptabilite',
                'display_name' => 'Comptabilité',
                'description' => 'Gestion financière exclusive. Facturation, paiements, relances, rapports financiers.',
                'is_system' => true,
            ],
            [
                'name' => 'enseignant',
                'display_name' => 'Enseignant',
                'description' => 'Pédagogie et suivi académique. Saisie notes, absences, appréciations.',
                'is_system' => true,
            ],
            [
                'name' => 'parent',
                'display_name' => 'Parent',
                'description' => 'Suivi et communication. Consultation notes, bulletins, absences, paiements.',
                'is_system' => true,
            ],
            [
                'name' => 'eleve',
                'display_name' => 'Élève',
                'description' => 'Consultation et participation. Emplois du temps, résultats, bulletins.',
                'is_system' => true,
            ],
        ];

        $roles = [];
        foreach ($rolesData as $data) {
            $roles[$data['name']] = Role::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
        }

        return $roles;
    }

    /**
     * Créer les permissions par module
     */
    private function createPermissions(): array
    {
        $modules = [
            'inscriptions' => [
                'create' => 'Créer une inscription',
                'read' => 'Consulter les inscriptions',
                'update' => 'Modifier une inscription',
                'delete' => 'Supprimer une inscription',
                'validate' => 'Valider une inscription',
                'reject' => 'Refuser une inscription',
            ],
            'students' => [
                'create' => 'Créer un dossier élève',
                'read' => 'Consulter les dossiers élèves',
                'update' => 'Modifier un dossier élève',
                'delete' => 'Supprimer un dossier élève',
                'export' => 'Exporter les données élèves',
                'import' => 'Importer des élèves',
            ],
            'classes' => [
                'create' => 'Créer une classe',
                'read' => 'Consulter les classes',
                'update' => 'Modifier une classe',
                'delete' => 'Supprimer une classe',
                'assign' => 'Affecter des élèves',
                'duplicate' => 'Dupliquer pour nouvelle année',
            ],
            'teachers' => [
                'create' => 'Créer un dossier enseignant',
                'read' => 'Consulter les enseignants',
                'update' => 'Modifier un dossier enseignant',
                'delete' => 'Supprimer un enseignant',
                'assign' => 'Affecter aux classes',
                'schedule' => 'Gérer les emplois du temps',
            ],
            'grades' => [
                'create' => 'Saisir des notes',
                'read' => 'Consulter les notes',
                'update' => 'Modifier des notes',
                'delete' => 'Supprimer des notes',
                'publish' => 'Publier les notes',
                'lock' => 'Verrouiller les notes',
            ],
            'attendance' => [
                'create' => 'Enregistrer absences/retards',
                'read' => 'Consulter les absences',
                'update' => 'Modifier les absences',
                'delete' => 'Supprimer une absence',
                'justify' => 'Justifier une absence',
                'stats' => 'Statistiques absences',
            ],
            'bulletins' => [
                'generate' => 'Générer les bulletins',
                'read' => 'Consulter les bulletins',
                'validate' => 'Valider les bulletins',
                'print' => 'Imprimer les bulletins',
                'export' => 'Exporter les bulletins',
            ],
            'finance' => [
                'invoice' => 'Créer des factures',
                'payment' => 'Enregistrer des paiements',
                'receipt' => 'Générer des reçus',
                'reminder' => 'Envoyer des relances',
                'block' => 'Bloquer pour impayés',
                'stats' => 'Statistiques financières',
                'read' => 'Consulter les données financières',
            ],
            'schedule' => [
                'create' => 'Créer des emplois du temps',
                'read' => 'Consulter les emplois du temps',
                'update' => 'Modifier les emplois du temps',
                'delete' => 'Supprimer des créneaux',
                'publish' => 'Publier les emplois du temps',
                'export' => 'Exporter les emplois du temps',
            ],
            'discipline' => [
                'create' => 'Enregistrer une sanction',
                'read' => 'Consulter les sanctions',
                'update' => 'Modifier une sanction',
                'delete' => 'Supprimer une sanction',
                'notify' => 'Notifier les parents',
                'stats' => 'Statistiques discipline',
            ],
            'orientation' => [
                'create' => 'Créer une fiche orientation',
                'read' => 'Consulter les orientations',
                'update' => 'Modifier une orientation',
                'validate' => 'Valider une orientation',
                'propose' => 'Proposer une série',
            ],
            'reports' => [
                'generate' => 'Générer des rapports',
                'read' => 'Consulter les rapports',
                'export' => 'Exporter les rapports',
                'schedule' => 'Planifier des rapports',
            ],
            'users' => [
                'create' => 'Créer un utilisateur',
                'read' => 'Consulter les utilisateurs',
                'update' => 'Modifier un utilisateur',
                'delete' => 'Supprimer un utilisateur',
                'assign_role' => 'Assigner des rôles',
                'block' => 'Bloquer un compte',
            ],
            'audit' => [
                'read' => 'Consulter les logs',
                'export' => 'Exporter les logs',
                'filter' => 'Filtrer les logs',
            ],
            'settings' => [
                'read' => 'Consulter les paramètres',
                'update' => 'Modifier les paramètres',
            ],
            'communication' => [
                'send_sms' => 'Envoyer des SMS',
                'send_email' => 'Envoyer des emails',
                'read_messages' => 'Lire les messages',
                'reply' => 'Répondre aux messages',
            ],
        ];

        $permissions = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action => $displayName) {
                $name = "{$module}.{$action}";
                $permissions[$name] = Permission::updateOrCreate(
                    ['name' => $name],
                    [
                        'display_name' => $displayName,
                        'module' => $module,
                    ]
                );
            }
        }

        return $permissions;
    }

    /**
     * Assigner les permissions aux rôles
     */
    private function assignPermissions(array $roles, array $permissions): void
    {
        // DIRECTION - Accès complet sauf saisie notes et paiements
        $directionPermissions = array_filter(array_keys($permissions), function ($p) {
            // Exclure saisie notes et modification paiements
            return !in_array($p, ['grades.create', 'grades.update', 'grades.delete', 'finance.payment']);
        });
        $roles['direction']->permissions()->sync(
            array_map(fn($p) => $permissions[$p]->id, $directionPermissions)
        );

        // SECRÉTARIAT
        $secretariatPermissions = [
            'inscriptions.create',
            'inscriptions.read',
            'inscriptions.update',
            'students.create',
            'students.read',
            'students.update',
            'students.export',
            'classes.read',
            'classes.assign',
            'teachers.read',
            'finance.read', // Lecture seule
            'communication.send_email',
            'communication.read_messages',
            'communication.reply',
            'attendance.read',
            'bulletins.read',
        ];
        $roles['secretariat']->permissions()->sync(
            array_map(fn($p) => $permissions[$p]->id, $secretariatPermissions)
        );

        // COMPTABILITÉ
        $comptabilitePermissions = [
            'finance.invoice',
            'finance.payment',
            'finance.receipt',
            'finance.reminder',
            'finance.block',
            'finance.stats',
            'finance.read',
            'students.read', // Pour identifier les élèves
            'classes.read',
            'reports.generate',
            'reports.read',
            'reports.export',
        ];
        $roles['comptabilite']->permissions()->sync(
            array_map(fn($p) => $permissions[$p]->id, $comptabilitePermissions)
        );

        // ENSEIGNANT
        $enseignantPermissions = [
            'grades.create',
            'grades.read',
            'grades.update',
            'grades.publish',
            'attendance.create',
            'attendance.read',
            'attendance.update',
            'students.read', // Consultation académique
            'classes.read',
            'schedule.read',
            'communication.send_email',
            'communication.read_messages',
            'communication.reply',
            'bulletins.read',
        ];
        $roles['enseignant']->permissions()->sync(
            array_map(fn($p) => $permissions[$p]->id, $enseignantPermissions)
        );

        // PARENT
        $parentPermissions = [
            'grades.read',
            'bulletins.read',
            'attendance.read',
            'discipline.read',
            'finance.read',
            'communication.read_messages',
            'communication.reply',
            'schedule.read',
        ];
        $roles['parent']->permissions()->sync(
            array_map(fn($p) => $permissions[$p]->id, $parentPermissions)
        );

        // ÉLÈVE
        $elevePermissions = [
            'grades.read',
            'bulletins.read',
            'schedule.read',
            'communication.read_messages',
        ];
        $roles['eleve']->permissions()->sync(
            array_map(fn($p) => $permissions[$p]->id, $elevePermissions)
        );
    }

    /**
     * Créer les utilisateurs par défaut
     */
    private function createDefaultUsers(array $roles): void
    {
        $users = [
            [
                'email' => 'direction@ecole.bf',
                'password' => Hash::make('Direction@2024'),
                'first_name' => 'Directeur',
                'last_name' => 'Principal',
                'role' => 'direction',
                'two_factor_enabled' => true,
            ],
            [
                'email' => 'secretariat@ecole.bf',
                'password' => Hash::make('Secretariat@2024'),
                'first_name' => 'Secrétaire',
                'last_name' => 'Général',
                'role' => 'secretariat',
            ],
            [
                'email' => 'comptabilite@ecole.bf',
                'password' => Hash::make('Comptabilite@2024'),
                'first_name' => 'Comptable',
                'last_name' => 'Principal',
                'role' => 'comptabilite',
                'two_factor_enabled' => true,
            ],
            [
                'email' => 'enseignant@ecole.bf',
                'password' => Hash::make('Enseignant@2024'),
                'first_name' => 'Jean',
                'last_name' => 'OUEDRAOGO',
                'role' => 'enseignant',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Assigner le rôle
            $role = $roles[$userData['role']];
            $user->roles()->syncWithoutDetaching($role->id);
        }

        $this->command->info('Utilisateurs par défaut créés:');
        $this->command->table(
            ['Email', 'Mot de passe', 'Rôle'],
            [
                ['direction@ecole.bf', 'Direction@2024', 'Direction'],
                ['secretariat@ecole.bf', 'Secretariat@2024', 'Secrétariat'],
                ['comptabilite@ecole.bf', 'Comptabilite@2024', 'Comptabilité'],
                ['enseignant@ecole.bf', 'Enseignant@2024', 'Enseignant'],
            ]
        );
    }
}
