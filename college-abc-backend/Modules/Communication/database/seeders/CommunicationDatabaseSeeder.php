<?php

namespace Modules\Communication\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Communication\Entities\CommunicationTemplate;

class CommunicationDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default email templates
        $templates = [
            [
                'name' => 'Bienvenue',
                'slug' => 'welcome',
                'description' => 'Template d\'email de bienvenue pour les nouveaux utilisateurs',
                'channel' => 'email',
                'subject' => 'Bienvenue sur {{app_name}}',
                'content' => 'Bienvenue {{user_name}} sur {{app_name}} !',
                'html_content' => '<h1>Bienvenue {{user_name}} sur {{app_name}} !</h1><p>Votre compte a été créé avec succès.</p>',
                'variables' => [
                    'user_name' => ['type' => 'string', 'required' => true, 'description' => 'Nom de l\'utilisateur'],
                ],
                'is_active' => true,
                'category' => 'auth',
                'priority' => 'normal',
            ],
            [
                'name' => 'Note publiée',
                'slug' => 'grade-published',
                'description' => 'Notification lorsqu\'une note est publiée',
                'channel' => 'email',
                'subject' => 'Nouvelle note disponible - {{subject}}',
                'content' => 'Une nouvelle note a été publiée pour {{subject}}.',
                'html_content' => '<h2>Nouvelle note disponible</h2><p>Une note a été publiée pour la matière <strong>{{subject}}</strong>.</p>',
                'variables' => [
                    'subject' => ['type' => 'string', 'required' => true, 'description' => 'Nom de la matière'],
                    'grade' => ['type' => 'string', 'required' => true, 'description' => 'Note obtenue'],
                ],
                'is_active' => true,
                'category' => 'academic',
                'priority' => 'normal',
            ],
            [
                'name' => 'Absence détectée',
                'slug' => 'absence-notification',
                'description' => 'Notification d\'absence',
                'channel' => 'sms',
                'subject' => 'Absence détectée',
                'content' => 'Une absence a été détectée pour {{student_name}} en {{subject}}.',
                'variables' => [
                    'student_name' => ['type' => 'string', 'required' => true, 'description' => 'Nom de l\'étudiant'],
                    'subject' => ['type' => 'string', 'required' => true, 'description' => 'Nom de la matière'],
                ],
                'is_active' => true,
                'category' => 'attendance',
                'priority' => 'high',
            ],
            [
                'name' => 'Test de communication',
                'slug' => 'test',
                'description' => 'Template de test pour vérifier le système',
                'channel' => 'email',
                'subject' => '{{subject}}',
                'content' => '{{content}}',
                'html_content' => '<h1>{{subject}}</h1><p>{{content}}</p>',
                'variables' => [
                    'subject' => ['type' => 'string', 'required' => false, 'description' => 'Sujet du message'],
                    'content' => ['type' => 'string', 'required' => false, 'description' => 'Contenu du message'],
                ],
                'is_active' => true,
                'category' => 'system',
                'priority' => 'low',
            ],
        ];

        foreach ($templates as $template) {
            CommunicationTemplate::create($template);
        }
    }
}
