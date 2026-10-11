<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DualGuardSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_storefront_logins_can_coexist_in_one_browser_session(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $shop = Shop::create([
            'name' => 'Dual Guard Shop',
            'email' => 'dual-guard@example.com',
            'phone' => '01700000099',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'email' => 'staff@example.com',
        ]);
        $admin->syncRoles(['Admin']);

        $customerUser = User::factory()->create([
            'shop_id' => $shop->id,
            'email' => 'customer@example.com',
        ]);
        // SQLite test schema still enums staff roles; storefront identity is the Customer Spatie role.
        $customerUser->syncRoles(['Customer']);
        Customer::create([
            'shop_id' => $shop->id,
            'user_id' => $customerUser->id,
            'name' => $customerUser->name,
            'phone' => '01711112222',
            'email' => $customerUser->email,
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');

        $this->postJson('/account/login', [
            'email' => $customerUser->email,
            'password' => 'password',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($customerUser, 'web');

        // Website account stays customer; staff panel stays staff (same browser session).
        $this->get('/account')->assertOk();
        $this->get('/refresh-session')->assertOk()->assertJsonStructure(['csrf_token']);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($customerUser, 'web');

        $this->post('/account/logout')->assertRedirect(route('home'));
        $this->assertGuest('admin');
        $this->assertGuest('web');
    }

    public function test_customer_cannot_authenticate_on_admin_guard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $shop = Shop::create([
            'name' => 'Customer Block Shop',
            'email' => 'cust-block@example.com',
            'phone' => '01700000088',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $customerUser = User::factory()->create([
            'shop_id' => $shop->id,
            'email' => 'shopper@example.com',
        ]);
        $customerUser->syncRoles(['Customer']);

        $this->from('/admin/login')->post('/admin/login', [
            'email' => $customerUser->email,
            'password' => 'password',
        ])->assertRedirect('/admin/login')->assertSessionHasErrors('email');

        $this->assertGuest('admin');
        $this->assertGuest('web');
    }

    public function test_staff_cannot_authenticate_on_storefront_guard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $shop = Shop::create([
            'name' => 'Staff Block Shop',
            'email' => 'staff-block@example.com',
            'phone' => '01700000077',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'email' => 'pos-staff@example.com',
        ]);
        $admin->syncRoles(['Admin']);

        $this->postJson('/account/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        $this->assertGuest('web');
        $this->assertGuest('admin');
    }
}
