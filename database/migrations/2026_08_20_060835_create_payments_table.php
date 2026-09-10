<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Our own audit trail of card payments taken via PayPal's Advanced
     * Credit and Debit Card Payments (see PayPalService/PaymentController).
     * Deliberately holds NO raw card data — number/expiry/CVV are only
     * ever held in memory for the single request that forwards them to
     * PayPal, never written anywhere, including here and including logs.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('uid'); // Firebase Auth uid, not a Laravel users row — see FcmToken's migration for why.
            $table->string('provider')->default('paypal');
            $table->string('paypal_order_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('plan'); // e.g. 'vip_30_days'
            $table->string('status'); // completed | failed
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index('uid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
