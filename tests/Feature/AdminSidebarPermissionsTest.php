<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminSidebarPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_guard_user_passes_sidebar_permission_checks(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $shop = Shop::create([
            'name' => 'Sidebar Shop',
            'email' => 'sidebar@example.com',
            'phone' => '01700000111',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'email' => 'sidebar-admin@example.com',
        ]);
        $admin->syncRoles(['Admin']);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin, 'admin');

        $admin->refresh();
        $this->assertTrue($admin->hasRole('Admin'));
        $this->assertTrue($admin->checkPermissionTo('view dashboard', 'web'));
        $this->assertFalse($admin->checkPermissionTo('view dashboard', 'admin'));

        Auth::shouldUse('admin');
        $this->assertSame('admin', Auth::getDefaultDriver());
        $this->assertSame('admin', config('auth.defaults.guard'));
        $this->assertTrue(Auth::user()->checkPermissionTo('view dashboard'), 'checkPermissionTo without guard');
        $this->assertTrue(Auth::user()->checkPermissionTo('view dashboard', 'web'), 'checkPermissionTo web');
        $this->assertTrue(Auth::user()->can('view dashboard'), 'Gate can()');
        $this->assertTrue(Auth::user()->can('manage inventory'));
        $this->assertTrue(Auth::user()->can('manage website'));
        $this->assertTrue(Auth::user()->can('view sales ledger'));

        $html = view('layouts.navigation')->render();
        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringContainsString('Inventory', $html);
        $this->assertStringContainsString('CMS', $html);
        $this->assertStringContainsString('Sales Ledger', $html);
    }
}
