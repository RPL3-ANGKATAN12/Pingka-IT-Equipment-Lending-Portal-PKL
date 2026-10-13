
@extends('layouts.app')

@section('title', 'Borrowed Items')

@section('content')
<div class="dashboard">
    <div class="borrowed-header">
        <div>
            <h2 class="page-title">
                <i class="fas fa-box-open"></i>
                Borrowed Items
            </h2>
            <p class="borrowed-subtitle">
                Manage due dates and confirm returned items.
            </p>
        </div>
    </div>
    @if(session('success'))
        <div class="borrowed-alert borrowed-alert-success">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="borrowed-alert borrowed-alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

        <div class="borrowed-alert borrowed-alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="stats-grid">
        <div class="status-card">
            <h4>{{ $loans->count() }}</h4>
            <p>Total Records</p>
        </div>

        <div class="status-card proses">
            <h4>{{ $loans->whereNull('returned_at')->count() }}</h4>
            <p>Not Yet Returned</p>
        </div>

        <div class="status-card selesai">
            <h4>{{ $loans->whereNotNull('returned_at')->count() }}</h4>
            <p>Returned</p>
        </div>
    </div>

    <div class="dashboard-card">

        <div class="borrowed-toolbar">
            <input type="text" id="searchReturned" placeholder="Search by borrower, item, or department..." oninput="filterReturnedItems()" aria-label="Search borrowed items">

            <select id="filterReturned" onchange="filterReturnedItems()" aria-label="Filter return status">
                <option value="all">All Return Statuses</option>
                <option value="belum">Not Yet Returned</option>
                <option value="sudah">Returned</option>
            </select>
        </div>

        <div class="table-container">
            <table class="data-table borrowed-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Borrower</th>
                        <th>Department</th>
                        <th>Item Name</th>
                        <th>Hostname</th>
                        <th>Type</th>
                        <th>Serial Number</th>
                        <th>Borrow Date</th>
                        <th>Return Due Date</th>
                        <th>Return Status</th>
                    </tr>
                </thead>

                <tbody id="returnedTable">
                    @forelse($loans as $loan)
                        <tr
                            class="returned-row"
                            data-status="{{ $loan->returned_at ? 'sudah' : 'belum' }}"
                            data-search="{{ strtolower(($loan->name ?? '') . ' ' . ($loan->departement ?? '') . ' ' . ($loan->item->name ?? '') . ' ' . ($loan->hostname ?? '') . ' ' . ($loan->type ?? '') . ' ' . ($loan->sn ?? '')) }}"
                        >
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <strong>{{ $loan->name }}</strong>
                                <div class="borrower-email">
                                    {{ $loan->email ?? '-' }}
                                </div>
                            </td>

                            <td>{{ $loan->departement ?? '-' }}</td>

                            <td>{{ $loan->item->name ?? '-' }}</td>

                            <td>{{ $loan->hostname ?? '-' }}</td>

                            <td>{{ $loan->type ?? '-' }}</td>

                            <td>{{ $loan->sn ?? '-' }}</td>

                            <td>
                                {{ $loan->start_date?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td class="due-date-cell">
                                @if($loan->returned_at)
                                    <span class="saved-due-date">
                                        {{ $loan->due_date?->format('d/m/Y') ?? '-' }}
                                    </span>
                                @else
                                    <input type="date" class="return-due-date-input" id="due_date_{{ $loan->id }}" value="{{ $loan->due_date?->format('Y-m-d') }}" aria-label="Return due date for {{ $loan->name }}" required>
                                    <small class="due-date-warning" id="due_date_warning_{{ $loan->id }}" hidden>
                                        Please select a due date.
                                    </small>
                                @endif
                            </td>

                            <td class="return-status-cell">
                                @if($loan->returned_at)
                                    <span class="returned-status-badge">
                                        <i class="fas fa-check-circle"></i>
                                        Returned
                                    </span>

                                    <div class="returned-date">
                                        {{ $loan->returned_at->format('d/m/Y H:i') }}
                                    </div>
                                @else
                                    <button
                                        type="button"
                                        class="btn-confirm-return"
                                        onclick="openReturnModal(this)"
                                        data-action="{{ route('admin.returned-items.return', $loan->id) }}"
                                        data-loan-id="{{ $loan->id }}"
                                        data-borrower="{{ $loan->name }}"
                                        data-item="{{ $loan->item->name ?? '-' }}"
                                        data-hostname="{{ $loan->hostname ?? '-' }}"
                                    >
                                        <i class="fas fa-undo-alt"></i>
                                        Confirm Return
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="borrowed-empty">
                                <i class="fas fa-box-open"></i>
                                <p>No loan records found.</p>
                            </td>
                        </tr>
                    @endforelse

                    <tr id="noReturnedResults" style="display: none;">
                        <td colspan="10" class="borrowed-empty">
                            <i class="fas fa-search"></i>
                            <p>No records found.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="returnModal" class="return-modal-overlay" aria-hidden="true">
    <div class="return-modal" role="dialog" aria-modal="true" aria-labelledby="returnModalTitle" >
        <div class="return-modal-icon">
            <i class="fas fa-box-open"></i>
        </div>

        <h3 id="returnModalTitle">Confirm Item Return</h3>

        <p class="return-modal-description">
            Are you sure this item has been returned?
            Please verify the details below before continuing.
        </p>

        <div class="return-modal-details">
            <div class="return-detail-row">
                <span>Borrower</span>
                <strong id="returnBorrower">-</strong>
            </div>

            <div class="return-detail-row">
                <span>Item Name</span>
                <strong id="returnItem">-</strong>
            </div>

            <div class="return-detail-row">
                <span>Hostname</span>
                <strong id="returnHostname">-</strong>
            </div>

            <div class="return-detail-row">
                <span>Return Due Date</span>
                <strong id="returnDueDateDisplay">-</strong>
            </div>
        </div>

        <div class="return-modal-notice">
            <i class="fas fa-info-circle"></i>
            <span>
                The available stock will increase by one after confirmation.
            </span>
        </div>

        <form id="returnConfirmForm" method="POST" action="">
            @csrf

            <input type="hidden" name="returned" value="1">

            <input type="hidden" name="due_date" id="confirmReturnDueDate">

            <div class="return-modal-actions">
                <button type="button" class="return-btn-cancel" onclick="closeReturnModal()">
                    Cancel
                </button>

                <button type="submit" class="return-btn-confirm" id="confirmReturnSubmit">
                    <i class="fas fa-check"></i>
                    Yes, Confirm Return
                </button>
            </div>
        </form>
    </div>
</div>


<script>
function filterReturnedItems() {
    const search = document
        .getElementById('searchReturned')
        .value
        .toLowerCase()
        .trim();

    const status = document.getElementById('filterReturned').value;
    const rows = document.querySelectorAll('.returned-row');

    let visible = 0;

    rows.forEach(function (row) {
        const matchesSearch = row.dataset.search.includes(search);

        const matchesStatus =
            status === 'all' || row.dataset.status === status;

        const show = matchesSearch && matchesStatus;

        row.style.display = show ? '' : 'none';

        if (show) {
            visible++;
        }
    });

    document.getElementById('noReturnedResults').style.display =
        visible === 0 ? '' : 'none';
}

/* Open Confirm Return Modal */
function openReturnModal(button) {
    const loanId = button.dataset.loanId;
    const dateInput = document.getElementById('due_date_' + loanId);
    const warning = document.getElementById('due_date_warning_' + loanId);

    // Ensure due date has been selected
    if (!dateInput || !dateInput.value) {
        if (dateInput) {
            dateInput.classList.add('invalid');
            dateInput.focus();
        }

        if (warning) {
            warning.hidden = false;
        }

        return;
    }

    dateInput.classList.remove('invalid');

    if (warning) {
        warning.hidden = true;
    }

    const modal = document.getElementById('returnModal');
    const form = document.getElementById('returnConfirmForm');

    // Set the destination route
    form.action = button.dataset.action;

    // Pass the selected date to the form
    document.getElementById('confirmReturnDueDate').value =
        dateInput.value;

    // Display the selected date in the confirmation modal
    const dateParts = dateInput.value.split('-');
    document.getElementById('returnDueDateDisplay').textContent =
        dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0];

    // Display borrower and item details
    document.getElementById('returnBorrower').textContent =
        button.dataset.borrower || '-';

    document.getElementById('returnItem').textContent =
        button.dataset.item || '-';

    document.getElementById('returnHostname').textContent =
        button.dataset.hostname || '-';

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    modal.querySelector('.return-btn-cancel').focus();
}

function closeReturnModal() {
    const modal = document.getElementById('returnModal');

    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.querySelectorAll('.return-due-date-input').forEach(function (input) {
    input.addEventListener('change', function () {
        if (this.value) {
            this.classList.remove('invalid');

            const loanId = this.id.replace('due_date_', '');
            const warning = document.getElementById(
                'due_date_warning_' + loanId
            );

            if (warning) {
                warning.hidden = true;
            }
        }
    });
});

document.getElementById('returnModal').addEventListener(
    'click',
    function (event) {
        if (event.target === this) {
            closeReturnModal();
        }
    }
);

document.addEventListener('keydown', function (event) {
    const modal = document.getElementById('returnModal');

    if (event.key === 'Escape' && modal.classList.contains('active')) {
        closeReturnModal();
    }
});

document.getElementById('returnConfirmForm').addEventListener(
    'submit',
    function () {
        const button = document.getElementById('confirmReturnSubmit');

        button.disabled = true;
        button.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> Processing...';
    }
);
</script>
@endsection
