<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loans;
use App\Models\Request as LoanRequest;

class HomeController extends Controller
{
    public function index()
    {
        $pendingRequests = LoanRequest::with('item')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $loans = Loans::with('item')
            ->latest()
            ->get();

        $loanData = collect();

        foreach ($pendingRequests as $request) {
            $loanData->push((object) [
                'id' => 'request-' . $request->id,
                'name' => $request->name,
                'departement' => $request->departement,
                'item_id' => $request->item_id,
                'item' => $request->item,
                'hostname' => null,
                'type' => null,
                'sn' => null,
                'start_date' => $request->start_date,
                'others' => $request->others,
                'status' => 'pending',
                'source' => 'request',
                'source_id' => $request->id,
                'created_at' => $request->created_at,
            ]);
        }

        foreach ($loans as $loan) {
            $loanData->push((object) [
                'id' => 'loan-' . $loan->id,
                'name' => $loan->name,
                'departement' => $loan->departement,
                'item_id' => $loan->item_id,
                'item' => $loan->item,
                'hostname' => $loan->hostname ?? null,
                'type' => $loan->type ?? null,
                'sn' => $loan->sn ?? null,
                'start_date' => $loan->start_date,
                'others' => $loan->others,
                'status' => $loan->status,
                'source' => 'loan',
                'source_id' => $loan->id,
                'created_at' => $loan->created_at,
            ]);
        }

        $loanData = $loanData
            ->sortByDesc(function ($data) {
                return $data->created_at;
            })
            ->values();

        $items = Item::orderBy('name')->get();

        return view('guest.home', [
            'loans' => $loanData,
            'items' => $items,
        ]);
    }
}