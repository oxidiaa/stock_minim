<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing users to have usernames if they exist by email
        $whc = User::where('email', 'whc@stockmin.com')->first();
        if ($whc) {
            $whc->update(['username' => 'whc']);
        } else {
            User::create([
                'name' => 'WHC User',
                'username' => 'whc',
                'email' => 'whc@stockmin.com',
                'password' => Hash::make('password'),
            ]);
        }

        $purchasing = User::where('email', 'purchasing@stockmin.com')->first();
        if ($purchasing) {
            $purchasing->update(['username' => 'purchasing']);
        } else {
            User::create([
                'name' => 'Purchasing User',
                'username' => 'purchasing',
                'email' => 'purchasing@stockmin.com',
                'password' => Hash::make('password'),
            ]);
        }

        $master = User::where('email', 'master@stockmin.com')->first();
        if ($master) {
            $master->update(['username' => 'master']);
        } else {
            User::create([
                'name' => 'Master User',
                'username' => 'master',
                'email' => 'master@stockmin.com',
                'password' => Hash::make('password'),
            ]);
        }
    }
}




