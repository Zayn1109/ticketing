<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $tax_id
 * @property string $contact_email
 * @property string|null $contact_phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'tax_id', 'contact_email', 'contact_phone'])]
class Organizer extends Model
{
    /**
     * @return BelongsToMany<User, $this, OrganizerUser>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(OrganizerUser::class)
            ->withPivot(['id', 'role', 'invited_by', 'joined_at'])
            ->withTimestamps();
    }
}
