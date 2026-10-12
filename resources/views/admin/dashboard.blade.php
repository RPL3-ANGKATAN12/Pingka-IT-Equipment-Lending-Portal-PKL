
@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="dashboard">

    {{-- HEADER --}}
    <div class="dashboard-heading">
        <div>
            <h2 class="page-title" style="margin-bottom: .5rem;">
                <i class="fas fa-chart-line"></i>
                Admin Dashboard
            </h2>
            <p class="dashboard-subtitle">
                Overview of IT equipment and lending activities.
            </p>
        </div>

        <a href="{{ route('admin.items.index') }}" class="dashboard-add-link">
            <i class="fas fa-laptop"></i>
            Manage Items
        </a>
    </div>

    {{-- MAIN STATISTICS --}}
    <section class="dashboard-section">
        <h3 class="section-heading">Overview</h3>

        <div class="dashboard-overview-grid">

            <a href="{{ route('admin.items.index') }}"
               class="overview-card">
                <div class="overview-icon">
                    <i class="fas fa-laptop"></i>
                </div>
                <div class="overview-info">
                    <span>Total Items</span>
                    <strong>{{ $stats['total_items'] ?? 0 }}</strong>
                    <small>Registered equipment</small>
                </div>
                <i class="fas fa-arrow-up-right-from-square overview-arrow"></i>
            </a>

            <a href="{{ route('admin.loans') }}"
               class="overview-card">
                <div class="overview-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="overview-info">
                    <span>Total Loans</span>
                    <strong>{{ $stats['total_loans'] ?? 0 }}</strong>
                    <small>Recorded loan transactions</small>
                </div>
                <i class="fas fa-arrow-up-right-from-square overview-arrow"></i>
            </a>

            <a href="{{ route('admin.requests') }}"
               class="overview-card">
                <div class="overview-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="overview-info">
                    <span>Request Pending</span>
                    <strong>{{ $stats['total_pending_request'] ?? 0 }}</strong>
                    <small>Waiting for admin approval</small>
                </div>
                <i class="fas fa-arrow-up-right-from-square overview-arrow"></i>
            </a>

            <a href="{{ route('admin.loans', ['status' => 'process']) }}"
               class="overview-card">
                <div class="overview-icon">
                    <i class="fas fa-hand-holding"></i>
                </div>
                <div class="overview-info">
                    <span>Currently Borrowed</span>
                    <strong>{{ $stats['total_borrowed'] ?? 0 }}</strong>
                    <small>Loans in progress</small>
                </div>
                <i class="fas fa-arrow-up-right-from-square overview-arrow"></i>
            </a>

        </div>
    </section>

    {{-- LOAN PROGRESS --}}
    <section class="dashboard-section">
        <div class="section-title-row">
            <div>
                <h3 class="section-heading">Loan Progress</h3>
                <p class="section-description">
                    Monitor the progress of approved loan transactions.
                </p>
            </div>

            <a href="{{ route('admin.loans') }}" class="dashboard-text-link">
                View all loans <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="progress-grid">

            <a href="{{ route('admin.loans', ['status' => 'process']) }}"
               class="progress-card">
                <div class="progress-card-top">
                    <span class="progress-icon process-icon">
                        <i class="fas fa-spinner"></i>
                    </span>
                    <span class="progress-label">In Progress</span>
                </div>

                <strong class="progress-number">
                    {{ $stats['total_process'] ?? 0 }}
                </strong>

                <span class="progress-caption">
                    Process
                </span>

                <div class="progress-card-footer">
                    View active loans
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="{{ route('admin.loans', ['status' => 'selesai']) }}"
               class="progress-card">
                <div class="progress-card-top">
                    <span class="progress-icon completed-icon">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    <span class="progress-label">Finished</span>
                </div>

                <strong class="progress-number">
                    {{ $stats['total_selesai'] ?? 0 }}
                </strong>

                <span class="progress-caption">
                    Completed
                </span>

                <div class="progress-card-footer">
                    View completed loans
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

        </div>
    </section>

    {{-- RECENT ACTIVITIES --}}
    <section class="dashboard-card recent-activity-card">
        <div class="activity-heading">
            <div>
                <h3>
                    <i class="fas fa-history"></i>
                    Recent Loan Activities
                </h3>
                <p>Latest recorded loan transactions.</p>
            </div>

            <a href="{{ route('admin.loans') }}"
               class="dashboard-text-link">
                View all <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        @forelse($recentLoans as $loan)
            <div class="activity-item">

                <div class="activity-avatar">
                    <i class="fas fa-user"></i>
                </div>

                <div class="activity-details">
                    <strong>{{ $loan->name }}</strong>

                    <span>
                        <i class="fas fa-laptop"></i>
                        {{ $loan->item->name ?? 'Item not found' }}
                    </span>

                    <small>
                        {{ $loan->departement ?? '-' }}
                        &bull;
                        {{ $loan->created_at?->format('d M Y, H:i') ?? '-' }}
                    </small>
                </div>

                <div class="activity-status">
                    @if($loan->status === 'process')
                        <span class="activity-badge badge-process">
                            <i class="fas fa-spinner"></i> Process
                        </span>
                    @elseif($loan->status === 'selesai')
                        <span class="activity-badge badge-completed">
                            <i class="fas fa-check-circle"></i> Completed
                        </span>
                    @elseif($loan->status === 'pending')
                        <span class="activity-badge badge-pending">
                            Pending
                        </span>
                    @elseif($loan->status === 'ditolak')
                        <span class="activity-badge badge-rejected">
                            Rejected
                        </span>
                    @else
                        <span class="activity-badge">
                            {{ ucfirst($loan->status) }}
                        </span>
                    @endif
                </div>

            </div>
        @empty
            <div class="activity-empty">
                <i class="fas fa-inbox"></i>
                <strong>No loan activities yet</strong>
                <p>Loan transactions will appear here when recorded.</p>
            </div>
        @endforelse
    </section>

