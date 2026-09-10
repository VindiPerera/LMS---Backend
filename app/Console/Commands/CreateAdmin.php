<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Bootstraps the (single) admin-panel login. There's no self-registration
 * screen by design — admin accounts are only ever created from the CLI.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {name} {--password=}';

    protected $description = 'Create an admin-panel login (separate from the mobile app\'s users table)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->argument('name');
        $password = $this->option('password') ?: $this->secret('Password');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'email', 'unique:admins,email'],
                'name' => ['required', 'string'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        Admin::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Admin account created for {$email}.");

        return self::SUCCESS;
    }
}
