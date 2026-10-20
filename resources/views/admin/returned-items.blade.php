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

    @if(session('error'))
        <div class="borrowed-alert borrowed-alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if(session('success'))
        <div class="borrowed-alert borrowed-alert-success">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="borrowed-alert borrowed-alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Statistics --}}
    <div class="stats-grid">
        <div
            class="status-card returned-filter-card"
            data-filter="belum"
            role="button"
            tabindex="0"
            aria-pressed="false"
            onclick="filterStatus('belum')"
            onkeydown="if(event.key === 'Enter' || event.key === ' '){event.preventDefault();filterStatus('belum')}"
        >
            <h4>{{ $loans->whereNull('returned_at')->count() }}</h4>
            <p>
                <i class="fas fa-clock"></i>
                Not Yet Returned
            </p>
        </div>

        <div
            class="status-card returned-filter-card"
            data-filter="sudah"
            role="button"
            tabindex="0"
            aria-pressed="false"
            onclick="filterStatus('sudah')"
            onkeydown="if(event.key === 'Enter' || event.key === ' '){event.preventDefault();filterStatus('sudah')}"
        >
            <h4>{{ $loans->whereNotNull('returned_at')->count() }}</h4>
            <p>
                <i class="fas fa-check-circle"></i>
                Returned
            </p>
        </div>
    </div>

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
                <option value="belum">Not Yet Returned</option>
                <option value="sudah">Returned</option>
            </select>

            <input
                type="text"
                id="searchFilter"
                placeholder="Search borrower/device/serial number..."
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

            <button
                type="button"
                class="btn-print-returned"
                onclick="printReturnedItems()"
            >
                <i class="fas fa-print"></i>
                Print Data
            </button>
        </div>

        <div class="table-container">
            <table class="data-table borrowed-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Borrower</th>
                        <th>Department</th>
                        <th>Item Name</th>
                        <th>Device Info</th>
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
                            data-search="{{ strtolower(($loan->name ?? '') . ' ' . ($loan->email ?? '') . ' ' . ($loan->departement ?? '') . ' ' . ($loan->item->name ?? '') . ' ' . ($loan->hostname ?? '') . ' ' . ($loan->type ?? '') . ' ' . ($loan->sn ?? '')) }}"
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

                            <td>
                                @if(filled($loan->sn))
                                    <div class="device-info-line">
                                        <strong>{{ $loan->sn }}</strong>
                                    </div>
                                @endif

                                @if(filled($loan->hostname))
                                    <div class="device-info-line">
                                        {{ $loan->hostname }}
                                    </div>
                                @endif

                                @if(filled($loan->type))
                                    <div class="device-info-line">
                                        {{ $loan->type }}
                                    </div>
                                @endif

                                @if(!filled($loan->hostname) && !filled($loan->type) && !filled($loan->sn))
                                    <span>-</span>
                                @endif
                            </td>

                            <td>
                                {{ $loan->start_date?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td class="due-date-cell">
                                @if($loan->returned_at)
                                    <span class="saved-due-date">
                                        {{ $loan->due_date?->format('d/m/Y') ?? '-' }}
                                    </span>
                                @else
                                    <input
                                        type="date"
                                        class="return-due-date-input"
                                        id="due_date_{{ $loan->id }}"
                                        value="{{ $loan->due_date?->format('Y-m-d') }}"
                                        aria-label="Return due date for {{ $loan->name }}"
                                        required
                                    >

                                    <small
                                        class="due-date-warning"
                                        id="due_date_warning_{{ $loan->id }}"
                                        hidden
                                    >
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
                                        data-borrower="{{ $loan->name ?? '-' }}"
                                        data-item="{{ $loan->item->name ?? '-' }}"
                                        data-hostname="{{ $loan->hostname ?? '' }}"
                                        data-type="{{ $loan->type ?? '' }}"
                                        data-sn="{{ $loan->sn ?? '' }}"
                                    >
                                        <i class="fas fa-undo-alt"></i>
                                        Confirm Return
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="borrowed-empty">
                                <i class="fas fa-box-open"></i>
                                <p>No loan records found.</p>
                            </td>
                        </tr>
                    @endforelse

                    <tr id="noReturnedResults" style="display:none;">
                        <td colspan="8" class="borrowed-empty">
                            <i class="fas fa-search"></i>
                            <p>No records found.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Confirm Return Modal --}}
<div
    id="returnModal"
    class="return-modal-overlay"
    aria-hidden="true"
