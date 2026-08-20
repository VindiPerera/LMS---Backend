<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Audit trail only — see the create_payments_table migration for why this
 * never holds raw card data.
 */
class Payment extends Model
{
    protected $fillable = [
        'uid', 'provider', 'paypal_order_id', 'amount', 'currency', 'plan', 'status', 'failure_reason',
    ];
}
