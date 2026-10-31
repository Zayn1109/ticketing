<?php

namespace App\Models;

use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use Carbon\CarbonImmutable;
use Database\Factories\EventSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $event_id
 * @property string|null $name
 * @property string $venue_name
 * @property string|null $venue_address
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property CarbonImmutable|null $doors_open_at
 * @property string $timezone
 * @property int|null $capacity
 * @property EventSessionStatus $status
 * @property CarbonImmutable|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancellation_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
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
