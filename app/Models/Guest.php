<?php

namespace App\Models;

use App\Domain\PhoneNormalizer;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The reservation holder. Phone is required and unique; the DNI/CUIT is
 * optional but unique when present. Both are stored as digits only.
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string|null $document_number
 * @property-read string $full_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['first_name', 'last_name', 'phone', 'document_number'])]
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    /**
     * Stored in canonical form (see PhoneNormalizer) so uniqueness works.
     */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => PhoneNormalizer::normalize($value));
    }

    protected function documentNumber(): Attribute
    {
        return Attribute::set(fn (?string $value) => self::digits($value) ?: null);
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->last_name));
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value);
    }
}
