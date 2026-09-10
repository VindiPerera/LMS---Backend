<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per Firebase Auth uid holding that device's current FCM
 * registration token. See the create_fcm_tokens_table migration for why
 * this exists instead of reading the token out of Firestore's
 * users/{uid}.fcmToken field (which is also still written, harmlessly, by
 * push_notification_service.dart — this table is what NotificationController
 * actually reads from, so it never needs Firestore access itself).
 */
class FcmToken extends Model
{
    protected $fillable = ['uid', 'token'];
}
