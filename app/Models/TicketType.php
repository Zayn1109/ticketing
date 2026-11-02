<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $event_session_id
 * @property string $name
 * @property int $price
 * @property int $quota
 * @property CarbonImmutable $sale_start_at
 * @property CarbonImmutable $sale_end_at
 * @property int|null $max_per_user
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'price', 'quota', 'sale_start_at', 'sale_end_at', 'max_per_user'])]
class TicketType extends Model
{
    /** @use HasFactory<TicketTypeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<EventSession, $this>
     */
    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    /**
     * 票種跟著場次：場次可修改時才能新增／修改票種（docs/state-machines.md 後台操作規則）
     */
    public function isEditable(): bool
    {
        return $this->eventSession->isEditable();
    }

    /**
     * 只有草稿活動的票種可以刪除；W9 有訂單後改為「沒有訂單明細」才能刪
     */
    public function isDeletable(): bool
    {
        return $this->eventSession->isDeletable();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_start_at' => 'datetime',
            'sale_end_at' => 'datetime',
        ];
    }
}
