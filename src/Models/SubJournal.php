<?php

namespace Abivia\Ledger\Models;

use Abivia\Ledger\Helpers\Revision;
use Abivia\Ledger\Messages\EntityRef;
use Abivia\Ledger\Messages\SubJournal as JournalMessage;
use Abivia\Ledger\Traits\HasRevisions;
use Abivia\Ledger\Traits\UuidPrimaryKey;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Domains assigned within the ledger.
 *
 * @method static SubJournal create(array $attributes) Provided by model.
 * @property string $code Unique identifier for the sub-journal.
 * @property Carbon $created_at When the record was created.
 * @property string $extra Application defined information.
 * @property LedgerName[] $names
 * @property Carbon $revision Revision timestamp to detect race condition on update.
 * @property string $subJournalUuid Identifier for this journal.
 * @property Carbon $updated_at When the record was updated.
 * @mixin Builder
 */
class SubJournal extends Model
{
    use HasFactory, HasRevisions, UuidPrimaryKey;

    protected $casts = [
        'revision' => 'datetime',
    ];
    protected $dateFormat = 'Y-m-d H:i:s.u';
    protected $fillable = ['code', 'extra'];
    public $incrementing = false;
    protected $keyType = 'string';
    public $primaryKey = 'subJournalUuid';

    public static function createFromMessage(JournalMessage $message): self
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

    /**
     * @param EntityRef $entityRef
     * @return Builder
     * @throws Exception
     * @noinspection PhpIncompatibleReturnTypeInspection
     * @noinspection PhpDynamicAsStaticMethodCallInspection
     */
    public static function findWith(EntityRef $entityRef): Builder
    {
        if (isset($entityRef->uuid) && $entityRef->uuid !== null) {
            $finder = self::where('domainUuid', $entityRef->uuid);
        } elseif (isset($entityRef->code)) {
            $finder = self::where('code', $entityRef->code);
        } else {
            throw new Exception('Journal reference must have either code or uuid entries');
        }

        return $finder;
    }

    public function names(): HasMany
    {
        return $this->hasMany(LedgerName::class, 'ownerUuid', 'domainUuid');
    }

    public function toResponse(): array
    {
        $response = ['uuid' => $this->subJournalUuid];
        $response['code'] = $this->code;
        $response['names'] = [];
        foreach ($this->names as $name) {
            $response['names'][] = $name->toResponse();
        }
        if ($this->extra !== null) {
            $response['extra'] = $this->extra;
        }
        $response['revision'] = Revision::create($this->revision, $this->updated_at);
        $response['createdAt'] = $this->created_at;
        $response['updatedAt'] = $this->updated_at;

        return $response;
    }

}
