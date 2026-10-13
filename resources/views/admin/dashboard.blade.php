
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
@endsection