@extends('layouts.app')

@section('title', 'Loan Reports')

@section('content')
<div class="dashboard">
    <div class="report-page-heading">
        <div>
            <h2 class="page-title"><i class="fas fa-file-alt"></i> Loan Reports</h2>
            <p>Review and export loan records using the filters below.</p>
        </div>
    </div>

    <section class="report-filter-card" aria-labelledby="reportFilterTitle">
        <div class="report-filter-heading">
            <div class="report-section-icon"><i class="fas fa-sliders-h"></i></div>
            <div>
                <h3 id="reportFilterTitle">Filter report</h3>
                <p>Month and year are based on the loan start date. Name search matches part of a name.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.reports') }}" class="report-filter-form">
            <div class="report-filter-grid">
                <label class="report-field">
                    <span>Month</span>
                    <select name="month">
                        <option value="">All months</option>
                        @foreach(range(1, 12) as $month)
                            <option value="{{ $month }}" @selected(($filters['month'] ?? '') == $month)>
                                {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="report-field">
                    <span>Year</span>
                    <input
                        type="number"
                        name="year"
                        min="2000"
                        max="2100"
                        value="{{ $filters['year'] ?? '' }}"
                        placeholder="Any year"
                    >
                </label>

                <label class="report-field">
                    <span>Department</span>
                    <select name="department">
                        <option value="">All departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department }}" @selected(($filters['department'] ?? '') === $department)>
                                {{ $department }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="report-field">
                    <span>Borrower name</span>
                    <input
                        type="search"
                        name="name"
                        value="{{ $filters['name'] ?? '' }}"
                        maxlength="100"
                        placeholder="Search borrower"
                    >
                </label>
            </div>

            <div class="report-filter-actions">
                <a href="{{ route('admin.reports') }}" class="btn-outline">
                    <i class="fas fa-undo"></i> Reset filters
                </a>
                <div class="report-primary-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-filter"></i> Apply filters
                    </button>
                    <button
                        type="submit"
                        name="_review"
                        value="1"
                        class="btn-primary"
                        aria-haspopup="dialog"
                        aria-controls="reportReviewModal"
                    >
                        <i class="fas fa-file-pdf"></i> Review PDF
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="report-results-card" aria-labelledby="reportResultsTitle">
        <div class="report-results-heading">
            <div>
                <h3 id="reportResultsTitle">Report results</h3>
                <p>Records matching the selected filters</p>
            </div>
            <span class="report-result-count">
                <strong>{{ $loans->count() }}</strong>
                {{ \Illuminate\Support\Str::plural('record', $loans->count()) }}
            </span>
        </div>
        <div class="table-container">
            <table class="data-table">
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
                            <td colspan="8" class="report-empty-row">
                                <i class="fas fa-search"></i>
                                <strong>No matching records</strong>
                                <span>Try changing or clearing some filters.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div
    id="reportReviewModal"
    class="report-review-overlay"
    aria-hidden="true"
    onclick="if (event.target === this) closeReportReview()"
>
    <section
        class="report-review-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="reportReviewTitle"
        aria-describedby="reportReviewDescription"
    >
        <header class="report-review-header">
            <div class="report-review-title-icon"><i class="fas fa-file-pdf"></i></div>
            <div>
                <h2 id="reportReviewTitle">Review PDF before download</h2>
                <p id="reportReviewDescription">Check the selected filters and records included in your report.</p>
            </div>
            <button type="button" class="report-review-close" onclick="closeReportReview()" aria-label="Close review">
                <i class="fas fa-times"></i>
            </button>
        </header>

        <div class="report-review-body">
            <div class="report-review-summary">
                <div class="report-summary-icon"><i class="fas fa-list-ol"></i></div>
                <div>
                    <strong>{{ $loans->count() }} {{ \Illuminate\Support\Str::plural('record', $loans->count()) }}</strong>
                    <span>will be included in this PDF</span>
                </div>
            </div>

            <h3 class="report-review-subheading">Selected filters</h3>
            <div class="report-filter-summary">
                @if(!empty($filters['month']))
                    <span><i class="fas fa-calendar-alt"></i> {{ date('F', mktime(0, 0, 0, $filters['month'], 1)) }}</span>
                @endif
                @if(!empty($filters['year']))
                    <span><i class="fas fa-calendar"></i> {{ $filters['year'] }}</span>
                @endif
                @if(!empty($filters['department']))
                    <span><i class="fas fa-building"></i> {{ $filters['department'] }}</span>
                @endif
                @if(!empty($filters['name']))
                    <span><i class="fas fa-user"></i> Name contains “{{ $filters['name'] }}”</span>
                @endif
                @if(empty($filters['month']) && empty($filters['year']) && empty($filters['department']) && empty($filters['name']))
                    <span><i class="fas fa-globe"></i> All loan records</span>
                @endif
            </div>

            <h3 class="report-review-subheading">Record preview</h3>
            <div class="report-review-table-wrap">
                <table class="report-review-table">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Department</th>
                            <th>Item</th>
                            <th>Start date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loans->take(5) as $loan)
                            <tr>
                                <td>{{ $loan->name }}</td>
                                <td>{{ $loan->departement ?: '-' }}</td>
                                <td>{{ $loan->item?->name ?? $loan->item ?? '-' }}</td>
                                <td>{{ $loan->start_date?->format('d/m/Y') ?? '-' }}</td>
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
                                <td colspan="5" class="report-review-empty">There are no records to include. Adjust your filters before downloading.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($loans->count() > 5)
                <p class="report-preview-note">Showing the first 5 records. The PDF will include all {{ $loans->count() }} matching records.</p>
            @endif
        </div>

        <footer class="report-review-actions">
            <button type="button" class="btn-outline" onclick="closeReportReview()">Go back</button>
            @if($loans->isNotEmpty())
                <a href="{{ route('admin.reports.pdf', $filters) }}" class="btn-primary">
                    <i class="fas fa-download"></i> Confirm and download PDF
                </a>
            @else
                <button type="button" class="btn-primary" disabled>
                    <i class="fas fa-download"></i> No records to download
                </button>
            @endif
        </footer>
    </section>
</div>
@endsection

@push('scripts')
<script>
    let reportReviewTrigger = null;

    function openReportReview() {
        const modal = document.getElementById('reportReviewModal');
        reportReviewTrigger = document.activeElement;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('report-review-open');
        modal.querySelector('.report-review-close').focus();
    }

    function closeReportReview() {
        const modal = document.getElementById('reportReviewModal');
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('report-review-open');
        if (reportReviewTrigger) {
            reportReviewTrigger.focus();
        }
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && document.getElementById('reportReviewModal').classList.contains('is-open')) {
            closeReportReview();
        }
    });

    if (@json(request()->boolean('_review'))) {
        openReportReview();
    }
</script>
@endpush
