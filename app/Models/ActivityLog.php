<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'log_type', 'description',
        'subject_type', 'subject_id',
        'properties', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('log_type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getOldValuesAttribute(): ?array
    {
        return $this->properties['old'] ?? null;
    }

    public function getNewValuesAttribute(): ?array
    {
        return $this->properties['new'] ?? null;
    }

    // ─── Log Type Constants ───

    public const TYPE_CLUB_CREATED = 'club_created';
    public const TYPE_TEAM_CREATED = 'team_created';
    public const TYPE_FIXTURE_CREATED = 'fixture_created';
    public const TYPE_FIXTURE_PUBLISHED = 'fixture_published';
    public const TYPE_FIXTURE_IMPORTED = 'fixture_imported';
    public const TYPE_SQUAD_SELECTED = 'squad_selected';
    public const TYPE_AVAILABILITY_UPDATED = 'availability_updated';
    public const TYPE_FEE_ASSIGNED = 'fee_assigned';
    public const TYPE_FEE_PAID = 'fee_paid';
    public const TYPE_FEE_VERIFIED = 'fee_verified';
    public const TYPE_MATCH_STARTED = 'match_started';
    public const TYPE_BALL_SCORED = 'ball_scored';
    public const TYPE_BALL_UNDONE = 'ball_undone';
    public const TYPE_INNINGS_ENDED = 'innings_ended';
    public const TYPE_MATCH_COMPLETED = 'match_completed';
    public const TYPE_RESULT_PUBLISHED = 'result_published';
    public const TYPE_INVITATION_SENT = 'invitation_sent';
    public const TYPE_INVITATION_ACCEPTED = 'invitation_accepted';
    public const TYPE_MEMBER_ADDED = 'member_added';
    public const TYPE_MEMBER_REMOVED = 'member_removed';
    public const TYPE_SHORTAGE_REQUESTED = 'shortage_requested';

    public static function log(
        string $type,
        string $description,
        ?Model $subject = null,
        ?int $userId = null,
        ?array $properties = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): self {
        return self::create([
            'log_type' => $type,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'user_id' => $userId ?? auth()->id(),
            'properties' => $properties,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }
}
