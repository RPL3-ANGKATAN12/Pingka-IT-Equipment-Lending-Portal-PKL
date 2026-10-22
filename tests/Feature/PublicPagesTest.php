<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loans;
use App\Models\Request as LoanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_route_returns_to_home_and_opens_the_request_modal(): void
    {
        $response = $this->get(route('loans.create'));

        $response->assertRedirect(route('guest.home', ['request' => '1']));
        $this->followingRedirects()->get(route('loans.create'))
            ->assertOk()
            ->assertSee("get('request') === '1'", false);
    }

    public function test_admin_item_edit_route_uses_the_existing_item_management_page(): void
    {
        $item = Item::create([
            'name' => 'Editable laptop',
            'available' => 1,
            'status' => 'tersedia',
        ]);

        $admin = User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.items.edit', $item))
            ->assertRedirect(route('admin.items.index'));
    }

    public function test_public_loan_listing_shows_name_and_department_but_hides_other_private_details(): void
    {
        $item = Item::create([
            'name' => 'Test laptop',
            'available' => 0,
            'status' => 'dipinjam',
        ]);

        Loans::create([
            'public_token' => str_repeat('a', 40),
            'name' => 'Sensitive Borrower',
            'email' => 'borrower@example.test',
            'departement' => 'Confidential Department',
            'item_id' => $item->id,
            'hostname' => 'PRIVATE-HOST',
            'type' => 'PRIVATE-MODEL',
            'sn' => 'PRIVATE-SERIAL',
            'start_date' => '2026-10-21',
            'others' => 'PRIVATE-NOTE',
            'status' => 'process',
        ]);

        LoanRequest::create([
            'name' => 'Pending Borrower',
            'email' => 'pending@example.test',
            'departement' => 'Private Team',
            'item_id' => $item->id,
            'start_date' => '2026-10-22',
            'others' => 'Private request note',
            'status' => 'pending',
        ]);

        $this->get(route('guest.loans'))
            ->assertOk()
            ->assertSee('Test laptop')
            ->assertSee('Sensitive Borrower')
            ->assertSee('Confidential Department')
            ->assertDontSee('borrower@example.test')
            ->assertDontSee('PRIVATE-HOST')
            ->assertDontSee('PRIVATE-MODEL')
            ->assertDontSee('PRIVATE-SERIAL')
            ->assertDontSee('PRIVATE-NOTE')
            ->assertSee('Pending Borrower')
            ->assertSee('Private Team')
            ->assertDontSee('pending@example.test')
            ->assertDontSee('Private request note');
    }

    public function test_tracking_page_uses_the_private_token_without_exposing_borrower_details(): void
    {
        $item = Item::create([
            'name' => 'Trackable laptop',
            'available' => 0,
            'status' => 'dipinjam',
        ]);

        $loan = Loans::create([
            'public_token' => str_repeat('b', 40),
            'name' => 'Private Tracking Borrower',
            'email' => 'tracking@example.test',
            'departement' => 'Private Team',
            'item_id' => $item->id,
            'start_date' => '2026-10-21',
            'status' => 'process',
        ]);

        $this->get(route('loans.track', $loan->public_token))
            ->assertOk()
            ->assertSee('Trackable laptop')
            ->assertDontSee('Private Tracking Borrower')
            ->assertDontSee('tracking@example.test');

        $this->get('/track/invalid-token')->assertNotFound();
    }

    public function test_public_items_api_returns_only_safe_inventory_fields(): void
    {
        Item::create([
            'name' => 'Public laptop',
            'available' => 2,
            'hostname' => 'PRIVATE-HOST',
            'type' => 'PRIVATE-MODEL',
            'sn' => 'PRIVATE-SERIAL',
            'status' => 'tersedia',
        ]);

        $this->getJson('/api/items')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'available', 'status'],
                ],
            ])
            ->assertDontSee('PRIVATE-HOST')
            ->assertDontSee('PRIVATE-MODEL')
            ->assertDontSee('PRIVATE-SERIAL');
    }
}
