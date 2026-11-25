<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Local copy of Argentina's holidays. Shown on the calendar as reference
 * only: holidays don't affect any rule.
 *
 * @property int $id
 * @property CarbonImmutable $date
 * @property string $name
 * @property string|null $type
 */
#[Fillable(['date', 'name', 'type'])]
class Holiday extends Model
{
    protected function casts(): array
    {
        return ['date' => 'immutable_date'];
    }
}
