<?php

namespace Abivia\Ledger\Models;

use Abivia\Ledger\Messages\Name;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Multilingual support for account names
 *
 * @method static LedgerName create(array $attributes) Provided by model.
 * @property Carbon $created_at When the record was created.
 * @property int $id Primary key
 * @property string $language The language code for this name.
 * @property string $name The ledger entity name.
 * @property string $ownerUuid ID of the entity this name applies to.
 * @property Carbon $updated_at When the record was updated.
 * @mixin Builder
 */
class LedgerName extends Model
{
    use HasFactory;

    const CODE_SIZE = 8;

    protected $dateFormat = 'Y-m-d H:i:s.u';
    protected $fillable = ['language', 'name', 'ownerUuid'];

    public static function getWildcard(string $ownerUuid, string $wildcard): Builder
    {
        /** @var Builder $query */
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $query = static::where('ownerUuid', $ownerUuid);
        if (strpos($wildcard, '*') === false) {
            $query = $query->where('language', $wildcard);
        } else {
            $likeCard = str_replace('*', '%', $wildcard);
            $query = $query->where('language', 'like', $likeCard);
        }
        return $query;
    }

    public static function createFromMessage(Name $message): self
    {
        $instance = new static();
        foreach ($instance->fillable as $property) {
            if (isset($message->{$property})) {
                $instance->{$property} = $message->{$property};
            }
        }
        $instance->save();
        $instance->refresh();

        return $instance;
    }

    public function toResponse(): array
    {
        return [
            'name' => $this->name,
            'language' => $this->language,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

}
