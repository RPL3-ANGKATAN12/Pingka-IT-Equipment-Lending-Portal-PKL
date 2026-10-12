@extends('layouts.app')

@section('title', 'Borrowed Items')

@section('content')
<div class="dashboard">

    {{-- Page Header --}}
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

    {{-- Success Message --}}
    @if(session('success'))
        <div class="borrowed-alert borrowed-alert-success">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Error Message --}}
    @if(session('error'))
        <div class="borrowed-alert borrowed-alert-error">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Validation Errors --}}
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

    {{-- Borrowed Items Table --}}
    <div class="dashboard-card">

        {{-- Search and Filter --}}
        <div class="borrowed-toolbar">
            <input
                type="text"
                id="searchReturned"
                placeholder="Search by borrower, item, or department..."
                oninput="filterReturnedItems()"
            >

            <select
                id="filterReturned"
                onchange="filterReturnedItems()"
            >
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

                            {{-- Row Number --}}
                            <td>{{ $loop->iteration }}</td>

                            {{-- Borrower --}}
                            <td>
                                <strong>{{ $loan->name }}</strong>

                                <div class="borrower-email">
                                    {{ $loan->email ?? '-' }}
                                </div>
                            </td>

                            {{-- Department --}}
                            <td>{{ $loan->departement ?? '-' }}</td>

                            {{-- Item --}}
                            <td>{{ $loan->item->name ?? '-' }}</td>

                            {{-- Hostname --}}
                            <td>{{ $loan->hostname ?? '-' }}</td>

                            {{-- Type --}}
                            <td>{{ $loan->type ?? '-' }}</td>

                            {{-- Serial Number --}}
                            <td>{{ $loan->sn ?? '-' }}</td>

                            {{-- Borrow Date --}}
                            <td>
                                {{ $loan->start_date?->format('d/m/Y') ?? '-' }}
                            </td>

                            {{-- Return Due Date --}}
                            <td class="due-date-cell">

                                @if($loan->returned_at)

                                    {{ $loan->due_date?->format('d/m/Y') ?? '-' }}

                                @else

                                    <form
                                        method="POST"
                                        action="{{ route('admin.returned-items.due-date', $loan->id) }}"
                                        class="due-date-form"
                                    >
                                        @csrf
                                        @method('PUT')

                                        <input
                                            type="date"
                                            name="due_date"
                                            value="{{ $loan->due_date?->format('Y-m-d') }}"
                                            required
                                        >

                                        <button type="submit" class="due-date-save">
                                            Save
                                        </button>
                                    </form>

                                @endif

                            </td>

                            {{-- Return Status --}}
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
                                <p>No completed loan records found.</p>
                            </td>
                        </tr>

                    @endforelse

                    {{-- No Search Results --}}
                    <tr id="noReturnedResults" style="display:none;">
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


{{-- ==========================================
     CONFIRM RETURN MODAL
========================================== --}}
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

        {{-- Modal Icon --}}
        <div class="return-modal-icon">
            <i class="fas fa-box-open"></i>
        </div>

        {{-- Modal Heading --}}
        <h3 id="returnModalTitle">
            Confirm Item Return
        </h3>

        <p class="return-modal-description">
            Are you sure this item has been returned?
            Please verify the details below before continuing.
        </p>

        {{-- Item Details --}}
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

        </div>

        {{-- Stock Information --}}
        <div class="return-modal-notice">
            <i class="fas fa-info-circle"></i>

            <span>
                The available stock will increase by one after confirmation.
            </span>
        </div>

        {{-- Confirmation Form --}}
        <form
            id="returnConfirmForm"
            method="POST"
            action=""
        >
            @csrf

            <input
                type="hidden"
                name="returned"
                value="1"
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
                >
                    <i class="fas fa-check"></i>
                    Yes, Confirm Return
                </button>

            </div>
        </form>

    </div>
</div>


{{-- ==========================================
     CSS
========================================== --}}
<style>
/* Page Header */
.borrowed-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}

.borrowed-header .page-title {
    margin-bottom: 6px;
}

.borrowed-subtitle {
    color: #64748b;
    margin: 0;
    line-height: 1.6;
}

/* Alert Messages */
.borrowed-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 13px 16px;
    margin-bottom: 16px;
    border-radius: 10px;
    font-size: 14px;
}

.borrowed-alert-success {
    background: #dcfce7;
    color: #166534;
}

.borrowed-alert-error {
    background: #fee2e2;
    color: #991b1b;
}

.borrowed-alert ul {
    margin: 0;
    padding-left: 18px;
}

/* Search and Filter */
.borrowed-toolbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
}

.borrowed-toolbar input,
.borrowed-toolbar select {
    box-sizing: border-box;
    min-height: 42px;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #fff;
    color: #334155;
    font-family: inherit;
    font-size: 13px;
}

.borrowed-toolbar input {
    width: min(100%, 360px);
}

.borrowed-toolbar input:focus,
.borrowed-toolbar select:focus {
    outline: none;
    border-color: #8eaa65;
    box-shadow: 0 0 0 3px rgba(142, 170, 101, 0.14);
}

/* Table */
.borrowed-table {
    width: 100%;
}

.borrower-email {
    margin-top: 4px;
    color: #64748b;
    font-size: 12px;
}

.due-date-cell {
    min-width: 190px;
}

