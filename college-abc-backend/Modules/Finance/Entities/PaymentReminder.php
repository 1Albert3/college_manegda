<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Student\Entities\Student;

class PaymentReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'student_id',
        'type',
        'message',
        'reminder_date',
        'status',
        'sent_at',
        'error_message',
        'attempt_count',
    ];

    protected $casts = [
        'reminder_date' => 'date',
        'sent_at' => 'datetime',
        'attempt_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', 'planifie');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'envoye');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'echoue');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'annule');
    }

    public function scopeDueToday($query)
    {
        return $query->where('reminder_date', now()->toDateString())
                     ->where('status', 'planifie');
    }

    public function scopeDueSoon($query, int $days = 3)
    {
        return $query->whereBetween('reminder_date', [now(), now()->addDays($days)])
                     ->where('status', 'planifie');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeOrderByRecent($query)
    {
        return $query->orderBy('reminder_date', 'desc');
    }

    // Accessors
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'sms' => 'SMS',
            'email' => 'Email',
            'notification' => 'Notification',
            default => ucfirst($this->type),
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'planifie' => 'Planifié',
            'envoye' => 'Envoyé',
            'echoue' => 'Échoué',
            'annule' => 'Annulé',
            default => ucfirst($this->status),
        };
    }

    public function getIsSentAttribute()
    {
        return $this->status === 'envoye';
    }

    public function getIsScheduledAttribute()
    {
        return $this->status === 'planifie';
    }

    public function getIsFailedAttribute()
    {
        return $this->status === 'echoue';
    }

    public function getIsDueTodayAttribute()
    {
        return $this->reminder_date->isToday() && $this->is_scheduled;
    }

    public function getIsPastDueAttribute()
    {
        return $this->reminder_date < now() && $this->is_scheduled;
    }

    // Methods
    public function markAsSent()
    {
        $this->status = 'envoye';
        $this->sent_at = now();
        return $this->save();
    }

    public function markAsFailed(string $errorMessage)
    {
        $this->status = 'echoue';
        $this->error_message = $errorMessage;
        $this->attempt_count += 1;
        return $this->save();
    }

    public function cancel()
    {
        $this->status = 'annule';
        return $this->save();
    }

    public function retry()
    {
        if ($this->attempt_count >= 3) {
            throw new \Exception('Maximum retry attempts reached');
        }

        $this->status = 'planifie';
        $this->error_message = null;
        return $this->save();
    }

    public function reschedule(\DateTime $newDate)
    {
        if (!$this->is_scheduled) {
            throw new \Exception('Can only reschedule scheduled reminders');
        }

        $this->reminder_date = $newDate;
        return $this->save();
    }

    public function send()
    {
        try {
            switch ($this->type) {
                case 'sms':
                    return $this->sendSMS();
                case 'email':
                    return $this->sendEmail();
                case 'notification':
                    return $this->sendNotification();
                default:
                    throw new \Exception('Unknown reminder type: ' . $this->type);
            }
        } catch (\Exception $e) {
            $this->markAsFailed($e->getMessage());
            return false;
        }
    }

    protected function sendSMS()
    {
        // Cette méthode sera implémentée dans le service SMS
        // Pour l'instant, on simule l'envoi
        $smsService = app(\Modules\Communication\Services\SMSService::class);
        
        $parent = $this->student->primaryParent;
        if (!$parent || !$parent->phone) {
            throw new \Exception('No phone number available for student parent');
        }

        $sent = $smsService->send($parent->phone, $this->message);
        
        if ($sent) {
            $this->markAsSent();
            return true;
        }
        
        throw new \Exception('Failed to send SMS');
    }

    protected function sendEmail()
    {
        // Cette méthode sera implémentée avec le système d'email
        $parent = $this->student->primaryParent;
        if (!$parent || !$parent->email) {
            throw new \Exception('No email available for student parent');
        }

        // TODO: Implémenter l'envoi d'email
        $this->markAsSent();
        return true;
    }

    protected function sendNotification()
    {
        // Cette méthode sera implémentée avec le système de notifications
        // TODO: Implémenter les notifications in-app
        $this->markAsSent();
        return true;
    }

    public static function createForInvoice(Invoice $invoice, string $type = 'sms', int $daysBeforeDue = 3)
    {
        $reminderDate = $invoice->due_date->copy()->subDays($daysBeforeDue);
        
        if ($reminderDate < now()) {
            $reminderDate = now()->addDay();
        }

        $message = self::generateMessage($invoice);

        return self::create([
            'invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'type' => $type,
            'message' => $message,
            'reminder_date' => $reminderDate,
            'status' => 'planifie',
        ]);
    }

    protected static function generateMessage(Invoice $invoice)
    {
        $studentName = $invoice->student->full_name;
        $dueAmount = $invoice->formatted_due_amount;
        $dueDate = $invoice->due_date->format('d/m/Y');

        return "Cher parent de {$studentName}, " .
               "nous vous rappelons qu'un montant de {$dueAmount} reste à payer avant le {$dueDate}. " .
               "Merci de régulariser votre situation. Collège Wend-Manegda.";
    }
}
