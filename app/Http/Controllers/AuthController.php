<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index()
    {
        $items = Item::where('status', 'tersedia')
            ->orderBy('name')
            ->get();

        $loans = Loans::with('item')
            ->latest()
            ->limit(5)
            ->get();

        return view('guest.home', compact('items', 'loans'));
    }

    public function loginAdmin(Request $request)
    {
        $credentials = $request->validate([
            'nama' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt([
            'nama' => $credentials['nama'],
            'password' => $credentials['password'],
            'role' => 'admin',
        ])) {
            $request->session()->regenerate();

            return redirect()
                ->route('admin.dashboard')
                ->with('success', 'Succesfully logged in as admin.');
        }

        return back()
            ->withErrors(['nama' => 'Incorrect admin name or password.'])
            ->withInput($request->only('nama'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('guest.home')
            ->with('success', 'Succesfully logged out.');
    }
}
