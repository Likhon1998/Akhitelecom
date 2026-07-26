<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        $shop = Shop::firstOrCreate(
            ['email' => 'admin@nexapos.com'],
            [
                'name' => 'Nexa POS Master Shop',
                'phone' => '01700000000',
                'address' => 'Dhaka, Bangladesh',
                'is_active' => true,
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@nexapos.com'],
            [
                'shop_id' => $shop->id,
                'role' => 'admin',
                'name' => 'Store Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['Shop Owner']);

        $this->call([
            WebsiteSeeder::class,
        ]);
    }
}
