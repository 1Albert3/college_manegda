<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Notification - Base centrale (school_core)
 * 
 * Gestion des notifications utilisateurs (SMS, Email, Push)
 */
class Notification extends Model
{
    use HasUuids;

    // protected $connection = 'school_core';
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'title',
        'content',
        'data',
        'is_read',
        'read_at',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Types de notifications
     */
    const TYPE_ENROLLMENT = 'enrollment';
    const TYPE_GRADE = 'grade';
    const TYPE_ATTENDANCE = 'attendance';
    const TYPE_PAYMENT = 'payment';
    const TYPE_BULLETIN = 'bulletin';
    const TYPE_SYSTEM = 'system';
    const TYPE_MESSAGE = 'message';

    /**
     * Canaux de notification
     */
    const CHANNEL_APP = 'app';
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead(): void
    {
        $this->is_read = true;
        $this->read_at = now();
        $this->save();
    }

    /**
     * Créer une notification
     */
    public static function notify(
        string $userId,
        string $type,
        string $title,
        string $content,
        array $data = [],
        string $channel = self::CHANNEL_APP
    ): self {
        return static::create([
            'user_id' => $userId,
            'type' => $type,
            'channel' => $channel,
            'title' => $title,
            'content' => $content,
            'data' => $data,
            'is_read' => false,
            'sent_at' => now(),
        ]);
    }

    /**
     * Scope: non lues
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: par type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: par canal
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}
