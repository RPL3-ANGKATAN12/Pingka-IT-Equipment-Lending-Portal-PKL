<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loans;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $loans = $this->loansForReport($filters)->get();
        $departments = Loans::query()
            ->whereNotNull('departement')
            ->distinct()
            ->orderBy('departement')
            ->pluck('departement');

        return view('admin.reports', compact('loans', 'departments', 'filters'));
    }

    public function pdf(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $loans = $this->loansForReport($filters)->get();

        return Pdf::loadView('admin.reports.pdf', [
            'loans' => $loans,
            'filters' => $filters,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->download('loan-report-'.now()->format('Ymd-His').'.pdf');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'department' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function loansForReport(array $filters): Builder
    {
        return Loans::query()
            ->with('item')
            ->when(
                isset($filters['month']),
                fn (Builder $query) => $query->whereMonth('start_date', $filters['month'])
            )
            ->when(
                isset($filters['year']),
                fn (Builder $query) => $query->whereYear('start_date', $filters['year'])
            )
            ->when(
                isset($filters['department']) && $filters['department'] !== '',
                fn (Builder $query) => $query->where('departement', $filters['department'])
            )
            ->when(
                isset($filters['name']) && $filters['name'] !== '',
                fn (Builder $query) => $query->where('name', 'like', '%'.$filters['name'].'%')
            )
            ->orderByDesc('start_date')
            ->orderByDesc('id');
    }
}
