<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backs the QR-code / share-link flow (see DeepLinkController and
     * link.yourapp.com/u/{code} in DeepLinkRedirectController). Keyed by
     * Firebase uid, not a Laravel `users` row — same trust model as
     * fcm_tokens (this backend's own auth system is effectively unused;
     * see that migration's doc comment).
     *
     * `payload` is a denormalized snapshot (name/handle/avatar) taken when
     * the code was minted, not a live read of Firestore — this backend has
     * no Firestore access, so it's the only way the web landing page
     * (case B: app not installed) can show who the invite is from.
     */
    public function up(): void
    {
        Schema::create('deep_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            // 'add_friend' is the only type wired up today; the column
            // isn't narrowed to that so a future deep-link kind (a shared
            // moment, a voice room invite, ...) doesn't need a new table.
            $table->string('type');
            $table->string('uid');
            $table->json('payload')->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deep_links');
    }
};
