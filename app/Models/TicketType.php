<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_session_id
 * @property string $name
 * @property int $price
 * @property int $quota
 * @property Carbon $sale_start_at
 * @property Carbon $sale_end_at
 * @property int|null $max_per_user
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'price', 'quota', 'sale_start_at', 'sale_end_at', 'max_per_user'])]
class TicketType extends Model
{
    /**
     * @return BelongsTo<EventSession, $this>
     */
    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
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
