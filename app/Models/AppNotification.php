<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id', 'type', 'title', 'message',
        'notifiable_type', 'notifiable_id',
        'data',
        'sent_push', 'sent_email', 'sent_sms',
        'is_read', 'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_push' => 'boolean',
        'sent_email' => 'boolean',
        'sent_sms' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // ─── Notification Type Constants ───

    public const TYPE_FIXTURE_PUBLISHED = 'fixture_published';
    public const TYPE_AVAILABILITY_REQUESTED = 'availability_requested';
    public const TYPE_SQUAD_SELECTED = 'squad_selected';
    public const TYPE_FEE_ASSIGNED = 'fee_assigned';
    public const TYPE_FEE_VERIFIED = 'fee_verified';
    public const TYPE_MATCH_STARTED = 'match_started';
    public const TYPE_MATCH_COMPLETED = 'match_completed';
    public const TYPE_MATCH_RESULT = 'match_result';
    public const TYPE_INVITATION_RECEIVED = 'invitation_received';
    public const TYPE_SHORTAGE_REQUEST = 'shortage_request';
    public const TYPE_SHORTAGE_FULFILLED = 'shortage_fulfilled';
    public const TYPE_PLAYER_UNAVAILABLE = 'player_unavailable';

    public static function types(): array
    {
        return [
            self::TYPE_FIXTURE_PUBLISHED,
            self::TYPE_AVAILABILITY_REQUESTED,
            self::TYPE_SQUAD_SELECTED,
            self::TYPE_FEE_ASSIGNED,
            self::TYPE_FEE_VERIFIED,
            self::TYPE_MATCH_STARTED,
            self::TYPE_MATCH_COMPLETED,
            self::TYPE_MATCH_RESULT,
            self::TYPE_INVITATION_RECEIVED,
            self::TYPE_SHORTAGE_REQUEST,
            self::TYPE_SHORTAGE_FULFILLED,
            self::TYPE_PLAYER_UNAVAILABLE,
        ];
    }
}
