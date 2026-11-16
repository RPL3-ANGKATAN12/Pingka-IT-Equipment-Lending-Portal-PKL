<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 10px; }
        h1 { margin: 0 0 6px; font-size: 20px; }
        .meta { margin-bottom: 16px; color: #555; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 6px; text-align: left; }
        th { background: #edf2f7; }
        .empty { text-align: center; }
    </style>
</head>
<body>
    <h1>IT Equipment Loan Report</h1>
    <div class="meta">
        Generated: {{ $generatedAt->format('d/m/Y H:i') }}<br>
        Filters:
        @if(!empty($filters['month']))
            {{ date('F', mktime(0, 0, 0, $filters['month'], 1)) }}
        @else
            All months
        @endif
        @if(!empty($filters['year']))
            {{ $filters['year'] }}
        @endif
        | Department: {{ $filters['department'] ?? 'All departments' }}
        | Borrower: {{ $filters['name'] ?? 'All borrowers' }}
        <br>
        Total records: {{ $loans->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Borrower</th>
                <th>Department</th>
                <th>Item</th>
                <th>Start Date</th>
                <th>Due Date</th>
                <th>Returned</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans as $index => $loan)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $loan->name }}</td>
                    <td>{{ $loan->departement ?: '-' }}</td>
                    <td>{{ $loan->item?->name ?? $loan->item ?? '-' }}</td>
                    <td>{{ $loan->start_date?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $loan->due_date?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $loan->returned_at?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $loan->returned_at ? 'Completed' : ([
                        'pending' => 'Pending',
                        'process' => 'Process',
                        'selesai' => 'Ready for Pickup',
                        'borrowed' => 'Borrowed',
                        'completed' => 'Completed',
                        'ditolak' => 'Rejected',
                    ][$loan->status] ?? ucfirst($loan->status)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">No loan records match these filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
