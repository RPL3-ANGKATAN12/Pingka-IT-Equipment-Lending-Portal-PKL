<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loans;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_item_with_completed_loan_history(): void
    {
        $admin = $this->createAdmin();
        $item = Item::create([
            'name' => 'Returned laptop',
            'available' => 0,
            'target_stock' => 1,
            'tracks_serial_numbers' => true,
            'status' => 'tersedia',
        ]);
        $item->serialNumbers()->create([
            'serial_number' => 'LAPTOP-001',
            'status' => 'borrowed',
        ]);
        $loan = Loans::create([
            'public_token' => str_repeat('a', 40),
            'name' => 'Borrower',
            'email' => 'borrower@example.test',
            'departement' => 'IT',
            'item_id' => $item->id,
            'sn' => 'LAPTOP-001',
            'start_date' => '2026-11-03',
            'status' => 'completed',
            'returned_at' => '2026-11-10 12:00:00',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.items.destroy', $item))
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHas('success', 'Item deleted successfully.');

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
        $this->assertDatabaseMissing('item_serial_numbers', [
            'item_id' => $item->id,
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'item_id' => null,
            'status' => 'completed',
        ]);
    }

    public function test_admin_cannot_delete_item_with_active_loan(): void
    {
        $admin = $this->createAdmin();
        $item = Item::create([
            'name' => 'Borrowed laptop',
            'available' => 0,
            'target_stock' => 1,
            'status' => 'dipinjam',
        ]);
        $loan = Loans::create([
            'public_token' => str_repeat('b', 40),
            'name' => 'Borrower',
            'email' => 'borrower@example.test',
            'departement' => 'IT',
            'item_id' => $item->id,
            'start_date' => '2026-11-03',
            'status' => 'borrowed',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.items.destroy', $item))
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHas(
                'error',
                'This item cannot be deleted while it has active loans or requests.'
            );

        $this->assertDatabaseHas('items', ['id' => $item->id]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'item_id' => $item->id,
        ]);
    }

    private function createAdmin(): User
    {
        return User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);
    }
}
