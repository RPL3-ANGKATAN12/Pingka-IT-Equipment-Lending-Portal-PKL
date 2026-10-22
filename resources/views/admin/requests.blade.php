
@extends('layouts.app')

@section('title', 'Loan Request')

@section('content')

<div class="dashboard">
    <h2 class="page-title">
        <i class="fas fa-clock"></i>
        Loan Request
    </h2>

    <div class="stats-grid">
        <div
            class="status-card"
            onclick="filterStatus('approved')"
            style="cursor:pointer;"
        >
            <h4>{{ $stats['Approved'] ?? 0 }}</h4>
            <p><i class="fas fa-clock"></i> Approved</p>
        </div>

        <div
            class="status-card proses"
            onclick="filterStatus('rejected')"
            style="cursor:pointer;"
        >
            <h4>{{ $stats['Rejected'] ?? 0 }}</h4>
            <p><i class="fas fa-spinner"></i> Rejected</p>
        </div>
    </div>

    <div class="dashboard-card">
        <h3>
            <i class="fas fa-hourglass-half"></i>
            Waiting for Approval
            <span class="badge badge-warning" style="margin-left:10px;">
                {{ $pendingRequests->count() }}
            </span>
        </h3>

        @if($pendingRequests->count())
            <div class="bulk-request-toolbar">
                <strong id="selectedRequestCount" class="bulk-request-selection-count">
                    No requests selected
                </strong>
                <div class="bulk-request-actions">
                    <button
                        type="button"
                        class="bulk-request-button bulk-request-approve"
                        disabled
                        onclick="openBulkConfirmModal('approve')"
                    >
                        <i class="fas fa-check-double"></i>
                        Approve Selected
                    </button>
                    <button
                        type="button"
                        class="bulk-request-button bulk-request-reject"
                        disabled
                        onclick="openBulkConfirmModal('reject')"
                    >
                        <i class="fas fa-times"></i>
                        Reject Selected
                    </button>
                </div>
            </div>

            <div class="table-container">
                <form
                    id="bulkRequestForm"
                    action="{{ route('admin.requests.bulk') }}"
                    method="POST"
                >
                    @csrf
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input
                                    type="checkbox"
                                    id="selectAllRequests"
                                    aria-label="Select all pending requests"
                                    onchange="toggleAllRequests(this)"
                                >
                            </th>
                            <th>No</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Item</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($pendingRequests as $no => $loan)
                            <tr>
                                <td>
                                    <input
                                        type="checkbox"
                                        class="pending-request-checkbox"
                                        name="request_ids[]"
                                        value="{{ $loan->id }}"
                                        aria-label="Select request from {{ $loan->name }}"
                                        onchange="updateBulkRequestSelection()"
                                    >
                                </td>
                                <td>{{ $no + 1 }}</td>
                                <td><strong>{{ $loan->name }}</strong></td>
                                <td>{{ $loan->departement }}</td>
                                <td>{{ $loan->item->name ?? '-' }}</td>
                                <td>
                                    {{ $loan->start_date?->format('d/m/Y') ?? '-' }}
                                </td>

                                <td>
                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                        <button
                                            type="button"
                                            class="btn-table btn-success"
                                            onclick="openConfirmModal(
                                                'approve',
                                                @js(route('admin.requests.approve', $loan)),
                                                @js($loan->name),
                                                @js($loan->item->name ?? '-')
                                            )"
                                        >
                                            <i class="fas fa-check"></i>
                                            Approve
                                        </button>

                                        <button
                                            type="button"
                                            class="btn-table btn-danger"
                                            onclick="openConfirmModal(
                                                'reject',
                                                @js(route('admin.requests.reject', $loan)),
                                                @js($loan->name),
                                                @js($loan->item->name ?? '-')
                                            )"
                                        >
                                            <i class="fas fa-times"></i>
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </form>
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <p>There are no requests waiting for approval.</p>
            </div>
        @endif
    </div>

    {{-- Request History --}}
    <div class="dashboard-card">
        <div
            class="filter-bar"
            style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:1.5rem;"
        >
            <select
                id="statusFilter"
                onchange="applyFilters()"
                style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:white;"
            >
                <option value="all">All Status</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>

            <input
                type="text"
                id="searchFilter"
                placeholder="Search name/department/hostname..."
                oninput="applyFilters()"
                autocomplete="off"
                style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; min-width:260px;"
            >

            <button
                type="button"
                class="btn-outline"
                onclick="resetFilters()"
            >
                <i class="fas fa-undo"></i>
                Reset
            </button>
        </div>

        <h3>
            <i class="fas fa-history"></i>
            Request History
            <span
                class="badge"
                style="background:var(--gray-light); margin-left:10px;"
            >
                {{ $historyRequests->count() }}
            </span>
        </h3>

        @if($historyRequests->count())
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Item</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody id="requestHistoryTable">
                        @foreach($historyRequests as $loan)
                            <tr
                                class="request-history-row"
                                data-status="{{ $loan->status }}"
                                data-search="{{ strtolower(($loan->name ?? '') . ' ' . ($loan->departement ?? '') . ' ' . ($loan->item->name ?? '')) }}"
                            >
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $loan->name }}</td>
                                <td>{{ $loan->departement }}</td>
                                <td>{{ $loan->item->name ?? '-' }}</td>

                                <td>
                                    @if($loan->status === 'approved')
                                        <span class="badge badge-success">
                                            Approved
                                        </span>
                                    @elseif($loan->status === 'rejected')
                                        <span class="badge badge-danger">
                                            Rejected
                                        </span>
                                    @else
                                        <span class="badge badge-warning">
                                            {{ ucfirst($loan->status) }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $loan->created_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                            </tr>
                        @endforeach

                        <tr id="noRequestResults" style="display:none;">
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-search"></i>
                                <p>No matching request records found.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No request history yet.</p>
            </div>
        @endif
    </div>
</div>

{{-- Confirm Modal --}}
<div id="confirmModal" class="confirm-modal">
    <div
        class="confirm-modal-overlay"
        onclick="closeConfirmModal()"
    ></div>

    <div class="confirm-modal-box">
        <button
            type="button"
            class="confirm-modal-close"
            onclick="closeConfirmModal()"
        >
            &times;
        </button>

        <div id="confirmModalIcon" class="confirm-modal-icon">
            <i class="fas fa-question"></i>
        </div>

        <h3 id="confirmModalTitle">
            Confirm Action
        </h3>

        <p id="confirmModalText">
            Are you sure?
        </p>
        <div id="bulkConfirmRequestCount" class="bulk-confirm-count" hidden>
            <i class="fas fa-users"></i>
            <span></span>
        </div>

        <form id="confirmModalForm" method="POST">
            @csrf

            <div class="confirm-modal-actions">
                <button
                    type="button"
                    class="confirm-modal-cancel"
                    onclick="closeConfirmModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="confirmModalSubmit"
                    class="confirm-modal-submit"
                >
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* Filter Request History */
function applyFilters() {
    const status = document.getElementById('statusFilter').value;

    const search = document
        .getElementById('searchFilter')
        .value
        .toLowerCase()
        .trim();

    const rows = document.querySelectorAll('.request-history-row');
    const noResults = document.getElementById('noRequestResults');

    let visibleCount = 0;

    rows.forEach(function (row) {
        const rowStatus = row.dataset.status || '';
        const rowSearch = row.dataset.search || '';

        const statusMatch =
            status === 'all' || rowStatus === status;

        const searchMatch =
            rowSearch.includes(search);

        const show =
            statusMatch && searchMatch;

        row.style.display = show ? '' : 'none';

        if (show) {
            visibleCount++;
        }
    });

    if (noResults) {
        noResults.style.display =
            rows.length > 0 && visibleCount === 0
                ? ''
                : 'none';
    }
}

/* Click statistics cards to filter */
function filterStatus(status) {
    document.getElementById('statusFilter').value = status;

    applyFilters();

    const historyTable = document.getElementById('requestHistoryTable');

    if (historyTable) {
        historyTable.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

/* Reset filter */
function resetFilters() {
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('searchFilter').value = '';

    applyFilters();
}

function updateBulkRequestSelection() {
    const checkboxes = Array.from(
        document.querySelectorAll('.pending-request-checkbox')
    );
    const selectedCount = checkboxes.filter(function(checkbox) {
        return checkbox.checked;
    }).length;

    document.getElementById('selectedRequestCount').textContent =
        selectedCount === 0
            ? 'No requests selected'
            : selectedCount + (selectedCount === 1 ? ' request selected' : ' requests selected');

    document.querySelectorAll(
        '.bulk-request-toolbar button[type="button"]'
    ).forEach(function(button) {
        button.disabled = selectedCount === 0;
    });

    const selectAll = document.getElementById('selectAllRequests');
    selectAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
    selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
}

function toggleAllRequests(selectAll) {
    document.querySelectorAll('.pending-request-checkbox').forEach(function(checkbox) {
        checkbox.checked = selectAll.checked;
    });

    updateBulkRequestSelection();
}

/* Confirmation modal */
function openConfirmModal(type, action, name, item) {
    const modal = document.getElementById('confirmModal');
    const form = document.getElementById('confirmModalForm');
    const icon = document.getElementById('confirmModalIcon');
    const title = document.getElementById('confirmModalTitle');
    const text = document.getElementById('confirmModalText');
    const submit = document.getElementById('confirmModalSubmit');

    clearBulkConfirmInputs();
    document.getElementById('bulkConfirmRequestCount').hidden = true;
    form.action = action;

    icon.className = 'confirm-modal-icon';
    submit.className = 'confirm-modal-submit';

    if (type === 'approve') {
        icon.classList.add('approve');
        icon.innerHTML = '<i class="fas fa-check"></i>';

        title.textContent = 'Approve Request?';

        text.innerHTML =
            'Are you sure you want to approve the loan request from ' +
            '<strong>' + escapeHtml(name) + '</strong> for ' +
            '<strong>' + escapeHtml(item) + '</strong>?';

        submit.textContent = 'Approve';
    } else {
        icon.classList.add('reject');
        icon.innerHTML = '<i class="fas fa-times"></i>';

        title.textContent = 'Reject Request?';

        text.innerHTML =
            'Are you sure you want to reject the loan request from ' +
            '<strong>' + escapeHtml(name) + '</strong> for ' +
            '<strong>' + escapeHtml(item) + '</strong>?';

        submit.classList.add('reject');
        submit.textContent = 'Reject';
    }

    modal.classList.add('active');
    document.body.classList.add('modal-open');
}

function openBulkConfirmModal(action) {
    const selectedIds = Array.from(
        document.querySelectorAll('.pending-request-checkbox:checked')
    ).map(function(checkbox) {
        return checkbox.value;
    });

    if (selectedIds.length === 0) {
        return;
    }

    const form = document.getElementById('confirmModalForm');
    const modal = document.getElementById('confirmModal');
    const icon = document.getElementById('confirmModalIcon');
    const title = document.getElementById('confirmModalTitle');
    const text = document.getElementById('confirmModalText');
    const count = document.getElementById('bulkConfirmRequestCount');
    const submit = document.getElementById('confirmModalSubmit');

    clearBulkConfirmInputs();
    form.action = @js(route('admin.requests.bulk'));

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    actionInput.className = 'bulk-confirm-input';
    form.appendChild(actionInput);

    selectedIds.forEach(function(id) {
        const requestInput = document.createElement('input');
        requestInput.type = 'hidden';
        requestInput.name = 'request_ids[]';
        requestInput.value = id;
        requestInput.className = 'bulk-confirm-input';
        form.appendChild(requestInput);
    });

    const isApproval = action === 'approve';
    icon.className = 'confirm-modal-icon ' + (isApproval ? 'approve' : 'reject');
    icon.innerHTML = isApproval
        ? '<i class="fas fa-check-double"></i>'
        : '<i class="fas fa-times"></i>';
    title.textContent = isApproval
        ? 'Approve selected requests?'
        : 'Reject selected requests?';
    text.textContent = isApproval
        ? 'Requests with available items will be approved and inventory will be reserved.'
        : 'The selected pending requests will be rejected.';
    count.querySelector('span').textContent =
        selectedIds.length + (selectedIds.length === 1 ? ' request selected' : ' requests selected');
    count.hidden = false;
    submit.className = 'confirm-modal-submit' + (isApproval ? '' : ' reject');
    submit.textContent = isApproval ? 'Yes, approve' : 'Yes, reject';

    modal.classList.add('active');
    document.body.classList.add('modal-open');
}

function clearBulkConfirmInputs() {
    document.querySelectorAll('.bulk-confirm-input').forEach(function(input) {
        input.remove();
    });
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
    document.body.classList.remove('modal-open');
}

/* Escape HTML in modal text */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';

    return div.innerHTML;
}

/* Close modal with Escape */
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeConfirmModal();
    }
});

/* Initialize filter */
applyFilters();
updateBulkRequestSelection();
</script>

@endsection
