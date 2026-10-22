<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_item_with_multiple_serial_numbers_at_once(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('admin.items.store'), [
                'name' => 'Laptop',
                'available' => 4,
                'tracks_serial_numbers' => 1,
                'serial_numbers' => "LAPTOP-001\nLAPTOP-002\nLAPTOP-003\nLAPTOP-004",
            ])
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHas('success');

        $item = Item::where('name', 'Laptop')->firstOrFail();

        $this->assertSame(4, $item->target_stock);
        $this->assertSame(4, $item->available);
        $this->assertDatabaseCount('item_serial_numbers', 4);
        $this->assertDatabaseHas('item_serial_numbers', [
            'item_id' => $item->id,
            'serial_number' => 'LAPTOP-004',
            'status' => 'available',
        ]);
    }

    public function test_serial_numbers_are_optional_and_can_be_added_later(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('admin.items.store'), [
                'name' => 'Laptop',
                'available' => 4,
                'tracks_serial_numbers' => 1,
            ])
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('items', [
            'name' => 'Laptop',
            'available' => 0,
            'target_stock' => 4,
            'tracks_serial_numbers' => 1,
        ]);
        $this->assertDatabaseCount('item_serial_numbers', 0);
    }

    public function test_admin_can_register_only_some_of_the_target_serial_numbers_at_creation(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('admin.items.store'), [
                'name' => 'Laptop',
                'available' => 4,
                'tracks_serial_numbers' => 1,
                'serial_numbers' => "LAPTOP-001\n\nLAPTOP-002\n",
            ])
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('items', [
            'name' => 'Laptop',
            'available' => 2,
            'target_stock' => 4,
        ]);
        $this->assertDatabaseCount('item_serial_numbers', 2);
    }

    public function test_duplicate_or_excess_serial_numbers_are_rejected(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->from(route('admin.items.index'))
            ->post(route('admin.items.store'), [
                'name' => 'Duplicate laptop',
                'available' => 2,
                'tracks_serial_numbers' => 1,
                'serial_numbers' => "LAPTOP-001\nlaptop-001",
            ])
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHasErrors('serial_numbers');

        $this->actingAs($admin)
            ->from(route('admin.items.index'))
            ->post(route('admin.items.store'), [
                'name' => 'Excess laptop',
                'available' => 1,
                'tracks_serial_numbers' => 1,
                'serial_numbers' => "LAPTOP-001\nLAPTOP-002",
            ])
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHasErrors('serial_numbers');

        $this->assertDatabaseCount('items', 0);
        $this->assertDatabaseCount('item_serial_numbers', 0);
    }

    public function test_tracking_can_be_enabled_later_and_serials_added_from_edit_modal(): void
    {
        $admin = $this->createAdmin();
        $item = Item::create([
            'name' => 'Laptop',
            'available' => 4,
            'target_stock' => 4,
            'tracks_serial_numbers' => false,
            'status' => 'tersedia',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.items.index'))
            ->put(route('admin.items.update', $item), [
                'name' => 'Laptop',
                'available' => 4,
                'tracks_serial_numbers' => 1,
                'serial_numbers' => "LAPTOP-001\nLAPTOP-002",
            ])
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'tracks_serial_numbers' => 1,
            'target_stock' => 4,
            'available' => 2,
        ]);
        $this->assertDatabaseCount('item_serial_numbers', 2);
    }

    public function test_editing_tracked_item_appends_serials_without_replacing_existing_ones(): void
    {
        $admin = $this->createAdmin();
        $item = Item::create([
            'name' => 'Laptop',
            'available' => 1,
            'target_stock' => 3,
            'tracks_serial_numbers' => true,
            'status' => 'tersedia',
        ]);
        $item->serialNumbers()->create([
            'serial_number' => 'LAPTOP-001',
            'status' => 'available',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.items.index'))
            ->put(route('admin.items.update', $item), [
                'name' => 'Laptop',
                'available' => 3,
                'tracks_serial_numbers' => 1,
                'serial_numbers' => "LAPTOP-002\nLAPTOP-003",
            ])
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('item_serial_numbers', 3);
        $this->assertDatabaseHas('item_serial_numbers', [
            'item_id' => $item->id,
            'serial_number' => 'LAPTOP-001',
        ]);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'available' => 3,
        ]);
    }

    public function test_edit_rejects_full_serial_list_when_it_contains_already_registered_numbers(): void
    {
        $admin = $this->createAdmin();
        $item = Item::create([
            'name' => 'Laptop',
            'available' => 1,
            'target_stock' => 4,
            'tracks_serial_numbers' => true,
            'status' => 'tersedia',
        ]);
        $item->serialNumbers()->create([
            'serial_number' => 'LAPTOP-001',
            'status' => 'available',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.items.index'))
            ->put(route('admin.items.update', $item), [
                'name' => 'Laptop',
                'available' => 4,
                'tracks_serial_numbers' => 1,
                'serial_numbers' => "LAPTOP-001\nLAPTOP-002\nLAPTOP-003\nLAPTOP-004",
            ])
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHasErrors('serial_numbers');

        $this->assertDatabaseCount('item_serial_numbers', 1);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'target_stock' => 4,
            'available' => 1,
        ]);
    }

    public function test_edit_limits_new_serial_numbers_to_remaining_target_stock_slots(): void
    {
        $admin = $this->createAdmin();
        $item = Item::create([
            'name' => 'Laptop',
            'available' => 1,
            'target_stock' => 2,
            'tracks_serial_numbers' => true,
            'status' => 'tersedia',
        ]);
        $item->serialNumbers()->create([
            'serial_number' => 'LAPTOP-001',
            'status' => 'available',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.items.index'))
            ->put(route('admin.items.update', $item), [
                'name' => 'Laptop',
                'available' => 2,
                'tracks_serial_numbers' => 1,
                'serial_numbers' => 'laptop-001',
            ])
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHasErrors('serial_numbers');

        $this->assertDatabaseCount('item_serial_numbers', 1);

        $this->actingAs($admin)
            ->from(route('admin.items.index'))
            ->put(route('admin.items.update', $item), [
                'name' => 'Laptop',
                'available' => 2,
                'tracks_serial_numbers' => 1,
                'serial_numbers' => "LAPTOP-002\nLAPTOP-003",
            ])
            ->assertRedirect(route('admin.items.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('item_serial_numbers', 1);
        $this->assertDatabaseHas('item_serial_numbers', [
            'item_id' => $item->id,
            'serial_number' => 'LAPTOP-001',
            'status' => 'available',
        ]);
    }

    public function test_edit_modal_receives_existing_serial_count_for_the_progress_counter(): void
    {
        $admin = $this->createAdmin();
        $item = Item::create([
            'name' => 'Laptop',
            'available' => 1,
            'target_stock' => 7,
            'tracks_serial_numbers' => true,
            'status' => 'tersedia',
        ]);
        $item->serialNumbers()->create([
            'serial_number' => 'LAPTOP-001',
            'status' => 'available',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.items.index'))
            ->assertOk()
            ->assertSee('data-target-stock="7"', false)
            ->assertSee('data-registered-serial-count="1"', false)
            ->assertSee('Number(editButton.dataset.registeredSerialCount)', false);
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
