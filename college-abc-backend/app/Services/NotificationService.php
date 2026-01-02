<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Service de Notifications Multi-Canal
 * 
 * Gère l'envoi de notifications via:
 * - SMS (API Burkina Faso)
 * - Email
 * - Notifications internes (push app)
 */
class NotificationService
{
    private string $smsApiUrl;
    private string $smsApiKey;
    private string $smsSenderId;
    private bool $smsEnabled;

    public function __construct()
    {
        $this->smsApiUrl = config('services.sms.api_url', '');
        $this->smsApiKey = config('services.sms.api_key', '');
        $this->smsSenderId = config('services.sms.sender_id', config('app.school_short_name', 'SCHOOL'));
        $this->smsEnabled = config('services.sms.enabled', false);
    }

    /**
     * Envoyer une notification via tous les canaux configurés
     */
    public function notify(
        User $user,
        string $title,
        string $message,
        string $type = Notification::TYPE_SYSTEM,
        array $channels = ['app'],
        array $data = []
    ): array {
        $results = [];

        foreach ($channels as $channel) {
            switch ($channel) {
                case 'app':
                    $results['app'] = $this->sendAppNotification($user, $title, $message, $type, $data);
                    break;
                case 'sms':
                    $results['sms'] = $this->sendSms($user->phone, $message);
                    break;
                case 'email':
                    $results['email'] = $this->sendEmail($user->email, $title, $message, $data);
                    break;
            }
        }

        return $results;
    }

