<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Role: matches FaceTalkRole enum in signup_screen.dart (student|teacher)
            $table->string('role')->default('student')->after('email');

            // Profile fields — mirror lib/models/user.dart (AppUser)
            $table->string('handle')->unique()->nullable()->after('role');
            $table->string('avatar_url')->nullable()->after('handle');
            $table->string('country_flag')->nullable()->after('avatar_url');
            $table->string('native_lang')->nullable()->after('country_flag');
            $table->string('learning_lang')->nullable()->after('native_lang');
            $table->boolean('is_online')->default(false)->after('learning_lang');
            $table->boolean('is_vip')->default(false)->after('is_online');
            $table->unsignedSmallInteger('age')->nullable()->after('is_vip');
            $table->string('gender')->default('other')->after('age');
            $table->text('bio')->nullable()->after('gender');
            $table->string('active_label')->nullable()->after('bio');
            $table->json('tags')->nullable()->after('active_label');

            // Teacher-specific / interests field from CreateProfileScreen ("Subject you teach" / "Interests / Grade")
            $table->string('detail')->nullable()->after('tags');

            // Track whether the user has completed CreateProfileScreen
            $table->boolean('profile_completed')->default(false)->after('detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'handle',
                'avatar_url',
                'country_flag',
                'native_lang',
                'learning_lang',
                'is_online',
                'is_vip',
                'age',
                'gender',
                'bio',
                'active_label',
                'tags',
                'detail',
                'profile_completed',
            ]);
        });
    }
};