>
    <div
        class="return-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="returnModalTitle"
    >
        <div class="return-modal-icon">
            <i class="fas fa-box-open"></i>
        </div>

        <h3 id="returnModalTitle">Confirm Item Return</h3>

        <p class="return-modal-description">
            Please review the available device information and return due date
            before confirming that the item has been returned.
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

            <div class="return-detail-row" id="returnHostnameRow" hidden>
                <span>Hostname</span>
                <strong id="returnHostname"></strong>
            </div>

            <div class="return-detail-row" id="returnTypeRow" hidden>
                <span>Type/Model</span>
                <strong id="returnType"></strong>
            </div>

            <div class="return-detail-row" id="returnSNRow" hidden>
                <span>Serial Number (SN)</span>
                <strong id="returnSN"></strong>
            </div>

            <div class="return-detail-row">
                <span>Return Due Date</span>
                <strong id="returnDueDateDisplay">-</strong>
            </div>
        </div>

        <div class="return-modal-notice">
            <i class="fas fa-info-circle"></i>
            <span>
                Please verify the device details before confirming the return.
                The available stock will be updated after confirmation.
            </span>
        </div>

        <form id="returnConfirmForm" method="POST" action="">
            @csrf

            <input type="hidden" name="returned" value="1">

            <input
                type="hidden"
                name="due_date"
                id="confirmReturnDueDate"
            >

            <div class="return-modal-actions">
                <button
                    type="button"
                    class="return-btn-cancel"
                    onclick="closeReturnModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="return-btn-confirm"
                    id="confirmReturnSubmit"
                >
                    <i class="fas fa-check"></i>
                    Yes, Confirm Return
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .device-info-line {
        margin-bottom: 4px;
        overflow-wrap: anywhere;
    }

    .device-info-line:last-child {
        margin-bottom: 0;
    }

    .return-detail-row {
        display: flex;
        flex-direction: column;
        gap: 5px;
        padding: 10px 0;
    }

    .return-detail-row + .return-detail-row {
        border-top: 1px solid rgba(100, 120, 70, 0.10);
    }

    .return-detail-row span {
        font-size: 12px;
        color: #74816e;
    }

    .return-detail-row strong {
        overflow-wrap: anywhere;
        color: #354432;
        font-size: 14px;
    }

    .return-modal-description {
        line-height: 1.7;
    }

    .return-modal-details {
        max-height: 300px;
        overflow-y: auto;
    }

    .return-detail-row[hidden] {
        display: none !important;
    }

    @media print {
        .borrowed-header,
        .stats-grid,
        .filter-bar,
        .return-modal-overlay,
        .return-status-cell button {
            display: none !important;
        }

        .due-date-cell input {
            display: none !important;
        }

        .due-date-cell::after {
            content: attr(data-print-date);
        }
    }
</style>
@endpush

@push('scripts')
<script>
function applyFilters() {
    const status = document.getElementById('statusFilter').value;

    const search = document
        .getElementById('searchFilter')
        .value
        .toLowerCase()
        .trim();

    const rows = document.querySelectorAll('.returned-row');
    const noResults = document.getElementById('noReturnedResults');
    let visibleCount = 0;

    rows.forEach(function (row) {
        const rowStatus = row.dataset.status || '';
        const rowSearch = row.dataset.search || '';

        const statusMatch = status === 'all' || rowStatus === status;
        const searchMatch = rowSearch.includes(search);
        const show = statusMatch && searchMatch;

        row.style.display = show ? '' : 'none';

        if (show) {
            visibleCount++;
        }
    });

    if (noResults) {
        noResults.style.display =
            rows.length > 0 && visibleCount === 0 ? '' : 'none';
    }

    document.querySelectorAll('.returned-filter-card').forEach(
        function (card) {
            const active =
                card.dataset.filter === status && status !== 'all';

            card.classList.toggle('active', active);
            card.setAttribute('aria-pressed', active ? 'true' : 'false');
        }
    );
}

function filterStatus(status) {
    document.getElementById('statusFilter').value = status;
    applyFilters();
}

function resetFilters() {
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('searchFilter').value = '';
    applyFilters();
}

function printReturnedItems() {
    document.querySelectorAll('.due-date-cell').forEach(function (cell) {
        const input = cell.querySelector('input[type="date"]');

        if (input) {
            cell.dataset.printDate = input.value
                ? input.value.split('-').reverse().join('/')
                : '-';
        }
    });

    window.print();
}

function setOptionalModalRow(rowId, valueId, value) {
    const row = document.getElementById(rowId);
    const element = document.getElementById(valueId);
    const cleanValue = (value || '').trim();

    if (cleanValue !== '') {
        element.textContent = cleanValue;
        row.hidden = false;
    } else {
        element.textContent = '';
        row.hidden = true;
    }
}

function openReturnModal(button) {
    const loanId = button.dataset.loanId;
    const dateInput = document.getElementById('due_date_' + loanId);
    const warning = document.getElementById('due_date_warning_' + loanId);

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

    form.action = button.dataset.action || '';

    document.getElementById('confirmReturnDueDate').value =
        dateInput.value;

    const dateParts = dateInput.value.split('-');

    document.getElementById('returnDueDateDisplay').textContent =
        dateParts.length === 3
            ? dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0]
            : '-';

    document.getElementById('returnBorrower').textContent =
        button.dataset.borrower || '-';

    document.getElementById('returnItem').textContent =
        button.dataset.item || '-';

    setOptionalModalRow(
        'returnHostnameRow',
        'returnHostname',
        button.dataset.hostname
    );

    setOptionalModalRow(
        'returnTypeRow',
        'returnType',
        button.dataset.type
    );

    setOptionalModalRow(
        'returnSNRow',
        'returnSN',
        button.dataset.sn
    );

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    const cancelButton = modal.querySelector('.return-btn-cancel');

    if (cancelButton) {
        cancelButton.focus();
    }
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
    function (event) {
        const button = document.getElementById('confirmReturnSubmit');

        if (!this.action || button.disabled) {
            event.preventDefault();
            return;
        }

        button.disabled = true;
        button.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> Processing...';
    }
);

window.addEventListener('afterprint', function () {
    document.querySelectorAll('.due-date-cell').forEach(function (cell) {
        delete cell.dataset.printDate;
    });
});

const urlStatus = new URLSearchParams(window.location.search).get('status');

if (urlStatus && ['belum', 'sudah'].includes(urlStatus)) {
    document.getElementById('statusFilter').value = urlStatus;
}

applyFilters();
</script>
@endpush