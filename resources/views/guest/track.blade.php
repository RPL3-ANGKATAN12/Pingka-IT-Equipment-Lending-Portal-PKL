@extends('layouts.app')

@section('title', 'Loan Tracking')

@section('content')
<div class="dashboard">
    <h2 class="page-title">Loan Tracking</h2>

    <div class="dashboard-card">
        <p><strong>Item:</strong> {{ $loan->item->name ?? 'Item unavailable' }}</p>
        <p><strong>Status:</strong> {{ ucfirst($loan->status) }}</p>
        <p><strong>Start date:</strong> {{ $loan->start_date?->format('d/m/Y') ?? '-' }}</p>
        <p><strong>Due date:</strong> {{ $loan->due_date?->format('d/m/Y') ?? '-' }}</p>
        @if($loan->returned_at)
            <p><strong>Returned:</strong> {{ $loan->returned_at->format('d/m/Y H:i') }}</p>
        @endif
    </div>
</div>
@endsection