</div>

<style>
.dashboard-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}

.dashboard-subtitle,
.section-description,
.activity-heading p {
    color: var(--text-light);
    font-size: .9rem;
    margin: 0;
}

.dashboard-add-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 16px;
    border-radius: 9px;
    background: var(--matcha-dark);
    color: white;
    text-decoration: none;
    font-size: .9rem;
    font-weight: 600;
}

.dashboard-add-link:hover {
    opacity: .9;
}

.dashboard-section {
    margin-bottom: 2rem;
}

.section-heading {
    color: var(--matcha-dark);
    font-size: 1.15rem;
    margin: 0 0 1rem;
}

.dashboard-overview-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.overview-card {
    min-width: 0;
    display: flex;
    align-items: flex-start;
    gap: 13px;
    position: relative;
    padding: 1.25rem;
    background: var(--white);
    border: 1px solid #e7ecdf;
    border-radius: 14px;
    box-shadow: var(--shadow-sm);
    text-decoration: none;
    color: inherit;
    transition: transform .2s, box-shadow .2s;
}

.overview-card:hover,
.progress-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.overview-icon,
.progress-icon {
    width: 43px;
    height: 43px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #edf3e4;
    color: var(--matcha-dark);
    font-size: 1.05rem;
}

.overview-info {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.overview-info > span {
    color: var(--text-light);
    font-size: .82rem;
}

.overview-info strong {
    color: var(--matcha-dark);
    font-size: 1.8rem;
    line-height: 1.2;
}

.overview-info small {
    color: var(--text-light);
    font-size: .74rem;
    line-height: 1.4;
}

.overview-arrow {
    position: absolute;
    top: 14px;
    right: 13px;
    color: #a0ad91;
    font-size: .75rem;
}

.section-title-row,
.activity-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.section-title-row .section-heading {
    margin-bottom: 5px;
}

.dashboard-text-link {
    color: var(--matcha-dark);
    text-decoration: none;
    font-size: .86rem;
    font-weight: 600;
    white-space: nowrap;
}

.dashboard-text-link:hover {
    text-decoration: underline;
}

.progress-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.progress-card {
    display: block;
    padding: 1.4rem;
    background: var(--white);
    border: 1px solid #e7ecdf;
    border-radius: 14px;
    box-shadow: var(--shadow-sm);
    color: inherit;
    text-decoration: none;
    transition: transform .2s, box-shadow .2s;
}

.progress-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.progress-label {
    color: var(--text-light);
    font-size: .82rem;
}

.completed-icon {
    background: #e5f1dc;
    color: #557d37;
}

.progress-number {
    display: block;
    margin-top: 1rem;
    font-size: 2.2rem;
    line-height: 1.2;
    color: var(--matcha-dark);
}

.progress-caption {
    display: block;
    margin-top: 4px;
    color: var(--text-light);
    font-size: .9rem;
}

.progress-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.3rem;
    padding-top: .9rem;
    border-top: 1px solid #edf0e9;
    color: var(--matcha-dark);
    font-size: .82rem;
    font-weight: 600;
}

.recent-activity-card {
    margin-bottom: 1.5rem;
}

.activity-heading {
    padding-bottom: 1rem;
    border-bottom: 1px solid #edf0e9;
}

.activity-heading h3 {
    margin-bottom: 6px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 1rem 0;
    border-bottom: 1px solid #edf0e9;
}

.activity-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.activity-avatar {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #edf3e4;
    color: var(--matcha-dark);
}

.activity-details {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.activity-details strong {
    color: var(--text-dark);
    font-size: .95rem;
}

.activity-details span {
    color: var(--text-light);
    font-size: .85rem;
}

.activity-details small {
    color: var(--text-light);
    font-size: .78rem;
}

.activity-status {
    flex-shrink: 0;
}

.activity-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    border-radius: 20px;
    background: #f0f0f0;
    color: #555;
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
}

.badge-process {
    background: #edf3e4;
    color: var(--matcha-dark);
}

.badge-completed {
    background: #e5f1dc;
    color: #557d37;
}

.badge-pending {
    background: #f2f0e5;
    color: #796b37;
}

.badge-rejected {
    background: #f5e9e5;
    color: #895848;
}

.activity-empty {
    padding: 2.5rem 1rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 9px;
    color: var(--text-light);
}

.activity-empty > i {
    font-size: 2rem;
    color: #a4b594;
}

.activity-empty strong {
    color: var(--matcha-dark);
}

.activity-empty p {
    margin: 0;
    font-size: .85rem;
}

@media (max-width: 1100px) {
    .dashboard-overview-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    .dashboard {
        padding: 1.2rem 4%;
    }

    .dashboard-heading {
        align-items: flex-start;
    }

    .dashboard-overview-grid,
    .progress-grid {
        grid-template-columns: 1fr;
    }

    .overview-card {
        padding: 1rem;
    }

    .activity-item {
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .activity-details {
        flex-basis: calc(100% - 60px);
    }

    .activity-status {
        margin-left: 55px;
    }
}
</style>
@endsection