<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Tests\TestCase;

class AdminPageAccessTest extends TestCase
{
    public function test_guest_is_redirected_to_login_when_opening_admin_page(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_opening_admin_products_page(): void
    {
        $this->get('/admin/products')
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_open_admin_page(): void
    {
        $customer = User::where('email', 'customer@example.com')->firstOrFail();

        $this->actingAs($customer)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_customer_cannot_open_admin_products_page(): void
    {
        $customer = User::where('email', 'customer@example.com')->firstOrFail();

        $this->actingAs($customer)
            ->get('/admin/products')
            ->assertForbidden();
    }

    public function test_active_admin_can_open_admin_page(): void
    {
        $admin = User::where('email', 'admin@techshop.vn')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertViewIs('admin.dashboard');
    }

    public function test_active_admin_can_open_admin_products_page(): void
    {
        $admin = User::where('email', 'admin@techshop.vn')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/products')
            ->assertOk()
            ->assertViewIs('admin.products');
    }
}
