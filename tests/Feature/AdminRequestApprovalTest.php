<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemSerialNumber;
use App\Models\Loans;
use App\Models\Request as LoanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_request_creates_a_loan_and_decrements_stock_once(): void
    {
        $admin = User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);

        $item = Item::create([
            'name' => 'Available laptop',
            'available' => 1,
            'status' => 'tersedia',
        ]);

        $request = LoanRequest::create([
            'name' => 'Requester',
            'email' => 'requester@example.test',
            'departement' => 'IT',
            'item_id' => $item->id,
            'start_date' => '2026-10-21',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.approve', $request))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'available' => 0,
            'status' => 'dipinjam',
        ]);
        $this->assertDatabaseCount('loans', 1);

        $this->post(route('admin.requests.approve', $request))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'available' => 0,
        ]);
        $this->assertDatabaseCount('loans', 1);
    }

    public function test_maintenance_items_cannot_be_approved(): void
    {
        $admin = User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);

        $item = Item::create([
            'name' => 'Maintenance laptop',
            'available' => 1,
            'status' => 'maintenance',
        ]);

        $request = LoanRequest::create([
            'name' => 'Requester',
            'email' => 'requester@example.test',
            'departement' => 'IT',
            'item_id' => $item->id,
            'start_date' => '2026-10-21',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.approve', $request))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'available' => 1,
            'status' => 'maintenance',
        ]);
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_editing_target_stock_keeps_current_loans_reserved(): void
    {
        $admin = User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);

        $item = Item::create([
            'name' => 'Reserved laptop',
            'available' => 1,
            'target_stock' => 2,
            'status' => 'tersedia',
        ]);

        Loans::create([
            'public_token' => str_repeat('c', 40),
            'name' => 'Borrower',
            'email' => 'borrower@example.test',
            'departement' => 'IT',
            'item_id' => $item->id,
            'start_date' => '2026-10-21',
            'status' => 'process',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.items.update', $item), [
                'name' => 'Reserved laptop',
                'available' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'available' => 1,
            'target_stock' => 2,
        ]);
    }

    public function test_a_returned_serial_number_can_be_assigned_to_a_new_loan(): void
    {
        $admin = User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);

        $item = Item::create([
            'name' => 'Tracked laptop',
            'available' => 1,
            'target_stock' => 1,
            'tracks_serial_numbers' => true,
            'status' => 'tersedia',
        ]);

        ItemSerialNumber::create([
            'item_id' => $item->id,
            'serial_number' => 'SERIAL-001',
            'status' => 'available',
        ]);

        Loans::create([
            'public_token' => str_repeat('d', 40),
            'name' => 'Previous Borrower',
            'email' => 'previous@example.test',
            'departement' => 'IT',
            'item_id' => $item->id,
            'sn' => 'SERIAL-001',
            'start_date' => '2026-10-01',
            'status' => 'selesai',
            'returned_at' => now(),
        ]);

        $loan = Loans::create([
            'public_token' => str_repeat('e', 40),
            'name' => 'Current Borrower',
            'email' => 'current@example.test',
            'departement' => 'IT',
            'item_id' => $item->id,
            'start_date' => '2026-10-21',
            'status' => 'process',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.loans.update', $loan), [
                'type' => 'Laptop',
                'sn' => 'SERIAL-001',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'sn' => 'SERIAL-001',
            'status' => 'selesai',
        ]);
        $this->assertDatabaseHas('item_serial_numbers', [
            'item_id' => $item->id,
            'serial_number' => 'SERIAL-001',
            'status' => 'borrowed',
        ]);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'available' => 0,
        ]);
    }

    public function test_admin_can_bulk_approve_selected_requests_and_skip_requests_without_stock(): void
    {
        $admin = User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);

        $item = Item::create([
            'name' => 'Bulk laptop',
            'available' => 2,
            'status' => 'tersedia',
        ]);

        $requests = collect(range(1, 3))->map(fn ($number) => LoanRequest::create([
            'name' => "Requester {$number}",
            'email' => "requester{$number}@example.test",
            'departement' => 'IT',
            'item_id' => $item->id,
            'start_date' => '2026-10-21',
            'status' => 'pending',
        ]));

        $this->actingAs($admin)
            ->post(route('admin.requests.bulk'), [
                'action' => 'approve',
                'request_ids' => $requests->pluck('id')->all(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn ($message) => str_contains($message, '2 request(s) approved')
                && str_contains($message, '1 skipped'));

        $this->assertDatabaseCount('loans', 2);
        $this->assertSame(2, LoanRequest::where('status', 'approved')->count());
        $this->assertSame(1, LoanRequest::where('status', 'pending')->count());
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'available' => 0,
            'status' => 'dipinjam',
        ]);
    }

    public function test_admin_can_bulk_reject_selected_pending_requests(): void
    {
        $admin = User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);

        $item = Item::create([
            'name' => 'Bulk laptop',
            'available' => 2,
            'status' => 'tersedia',
        ]);

        $requests = collect(range(1, 2))->map(fn ($number) => LoanRequest::create([
            'name' => "Requester {$number}",
            'email' => "requester{$number}@example.test",
            'departement' => 'IT',
            'item_id' => $item->id,
            'start_date' => '2026-10-21',
            'status' => 'pending',
        ]));

        $this->actingAs($admin)
            ->post(route('admin.requests.bulk'), [
                'action' => 'reject',
                'request_ids' => $requests->pluck('id')->all(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn ($message) => str_contains($message, '2 request(s) rejected'));

        $this->assertSame(2, LoanRequest::where('status', 'rejected')->count());
        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'available' => 2,
        ]);
    }

    public function test_bulk_request_action_requires_selected_ids_and_valid_action(): void
    {
        $admin = User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.requests'))
            ->post(route('admin.requests.bulk'), [
                'action' => 'delete',
                'request_ids' => [],
            ])
            ->assertRedirect(route('admin.requests'))
            ->assertSessionHasErrors(['action', 'request_ids']);
    }

    public function test_admin_request_page_has_bulk_confirmation_modal_and_selection_controls(): void
    {
        $admin = User::create([
            'nama' => 'Portal Admin',
            'email' => 'admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);

        $item = Item::create([
            'name' => 'Bulk laptop',
            'available' => 2,
            'status' => 'tersedia',
        ]);

        LoanRequest::create([
            'name' => 'Requester',
            'email' => 'requester@example.test',
            'departement' => 'IT',
            'item_id' => $item->id,
            'start_date' => '2026-10-21',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.requests'))
            ->assertOk()
            ->assertSee('Approve Selected')
            ->assertSee('Reject Selected')
            ->assertSee('openBulkConfirmModal')
            ->assertSee('bulkConfirmRequestCount')
            ->assertSee('Select all pending requests');
    }
}
