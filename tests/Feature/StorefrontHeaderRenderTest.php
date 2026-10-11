<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontHeaderRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_header_renders_account_control(): void
    {
        // Avoid full homepage DB/CMS dependencies — render the header partial directly.
        $html = view('website.partials.header', [
            'settings' => (object) [
                'store_name' => 'Test Store',
                'favicon_path' => null,
                'logo_path' => null,
                'currency_symbol' => 'Tk',
            ],
            'mainNav' => collect(),
            'categories' => collect(),
            'brands' => collect(),
        ])->render();

        $this->assertStringContainsString('gaget-action-label">Account', $html);
        $this->assertStringContainsString('openSignIn(', $html);
        $this->assertStringContainsString('Wishlist', $html);
        $this->assertStringContainsString('Cart', $html);
    }
}
