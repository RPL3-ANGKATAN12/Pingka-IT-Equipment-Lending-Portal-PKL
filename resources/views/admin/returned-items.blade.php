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
                Confirm item pickup, manage due dates, and record returned items.
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

            <select
                id="yearFilter"
                onchange="applyFilters()"
                aria-label="Filter by year"
                style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:white;"
            >
                <option value="all">All Years</option>
            </select>

            <select
                id="monthFilter"
                onchange="applyFilters()"
                aria-label="Filter by month"
                style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:white;"
            >
                <option value="all">All Months</option>
                <option value="01">January</option>
                <option value="02">February</option>
                <option value="03">March</option>
                <option value="04">April</option>
                <option value="05">May</option>
                <option value="06">June</option>
                <option value="07">July</option>
                <option value="08">August</option>
                <option value="09">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
            </select>

            <select
                id="fromMonthFilter"
                onchange="applyFilters()"
                aria-label="Filter from month"
                style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:white;"
            >
                <option value="">From month</option>
                <option value="01">January</option>
                <option value="02">February</option>
                <option value="03">March</option>
                <option value="04">April</option>
                <option value="05">May</option>
                <option value="06">June</option>
                <option value="07">July</option>
                <option value="08">August</option>
                <option value="09">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
            </select>

            <select
                id="toMonthFilter"
                onchange="applyFilters()"
                aria-label="Filter through month"
                style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:white;"
            >
                <option value="">To month</option>
                <option value="01">January</option>
                <option value="02">February</option>
                <option value="03">March</option>
                <option value="04">April</option>
                <option value="05">May</option>
                <option value="06">June</option>
                <option value="07">July</option>
                <option value="08">August</option>
                <option value="09">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
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
                            data-filter-date="{{ ($loan->returned_at ?? $loan->picked_up_at ?? $loan->start_date)?->format('Y-m-d') ?? '' }}"
                            data-search="{{ strtolower(($loan->name ?? '') . ' ' . ($loan->email ?? '') . ' ' . ($loan->departement ?? '') . ' ' . ($loan->item->name ?? '') . ' ' . ($loan->hostname ?? '') . ' ' . ($loan->type ?? '') . ' ' . ($loan->sn ?? '')) }}"
                        >
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <div class="borrower-heading">
                                    <strong>{{ $loan->name }}</strong>
                                    <span class="borrower-department">
                                        {{ $loan->departement ?? '-' }}
                                    </span>
                                </div>
                                <div class="borrower-email">
                                    {{ $loan->email ?? '-' }}
                                </div>
                            </td>

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
                                @if($loan->status === 'selesai' && !$loan->returned_at)
                                    <button
                                        type="button"
                                        class="btn-confirm-pickup"
                                        onclick="openPickupModal(this)"
                                        data-action="{{ route('admin.returned-items.pickup', $loan->id) }}"
                                        data-borrower="{{ $loan->name ?? '-' }}"
                                        data-item="{{ $loan->item->name ?? '-' }}"
                                    >
                                        <i class="fas fa-hand-holding"></i>
                                        Confirm Pickup
                                    </button>
                                    <small class="pickup-planned-date">
                                        Planned: {{ $loan->start_date?->format('d/m/Y') ?? '-' }}
                                    </small>
                                @else
                                    {{ $loan->picked_up_at?->format('d/m/Y H:i') ?? $loan->start_date?->format('d/m/Y') ?? '-' }}
                                @endif
                            </td>

                            <td class="due-date-cell">
                                @if($loan->returned_at)
                                    <span class="saved-due-date">
                                        {{ $loan->due_date?->format('d/m/Y') ?? '-' }}
                                    </span>
                                @else
                                    <div class="return-date-input-group">
                                        <input
                                            type="text"
                                            class="return-due-date-input"
                                            id="due_date_{{ $loan->id }}"
                                            value="{{ $loan->due_date?->format('d/m/Y') }}"
                                            placeholder="dd/mm/yyyy"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            aria-label="Return due date for {{ $loan->name }}"
                                            required
                                        >
                                        <button
                                            type="button"
                                            class="return-date-picker-button"
                                            onclick="openDatePicker('due_date_picker_{{ $loan->id }}')"
                                            aria-label="Choose return due date"
                                        >
                                            <i class="fas fa-calendar-alt"></i>
                                        </button>
                                        <input
                                            type="date"
                                            class="native-date-picker"
                                            id="due_date_picker_{{ $loan->id }}"
                                            value="{{ $loan->due_date?->format('Y-m-d') }}"
                                            aria-label="Choose return due date"
                                            tabindex="-1"
                                        >
                                    </div>

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
                                @elseif($loan->status === 'selesai')
                                    <span class="pickup-pending-badge">
                                        <i class="fas fa-clock"></i>
                                        Waiting for Pickup
                                    </span>
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
                            <td colspan="7" class="borrowed-empty">
                                <i class="fas fa-box-open"></i>
                                <p>No loan records found.</p>
                            </td>
                        </tr>
                    @endforelse

                    <tr id="noReturnedResults" style="display:none;">
                        <td colspan="7" class="borrowed-empty">
                            <i class="fas fa-search"></i>
                            <p>No records found.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Confirm Pickup Modal --}}