.due-date-form {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.due-date-form input {
    box-sizing: border-box;
    max-width: 155px;
    min-width: 0;
    padding: 7px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    font-family: inherit;
    font-size: 12px;
}

.due-date-save {
    padding: 7px 10px;
    border: 0;
    border-radius: 7px;
    background: #dcefe1;
    color: #245c39;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.due-date-save:hover {
    background: #c7e5d0;
}

.return-status-cell {
    min-width: 155px;
}

.returned-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 20px;
    background: #dcfce7;
    color: #166534;
    font-size: 12px;
    font-weight: 600;
}

.returned-date {
    margin-top: 6px;
    color: #64748b;
    font-size: 12px;
}

.borrowed-empty {
    padding: 30px !important;
    color: #64748b;
    text-align: center;
}

.borrowed-empty i {
    color: #8da66a;
    font-size: 25px;
}

.borrowed-empty p {
    margin-bottom: 0;
}

/* Confirm Return Button */
.btn-confirm-return {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 12px;
    border: 0;
    border-radius: 8px;
    background: #718747;
    color: #fff;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    transition: background 0.2s ease, transform 0.2s ease;
}

.btn-confirm-return:hover {
    background: #5f7439;
    transform: translateY(-1px);
}

/* Modal Overlay */
.return-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-y: auto;
    background: rgba(25, 35, 22, 0.52);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.return-modal-overlay.active {
    display: flex;
    animation: returnFadeIn 0.2s ease;
}

/* Modal Container */
.return-modal {
    box-sizing: border-box;
    width: 100%;
    max-width: 450px;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    padding: 28px;
    border: 1px solid #edf0e8;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    animation: returnSlideUp 0.25s ease;
}

/* Modal Icon */
.return-modal-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 58px;
    height: 58px;
    margin: 0 auto 16px;
    border-radius: 50%;
    background: #edf3df;
    color: #718747;
    font-size: 25px;
}

/* Modal Text */
.return-modal h3 {
    margin: 0 0 10px;
    color: #526b36;
    font-size: 22px;
    font-weight: 700;
    text-align: center;
}

.return-modal-description {
    margin: 0 0 22px;
    color: #64748b;
    font-size: 13px;
    line-height: 1.7;
    text-align: center;
}

/* Borrower and Item Details */
.return-modal-details {
    display: flex;
    flex-direction: column;
    gap: 15px;
    padding: 16px;
    border: 1px solid #e5eadb;
    border-radius: 12px;
    background: #f8faf4;
}

.return-detail-row {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.return-detail-row span {
    color: #7b8490;
    font-size: 12px;
}

.return-detail-row strong {
    overflow-wrap: anywhere;
    color: #334155;
    font-size: 14px;
    font-weight: 600;
}

/* Stock Notice */
.return-modal-notice {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin-top: 16px;
    padding: 12px;
    border-radius: 9px;
    background: #edf5e8;
    color: #526b36;
    font-size: 12px;
    line-height: 1.7;
}

.return-modal-notice i {
    margin-top: 3px;
    flex-shrink: 0;
}

/* Modal Buttons */
.return-modal-actions {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 24px;
}

.return-btn-cancel,
.return-btn-confirm {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 40px;
    padding: 10px 14px;
    border: none;
    border-radius: 8px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s ease;
}

.return-btn-cancel {
    background: #f1f3ed;
    color: #526044;
}

.return-btn-cancel:hover {
    background: #e5e9dc;
}

.return-btn-confirm {
    background: #718747;
    color: #fff;
}

.return-btn-confirm:hover {
    background: #5f7439;
}

.return-btn-cancel:focus-visible,
.return-btn-confirm:focus-visible,
.btn-confirm-return:focus-visible {
    outline: 3px solid #b9cc96;
    outline-offset: 3px;
}

/* Animations */
@keyframes returnFadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes returnSlideUp {
    from {
        opacity: 0;
        transform: translateY(12px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 480px) {
    .return-modal {
        padding: 22px 18px;
    }

    .return-modal h3 {
        font-size: 20px;
    }

    .return-modal-actions {
        flex-direction: column-reverse;
    }

    .return-btn-cancel,
    .return-btn-confirm {
        width: 100%;
    }

    .borrowed-toolbar input {
        width: 100%;
    }

    .borrowed-toolbar select {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .return-modal-overlay.active,
    .return-modal {
        animation: none;
    }

    .btn-confirm-return {
        transition: none;
    }
}
</style>


{{-- ==========================================
     JAVASCRIPT
========================================== --}}
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
    const modal = document.getElementById('returnModal');
    const form = document.getElementById('returnConfirmForm');

    form.action = button.dataset.action;

    document.getElementById('returnBorrower').textContent =
        button.dataset.borrower || '-';

    document.getElementById('returnItem').textContent =
        button.dataset.item || '-';

    document.getElementById('returnHostname').textContent =
        button.dataset.hostname || '-';

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');

    document.body.style.overflow = 'hidden';

    const cancelButton = modal.querySelector('.return-btn-cancel');

    if (cancelButton) {
        cancelButton.focus();
    }
}


/* Close Modal */
function closeReturnModal() {
    const modal = document.getElementById('returnModal');

    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');

    document.body.style.overflow = '';
}


/* Close Modal When Clicking Outside */
document.getElementById('returnModal').addEventListener(
    'click',
    function (event) {
        if (event.target === this) {
            closeReturnModal();
        }
    }
);


/* Close Modal Using Escape */
document.addEventListener('keydown', function (event) {
    if (
        event.key === 'Escape' &&
        document.getElementById('returnModal').classList.contains('active')
    ) {
        closeReturnModal();
    }
});
</script>

@endsection