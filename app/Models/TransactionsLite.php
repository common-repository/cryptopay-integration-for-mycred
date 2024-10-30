<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\MyCred\Models;

use BeycanPress\CryptoPayLite\Models\AbstractTransaction;

class TransactionsLite extends AbstractTransaction
{
    public string $addon = 'mycred_buycred';

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct('mycred_buycred_transaction');
    }
}
