<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real user targets are Firebase Auth uids (strings), not Laravel model
 * ids — see AdminAuditLog::recordFor() and FirestoreUserDirectory.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_audit_logs', function (Blueprint $table) {
            $table->string('target_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('admin_audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('target_id')->nullable()->change();
        });
    }
};
