<?php

namespace App\Models;

use Database\Factories\ApartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A rentable unit of the apart. Only active apartments can be booked.
 *
 * @property int $id
 * @property string $number
 * @property int $capacity
 * @property bool $is_active
 * @property string|null $description
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['number', 'capacity', 'is_active', 'description', 'sort_order'])]
class Apartment extends Model
{
    /** @use HasFactory<ApartmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Apartment>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Calendar and list order: sort_order first, number as tiebreaker.
     *
     * @param  Builder<Apartment>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('number');
    }
}
