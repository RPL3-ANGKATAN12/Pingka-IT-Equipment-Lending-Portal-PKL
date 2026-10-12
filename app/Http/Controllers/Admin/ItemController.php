<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::withCount('loans')
            ->orderBy('name')
            ->get();

        return view('admin.item', compact('items'));
    }

    public function create()
    {
        return redirect()->route('admin.items.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'available' => 'required|integer|min:0',
            'hostname' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:100',
            'sn' => 'nullable|string|max:100',
            'status' => 'nullable|in:tersedia,dipinjam,maintenance',
            'others' => 'nullable|string',
        ]);

        $validated['status'] = $validated['status'] ?? 'tersedia';

        Item::create($validated);

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Item added successfully.');
    }

    public function edit(Item $item)
    {
        return view('admin.item-edit', compact('item'));
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'available' => 'required|integer|min:0',
            'hostname' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:100',
            'sn' => 'nullable|string|max:100',
            'status' => 'nullable|in:tersedia,dipinjam,maintenance',
            'others' => 'nullable|string',
        ]);

        $item->update($validated);

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        if ($item->loans()->exists()) {
            return back()->with(
                'error',
                'Item cannot be deleted because it already has loan data.'
            );
        }

        $item->delete();

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Item deleted successfully.');
    }
}