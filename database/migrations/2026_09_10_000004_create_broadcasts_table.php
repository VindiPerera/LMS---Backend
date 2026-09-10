<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per bulk push send. See App\Jobs\SendBroadcastPush — the actual
 * sending happens queued, this row is what the admin panel polls/displays
 * for progress and history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('body', 1000);
            // Audience selector, matching FirestoreUserDirectory::search()'s
            // single-filter v1 scope — e.g. {"role":"teacher"}, {"is_vip":"1"},
            // or {} for "everyone with a saved FCM token".
            $table->json('audience_filters');
            $table->unsignedInteger('recipient_estimate')->default(0);
            $table->string('status')->default('pending'); // pending|sending|completed|failed
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
