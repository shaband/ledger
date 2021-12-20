<?php
declare(strict_types=1);

namespace Abivia\Ledger\Http\Controllers;

use Abivia\Ledger\Exceptions\Breaker;
use Abivia\Ledger\Messages\Balance;
use Abivia\Ledger\Models\JournalEntry;
use Abivia\Ledger\Models\LedgerAccount;
use Abivia\Ledger\Models\LedgerBalance;
use Abivia\Ledger\Models\LedgerCurrency;
use Abivia\Ledger\Models\LedgerDomain;
use Abivia\Ledger\Messages\Currency;
use Abivia\Ledger\Messages\Message;
use Abivia\Ledger\Traits\Audited;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Handle Balance requests.
 */
class LedgerBalanceController extends Controller
{
    /**
     * Get a balance. Returns null if the account is valid but no balance has been created.
     *
     * @param Balance $message
     * @return ?LedgerBalance
     * @throws Breaker
     */
    public function get(Balance $message): ?LedgerBalance
    {
        $message->validate(Message::OP_GET);
        /** @var LedgerAccount $ledgerAccount */
        $ledgerAccount = LedgerAccount::findWith($message->account)->first();
        if ($ledgerAccount === null) {
            throw Breaker::withCode(
                Breaker::INVALID_DATA,
                [__('Account :code not found.', ['code' => $message->account->code])]
            );
        }
        $message->account->uuid = $ledgerAccount->ledgerUuid;
        /** @var LedgerDomain $ledgerDomain */
        $ledgerDomain = LedgerDomain::findWith($message->domain)->first();
        if ($ledgerDomain === null) {
            throw Breaker::withCode(
                Breaker::INVALID_DATA,
                __('Domain :code not found.', ['code' => $message->domain->code])
            );
        }
        $message->domain->uuid = $ledgerDomain->domainUuid;

        $ledgerBalance = LedgerBalance::where('ledgerUuid', $ledgerAccount->ledgerUuid)
            ->where('domainUuid', $ledgerDomain->domainUuid)
            ->where('currency', $message->currency)
            ->first();

        return $ledgerBalance;
    }

    /**
     * Perform a currency operation.
     *
     * @param Balance $message
     * @param int $opFlag
     * @return LedgerBalance|null
     * @throws Breaker
     */
    public function run(Balance $message, int $opFlag): ?LedgerBalance
    {
        switch ($opFlag & Message::ALL_OPS) {
            case Message::OP_GET:
            case Message::OP_QUERY:
                return $this->get($message);
            default:
                throw Breaker::withCode(Breaker::RULE_VIOLATION);
        }
    }

}
