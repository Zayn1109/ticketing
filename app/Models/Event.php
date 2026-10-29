<?php

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organizer_id
 * @property string $name
 * @property string|null $description
 * @property EventStatus $status
 * @property int $created_by
 * @property Carbon|null $published_at
 * @property int|null $published_by
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancellation_reason
 * @property Carbon|null $last_session_ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int $sessions_count
 */
#[Fillable(['name', 'description'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Organizer, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<EventSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'published_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_session_ends_at' => 'datetime',
        ];
    }
}
