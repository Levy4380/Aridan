<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Catalog of per-reservation extras (first iteration: Cochera, Desayuno).
 * The name is what the UI shows; config/extras.php maps it to an icon.
 *
 * @property int $id
 * @property string $name
 * @property bool $is_active
 */
#[Fillable(['name', 'is_active'])]
class Extra extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Whether the extras flow is on (config/extras.php). While off, the UI
     * hides them and saving a reservation leaves its extras untouched.
     */
    public static function enabled(): bool
    {
        return (bool) config('extras.enabled');
    }

    public function icon(): ?string
    {
        return config('extras.icons.'.$this->name);
    }

    /**
     * @return BelongsToMany<Reservation, $this>
     */
    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class);
    }
}
