<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\OrganizerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $tax_id
 * @property string $contact_email
 * @property string|null $contact_phone
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'tax_id', 'contact_email', 'contact_phone'])]
class Organizer extends Model
{
    /** @use HasFactory<OrganizerFactory> */
    use HasFactory;

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

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
