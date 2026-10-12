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
        return view('admin.create-aset-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Item::create([
            'name' => $request->name,
        ]);

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Item added successfully.');
    }

    public function edit(Item $item)
    {
        return view(
            'admin.item-edit',
            compact('item')
        );
    }

public function update(Request $request, Item $item)
{
    return response()->json([
        'TEST' => 'UPDATE TERPANGGIL',
        'item_id' => $item->id,
        'available' => $request->available,
    ]);
}

    public function destroy(Item $item)
    {
        $item->delete();

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Item deleted successfully.');
    }
}