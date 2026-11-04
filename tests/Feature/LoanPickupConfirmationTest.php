<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loans;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanPickupConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pickup_confirmation_changes_ready_loan_to_borrowed(): void
    {
        $admin = User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);
        $item = Item::create([
            'name' => 'Ready laptop',
            'available' => 0,
            'status' => 'dipinjam',
        ]);
        $loan = Loans::create([
            'public_token' => str_repeat('g', 40),
            'name' => 'Borrower',
            'email' => 'borrower@example.test',
            'departement' => 'IT',
            'item_id' => $item->id,
            'start_date' => '2026-11-03',
            'due_date' => '2026-11-10',
            'status' => 'selesai',
        ]);

        $this->get(route('guest.loans'))
            ->assertOk()
            ->assertSee(
                'class="badge badge-warning">Please pick up your item</span>',
                false
            )
            ->assertSee('N/A');
        $this->get(route('guest.loans'))
            ->assertOk()
            ->assertSee('<th>Name</th>', false)
            ->assertDontSee('<th>Department</th>', false);
        $this->get(route('loans.track', $loan->public_token))
            ->assertOk()
            ->assertSee('Perangkat siap diambil')
            ->assertSee('Silakan ambil perangkat yang telah disiapkan.');

        $this->actingAs($admin)
            ->get(route('admin.returned-items.index'))
            ->assertOk()
            ->assertSee('Confirm Pickup')
            ->assertSee('Waiting for Pickup')
            ->assertSee('class="borrower-department"', false)
            ->assertSee('IT')
            ->assertSee('Not required')
            ->assertSee('Not specified')
            ->assertSee('Not tracked');

        $this->get(route('admin.loans'))
            ->assertOk()
            ->assertSee('class="borrower-department"', false)
            ->assertSee('IT')
            ->assertSee('Not required')
            ->assertSee('Not specified')
            ->assertSee('Not tracked');

        $this->post(route('admin.returned-items.pickup', $loan))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'borrowed',
        ]);
        $this->assertNotNull($loan->fresh()->picked_up_at);

        $this->get(route('guest.loans'))
            ->assertOk()
            ->assertSee('class="badge badge-success">Completed</span>', false);
        $this->get(route('admin.loans'))
            ->assertOk()
            ->assertSee('class="badge badge-success">Completed</span>', false);
        $this->get(route('loans.track', $loan->public_token))
            ->assertOk()
            ->assertSee('Peminjaman berlangsung')
            ->assertSee('Pengambilan perangkat telah dikonfirmasi admin.')
            ->assertSee('Waktu diambil')
            ->assertSee('grid-template-columns: repeat(4, minmax(0, 1fr))', false);

        $this->actingAs($admin)
            ->get(route('admin.returned-items.index'))
            ->assertOk()
            ->assertSee('Confirm Return')
            ->assertDontSee('onclick="openPickupModal(this)"', false);
    }

    public function test_admin_cannot_confirm_return_before_pickup_is_confirmed(): void
    {
        $admin = User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);
        $item = Item::create([
            'name' => 'Waiting laptop',
            'available' => 0,
            'status' => 'dipinjam',
        ]);
        $loan = Loans::create([
            'public_token' => str_repeat('h', 40),
            'name' => 'Borrower',
            'email' => 'borrower@example.test',
            'departement' => 'IT',
            'item_id' => $item->id,
            'start_date' => '2026-11-03',
            'due_date' => '2026-11-10',
            'status' => 'selesai',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.returned-items.return', $loan), [
                'returned' => '1',
                'due_date' => '2026-11-10',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('returned');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'selesai',
            'returned_at' => null,
        ]);
    }
}
