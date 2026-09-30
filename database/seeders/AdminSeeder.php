<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

/**
 * Dev/staging login for the admin panel. Upserts by email so reseeding
 * is safe against the `admins.email` unique constraint.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => 'password', // hashed automatically by Admin's 'hashed' cast
            ]
        );
    }
}
