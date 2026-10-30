<?php

namespace App\Models;

use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use Database\Factories\EventSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property string|null $name
 * @property string $venue_name
 * @property string|null $venue_address
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon|null $doors_open_at
 * @property string $timezone
 * @property int|null $capacity
 * @property EventSessionStatus $status
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancellation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'venue_name', 'venue_address', 'starts_at', 'ends_at', 'doors_open_at', 'timezone', 'capacity'])]
class EventSession extends Model
{
    /** @use HasFactory<EventSessionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<TicketType, $this>
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    /**
     * 已取消的場次、或所屬活動已取消時，不可再修改（docs/state-machines.md 後台操作規則）
     */
    public function isEditable(): bool
    {
        return $this->status === EventSessionStatus::Scheduled
            && $this->event->isEditable();
    }

    /**
     * 只有草稿活動的場次可以刪除；已發布活動的場次只能取消
     */
    public function isDeletable(): bool
    {
        return $this->event->status === EventStatus::Draft;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventSessionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'doors_open_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