<div
    id="pickupModal"
    class="return-modal-overlay"
    aria-hidden="true"
>
    <div
        class="return-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="pickupModalTitle"
    >
        <div class="return-modal-icon">
            <i class="fas fa-hand-holding"></i>
        </div>

        <h3 id="pickupModalTitle">Confirm Item Pickup</h3>

        <p class="return-modal-description">
            Confirm that this borrower has picked up the prepared item.
        </p>

        <div class="return-modal-details">
            <div class="return-detail-row">
                <span>Borrower</span>
                <strong id="pickupBorrower">-</strong>
            </div>
            <div class="return-detail-row">
                <span>Item Name</span>
                <strong id="pickupItem">-</strong>
            </div>
        </div>

        <form id="pickupConfirmForm" method="POST" action="">
            @csrf
            <div class="return-modal-actions">
                <button
                    type="button"
                    class="return-btn-cancel"
                    onclick="closePickupModal()"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="return-btn-confirm"
                    id="confirmPickupSubmit"
                >
                    <i class="fas fa-check"></i>
                    Yes, Confirm Pickup
                </button>
            </div>
        </form>
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
    .borrowed-table .borrower-heading {
        display: flex;
        align-items: baseline;
        justify-content: flex-start;
        gap: 8px;
        min-width: 190px;
    }

    .borrowed-table .borrower-heading strong {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .borrowed-table .borrower-department {
        flex: 0 0 auto;
        color: #647a3a;
        font-size: 12px;
        font-weight: 600;
        text-align: right;
    }

    .device-info-line {
        margin-bottom: 4px;
        overflow-wrap: anywhere;
    }

    .btn-confirm-pickup {
        padding: 8px 11px;
        border: 0;
        border-radius: 7px;
        background: #e8f2ff;
        color: #245a91;
        cursor: pointer;
        font-weight: 600;
        white-space: nowrap;
    }

    .pickup-planned-date {
        display: block;
        margin-top: 5px;
        color: #74816e;
    }

    .pickup-pending-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #986a12;
        font-weight: 600;
    }

    .borrowed-alert-success {
        background: #e9f7ed;
        color: #25643a;
    }

    .return-date-input-group {
        position: relative;
        display: flex;
        align-items: center;
        gap: 6px;
        width: max-content;
        max-width: 100%;
    }

    .return-date-picker-button {
        flex: 0 0 auto;
        padding: 8px 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
        color: #526640;
        cursor: pointer;
    }

    .native-date-picker {
        position: absolute;
        top: 0;
        right: 0;
        width: 40px;
        height: 100%;
        opacity: 0;
        pointer-events: none;
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
        .return-status-cell button,
        .btn-confirm-pickup {
            display: none !important;
        }

        .return-date-picker-button,
        .native-date-picker {
            display: none !important;
        }

        #noReturnedResults,
        .returned-row[style*="display: none"] {
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
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;
    const fromMonth = document.getElementById('fromMonthFilter').value;
    const toMonth = document.getElementById('toMonthFilter').value;

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
        const filterDate = row.dataset.filterDate || '';
        const rowYear = filterDate.substring(0, 4);
        const rowMonth = filterDate.substring(5, 7);
        const hasDateFilter =
            year !== 'all' ||
            month !== 'all' ||
            fromMonth !== '' ||
            toMonth !== '';

        const statusMatch = status === 'all' || rowStatus === status;
        const searchMatch = rowSearch.includes(search);
        const hasDateMatch = !hasDateFilter || filterDate !== '';
        const yearMatch = year === 'all' || rowYear === year;
        const monthMatch = month === 'all' || rowMonth === month;
        const fromMonthMatch = !fromMonth || rowMonth >= fromMonth;
        const toMonthMatch = !toMonth || rowMonth <= toMonth;
        const show =
            statusMatch &&
            searchMatch &&
            hasDateMatch &&
            yearMatch &&
            monthMatch &&
            fromMonthMatch &&
            toMonthMatch;

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
    document.getElementById('yearFilter').value = 'all';
    document.getElementById('monthFilter').value = 'all';
    document.getElementById('fromMonthFilter').value = '';
    document.getElementById('toMonthFilter').value = '';
    document.getElementById('searchFilter').value = '';
    applyFilters();
}

function printReturnedItems() {
    applyFilters();

    document.querySelectorAll('.due-date-cell').forEach(function (cell) {
        const input = cell.querySelector('.return-due-date-input');

        if (input) {
            cell.dataset.printDate = input.value || '-';
        }
    });

    window.print();
}

function parseDisplayDate(value) {
    const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value.trim());
    if (!match) {
        return null;
    }

    const day = Number(match[1]);
    const month = Number(match[2]);
    const year = Number(match[3]);
    const date = new Date(year, month - 1, day);

    if (
        year < 1000 ||
        date.getFullYear() !== year ||
        date.getMonth() !== month - 1 ||
        date.getDate() !== day
    ) {
        return null;
    }

    return year + '-' + match[2] + '-' + match[1];
}

