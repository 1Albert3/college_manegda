<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordsSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            'admin@college-abc.bf' => 'password123',
            'directeur@college-abc.bf' => 'password123', 
            'secretaire@college-abc.bf' => 'password123',
            'comptable@college-abc.bf' => 'password123',
            'enseignant@college-abc.bf' => 'password123',
        ];

        foreach ($users as $email => $password) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->password = Hash::make($password);
                $user->save();
                echo "✅ Mot de passe mis à jour pour: $email\n";
            } else {
                echo "❌ Utilisateur non trouvé: $email\n";
            }
        }

        echo "\n🎯 IDENTIFIANTS DE TEST:\n";
        foreach ($users as $email => $password) {
            echo "- $email / $password\n";
        }
    }
}