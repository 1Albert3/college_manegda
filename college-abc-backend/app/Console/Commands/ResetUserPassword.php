<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password {email?}';
    protected $description = 'Reset user password to default (Abc12345!)';

    public function handle()
    {
        $email = $this->argument('email');
        $defaultPassword = 'Abc12345!';

        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->password = Hash::make($defaultPassword);
                $user->save();
                $this->info("Password reset for {$email}");
            } else {
                $this->error("User not found: {$email}");
            }
        } else {
            // Reset all users
            $count = User::count();
            User::query()->update(['password' => Hash::make($defaultPassword)]);
            $this->info("Password reset for {$count} users to: {$defaultPassword}");
        }

        return 0;
    }
}
