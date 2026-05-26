<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Generar socorristas del 1 al 5 con sus contraseñas correlativas
        foreach (range(1, 5) as $index) {
            User::create([
                'name' => "Socorrista Test $index",
                'email' => "socorrista" . $index . "@train-rescue.com",
                'password' => Hash::make("Socorrista" . $index), 
                'rol' => 'socorrista',               
                'club_id' => 1,                       
                'email_verified_at' => Carbon::now(), 
            ]);
        }
    }
}
