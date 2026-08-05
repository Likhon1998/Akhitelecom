<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        $shop = Shop::firstOrCreate(
            ['email' => 'admin@akhitelecom.com'],
            [
                'name' => 'Akhi Telecom',
                'phone' => '01700000000',
                'address' => 'Dhaka, Bangladesh',
                'is_active' => true,
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@akhitelecom.com'],
            [
                'shop_id' => $shop->id,
                'role' => 'admin',
                'name' => 'Admin',
                'password' => '12345678',
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['Admin']);
    }
}
