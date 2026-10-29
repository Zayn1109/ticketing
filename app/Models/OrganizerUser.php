<?php

namespace App\Models;

use App\Enums\OrganizerRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organizer_id
 * @property int $user_id
 * @property OrganizerRole $role
 * @property int|null $invited_by
 * @property Carbon $joined_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OrganizerUser extends Pivot
{
    protected $table = 'organizer_user';

    // 中介表有自己的 id 主鍵，Pivot 預設不是自增
    public $incrementing = true;

    /**
     * @return BelongsTo<Organizer, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => OrganizerRole::class,
            'joined_at' => 'datetime',
        ];
    }
}
