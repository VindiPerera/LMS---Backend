<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keyed by Firebase Auth uid, not a Laravel `users` row — this backend's
     * own auth system is effectively unused now (the app authenticates
     * against Firebase Auth directly; see AuthController's Google-sign-in
     * TODO). This table exists purely so NotificationController can look up
     * "what FCM token does uid X have right now" when push_notification_
     * service.dart (Flutter) posts a token here — replacing the Cloud
     * Functions -> Firestore push pipeline this project can't afford to
     * deploy (Cloud Functions requires the paid Blaze plan).
     */
    public function up(): void
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->text('token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};