function openDatePicker(pickerId) {
    const picker = document.getElementById(pickerId);
    if (!picker) {
        return;
    }

    if (typeof picker.showPicker === 'function') {
        picker.showPicker();
    } else {
        picker.focus();
        picker.click();
    }
}

function syncDueDateFromPicker(picker) {
    if (!picker.value) {
        return;
    }

    const parts = picker.value.split('-');
    const dateInput = document.getElementById(
        picker.id.replace('_picker', '')
    );
    dateInput.value = parts[2] + '/' + parts[1] + '/' + parts[0];
    dateInput.dispatchEvent(new Event('change', { bubbles: true }));
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
    const isoDate = dateInput ? parseDisplayDate(dateInput.value) : null;

    if (!dateInput || !isoDate) {
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
        isoDate;

    const dateParts = dateInput.value.split('/');

    document.getElementById('returnDueDateDisplay').textContent =
        dateParts.length === 3
            ? dateInput.value
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

function openPickupModal(button) {
    const modal = document.getElementById('pickupModal');
    const form = document.getElementById('pickupConfirmForm');
    form.action = button.dataset.action || '';

    document.getElementById('pickupBorrower').textContent =
        button.dataset.borrower || '-';
    document.getElementById('pickupItem').textContent =
        button.dataset.item || '-';

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    modal.querySelector('.return-btn-cancel').focus();
}

function closePickupModal() {
    const modal = document.getElementById('pickupModal');
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function closeReturnModal() {
    const modal = document.getElementById('returnModal');

    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.querySelectorAll('.return-due-date-input').forEach(function (input) {
    input.addEventListener('change', function () {
        const validDate = parseDisplayDate(this.value);
        if (validDate) {
            this.classList.remove('invalid');

            const loanId = this.id.replace('due_date_', '');
            const warning = document.getElementById(
                'due_date_warning_' + loanId
            );

            if (warning) {
                warning.hidden = true;
            }
        } else if (this.value.trim() !== '') {
            this.classList.add('invalid');

            const loanId = this.id.replace('due_date_', '');
            const warning = document.getElementById(
                'due_date_warning_' + loanId
            );

            if (warning) {
                warning.textContent = 'Use a valid date in dd/mm/yyyy format.';
                warning.hidden = false;
            }
        }
    });
});

document.querySelectorAll('.native-date-picker').forEach(function (picker) {
    picker.addEventListener('change', function () {
        syncDueDateFromPicker(this);
    });
});

function populateYearFilter() {
    const yearFilter = document.getElementById('yearFilter');
    const years = Array.from(document.querySelectorAll('.returned-row'))
        .map(row => (row.dataset.filterDate || '').substring(0, 4))
        .filter(Boolean);

    Array.from(new Set(years))
        .sort((a, b) => b.localeCompare(a))
        .forEach(function (year) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            yearFilter.appendChild(option);
        });
}

document.getElementById('returnModal').addEventListener(
    'click',
    function (event) {
        if (event.target === this) {
            closeReturnModal();
        }
    }
);

document.getElementById('pickupModal').addEventListener(
    'click',
    function (event) {
        if (event.target === this) {
            closePickupModal();
        }
    }
);

document.addEventListener('keydown', function (event) {
    const modal = document.getElementById('returnModal');
    const pickupModal = document.getElementById('pickupModal');

    if (event.key === 'Escape' && modal.classList.contains('active')) {
        closeReturnModal();
    }

    if (event.key === 'Escape' && pickupModal.classList.contains('active')) {
        closePickupModal();
    }
});

document.getElementById('pickupConfirmForm').addEventListener(
    'submit',
    function (event) {
        const button = document.getElementById('confirmPickupSubmit');

        if (!this.action || button.disabled) {
            event.preventDefault();
            return;
        }

        button.disabled = true;
        button.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> Processing...';
    }
);

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

populateYearFilter();

if (urlStatus && ['belum', 'sudah'].includes(urlStatus)) {
    document.getElementById('statusFilter').value = urlStatus;
}

applyFilters();
</script>
@endpush