    /**
     * Notification interne (app)
     */
    public function sendAppNotification(
        User $user,
        string $title,
        string $message,
        string $type = Notification::TYPE_SYSTEM,
        array $data = []
    ): bool {
        try {
            Notification::notify(
                $user->id,
                $type,
                $title,
                $message,
                $data,
                Notification::CHANNEL_APP
            );
            return true;
        } catch (\Exception $e) {
            Log::error('App notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoyer un SMS
     */
    public function sendSms(string $phone, string $message): array
    {
        if (!$this->smsEnabled) {
            return ['success' => false, 'error' => 'SMS service disabled'];
        }

        if (empty($phone)) {
            return ['success' => false, 'error' => 'No phone number'];
        }

        // Formater le numéro burkinabè
        $phone = $this->formatBurkinabePhone($phone);

        try {
            // API SMS (exemple avec Orange Burkina ou autre fournisseur)
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->smsApiKey}",
                'Content-Type' => 'application/json',
            ])->post($this->smsApiUrl, [
                'to' => $phone,
                'text' => $message,
                'from' => $this->smsSenderId,
            ]);

            if ($response->successful()) {
                AuditLog::log('sms_sent', null, null, null, [
                    'phone' => $phone,
                    'message_length' => strlen($message)
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->json('id'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error') ?? 'SMS sending failed',
            ];
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envoyer un email
     */
    public function sendEmail(
        string $email,
        string $subject,
        string $message,
        array $data = []
    ): bool {
        if (empty($email)) {
            return false;
        }

        try {
            Mail::send('emails.notification', [
                'content' => $message,
                'data' => $data,
            ], function ($mail) use ($email, $subject) {
                $mail->to($email)
                    ->subject("[" . config('app.school_name', 'École') . "] " . $subject);
            });

            AuditLog::log('email_sent', null, null, null, [
                'email' => $email,
                'subject' => $subject
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Formater un numéro de téléphone burkinabè
     * Convertit en format international +226
     */
    private function formatBurkinabePhone(string $phone): string
    {
        // Retirer tous les caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Si commence par 00226, remplacer par +226
        if (str_starts_with($phone, '00226')) {
            $phone = substr($phone, 2);
        }

        // Si commence par 226, ajouter +
        if (str_starts_with($phone, '226')) {
            return '+' . $phone;
        }

        // Si commence par 0, remplacer par +226
        if (str_starts_with($phone, '0')) {
            return '+226' . substr($phone, 1);
        }

        // Si 8 chiffres (téléphone local), ajouter +226
        if (strlen($phone) === 8) {
            return '+226' . $phone;
        }

        return '+226' . $phone;
    }

    /**
     * Notifier les parents d'un élève
     */
    public function notifyParents(
        array $guardians,
        string $title,
        string $message,
        string $type = Notification::TYPE_SYSTEM,
        array $channels = ['sms']
    ): array {
        $results = [];

        foreach ($guardians as $guardian) {
            $guardianResults = [];

            foreach ($channels as $channel) {
                switch ($channel) {
                    case 'sms':
                        if (!empty($guardian->telephone_1)) {
                            $guardianResults['sms_1'] = $this->sendSms($guardian->telephone_1, $message);
                        }
                        break;
                    case 'email':
                        if (!empty($guardian->email)) {
                            $guardianResults['email'] = $this->sendEmail($guardian->email, $title, $message);
                        }
                        break;
                }
            }

            $results[$guardian->id] = $guardianResults;
        }

        return $results;
    }

    /**
     * Notification d'inscription validée
     */
    public function notifyEnrollmentValidated($enrollment): array
    {
        $student = $enrollment->student;
        $guardians = $student->guardians;

        $message = "Inscription validée: {$student->full_name} est inscrit(e) en {$enrollment->class->nom} pour l'année {$enrollment->schoolYear->name}. Montant: " . number_format($enrollment->montant_final, 0, ',', ' ') . " FCFA.";

        return $this->notifyParents(
            $guardians->all(),
            'Inscription Validée',
            $message,
            Notification::TYPE_ENROLLMENT,
            ['sms', 'email']
        );
    }

    /**
     * Notification de nouvelle note
     */
    public function notifyNewGrade($grade): array
    {
        $student = $grade->student;
        $guardians = $student->guardians;

        $message = "Nouvelle note: {$student->prenoms} a obtenu {$grade->note_sur_20}/20 en {$grade->subject->nom}. {$grade->commentaire}";

        return $this->notifyParents(
            $guardians->all(),
            'Nouvelle Note',
            $message,
            Notification::TYPE_GRADE,
            ['sms']
        );
    }

    /**
     * Notification d'absence
     */
    public function notifyAbsence($attendance): array
    {
        $student = $attendance->student;
        $guardians = $student->guardians;

        $dateFormat = $attendance->date->format('d/m/Y');
        $message = "Absence: {$student->prenoms} a été absent(e) le {$dateFormat}. Motif: {$attendance->motif}. Merci de justifier cette absence.";

        return $this->notifyParents(
            $guardians->all(),
            'Absence Signalée',
            $message,
            Notification::TYPE_ATTENDANCE,
            ['sms']
        );
    }

    /**
     * Notification de rappel de paiement
     */
    public function notifyPaymentReminder($enrollment): array
    {
        $student = $enrollment->student;
        $guardians = $student->guardians;

        $restant = $enrollment->solde_restant;
        $message = "Rappel: Il reste " . number_format($restant, 0, ',', ' ') . " FCFA à payer pour {$student->prenoms}. Merci de régulariser votre situation.";

        return $this->notifyParents(
            $guardians->all(),
            'Rappel de Paiement',
            $message,
            Notification::TYPE_PAYMENT,
            ['sms']
        );
    }

    /**
     * Notification bulletin disponible
     */
    public function notifyBulletinAvailable($reportCard): array
    {
        $student = $reportCard->student;
        $guardians = $student->guardians;

        $message = "Le bulletin du {$reportCard->trimestre}e trimestre de {$student->prenoms} est disponible. Moyenne: {$reportCard->moyenne_generale}/20. Rang: {$reportCard->rang}/{$reportCard->effectif_classe}.";

        return $this->notifyParents(
            $guardians->all(),
            'Bulletin Disponible',
            $message,
            Notification::TYPE_BULLETIN,
            ['sms', 'email']
        );
    }

    /**
     * Envoi en masse (par groupe/classe)
     */
    public function bulkNotify(
        array $users,
        string $title,
        string $message,
        string $type = Notification::TYPE_SYSTEM,
        array $channels = ['app']
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($users as $user) {
            $result = $this->notify($user, $title, $message, $type, $channels);

            $success = collect($result)->every(
                fn($r) =>
                is_bool($r) ? $r : ($r['success'] ?? false)
            );

            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['details'][$user->id] = $result;
        }

        return $results;
    }
}
