@extends('layouts.app')

@section('title', 'Loans Data')

@section('content')
<div class="dashboard">

    <div class="loans-page-header">
        <h2 class="page-title" style="margin-bottom:0;">
            <i class="fas fa-list"></i> Loans Data
        </h2>
    </div>

    {{-- STATISTICS --}}
    <div class="stats-grid">
        <div class="status-card" onclick="filterStatus('pending')" style="cursor:pointer;">
            <h4>{{ $stats['Pending'] ?? 0 }}</h4>
            <p><i class="fas fa-clock"></i> Pending</p>
        </div>

        <div class="status-card proses" onclick="filterStatus('process')" style="cursor:pointer;">
            <h4>{{ $stats['Process'] ?? 0 }}</h4>
            <p><i class="fas fa-spinner"></i> Process</p>
        </div>

        <div class="status-card selesai" onclick="filterStatus('selesai')" style="cursor:pointer;">
            <h4>{{ $stats['Done'] ?? 0 }}</h4>
            <p><i class="fas fa-check-circle"></i> Completed</p>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="dashboard-card">
        <div class="filter-bar">
            <select id="statusFilter" onchange="applyFilters()">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="process">Process</option>
                <option value="selesai">Completed</option>
                <option value="ditolak">Rejected</option>
            </select>

            <select id="itemFilter" onchange="applyFilters()">
                <option value="all">All Items</option>
                @foreach($items as $item)
                    <option value="{{ strtolower($item->name) }}">
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>

            <input
                type="text"
                id="searchFilter"
                placeholder="Search name/department/hostname..."
                oninput="applyFilters()"
                autocomplete="off"
            >

            <button type="button" class="btn-outline" onclick="resetFilters()">
                <i class="fas fa-undo"></i> Reset
            </button>
        </div>

        {{-- LOANS TABLE --}}
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Borrowed</th>
                        <th>Department</th>
                        <th>Item</th>
                        <th>Device Info</th>
                        <th>Others</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody id="loanTable">
                    @forelse($loans as $no => $loan)
                        @php
                            $loanItem = $loan->item;

                            $requiresHostname = (bool) (
                                $loanItem->requires_hostname ?? false
                            );

                            $tracksSerial = (bool) (
                                $loanItem->tracks_serial_numbers ?? false
                            );

                            $serialOptions = collect();

                            if ($loanItem && $tracksSerial) {
                                $serialOptions = $loanItem->serialNumbers
                                    ->filter(function ($serial) use ($loan) {
                                        if ($serial->status !== 'available') {
                                            return false;
                                        }

                                        $isBorrowedByAnotherLoan =
                                            \App\Models\Loans::where(
                                                'item_id',
                                                $loan->item_id
                                            )
                                            ->where('sn', $serial->serial_number)
                                            ->whereIn('status', ['process', 'selesai'])
                                            ->where('id', '!=', $loan->id)
                                            ->exists();

                                        return !$isBorrowedByAnotherLoan;
                                    })
                                    ->map(function ($serial) use ($loan) {
                                        return [
                                            'id' => $serial->id,
                                            'serial_number' => $serial->serial_number,
                                            'selected' =>
                                                (string) $serial->serial_number ===
                                                (string) ($loan->sn ?? ''),
                                        ];
                                    })
                                    ->values();
                            }
                        @endphp

                        <tr
                            class="loan-row"
                            data-status="{{ $loan->status }}"
                            data-item="{{ strtolower($loanItem->name ?? '') }}"
                            data-search="{{ strtolower(
                                ($loan->name ?? '') . ' ' .
                                ($loan->email ?? '') . ' ' .
                                ($loan->departement ?? '') . ' ' .
                                ($loanItem->name ?? '') . ' ' .
                                ($loan->hostname ?? '') . ' ' .
                                ($loan->type ?? '') . ' ' .
                                ($loan->sn ?? '') . ' ' .
                                ($loan->others ?? '')
                            ) }}"
                        >
                            <td>{{ $no + 1 }}</td>

                            <td>
                                <strong>{{ $loan->name }}</strong>
                                <div class="borrower-email">
                                    {{ $loan->email ?? '-' }}
                                </div>
                            </td>

                            <td>{{ $loan->departement ?? '-' }}</td>

                            <td>{{ $loanItem->name ?? '-' }}</td>

                            <td>
                                @if($requiresHostname && filled($loan->hostname))
                                    <strong>{{ $loan->hostname }}</strong>
                                @endif

                                @if(filled($loan->type))
                                    <div class="borrower-email">
                                        {{ $loan->type }}
                                    </div>
                                @endif

                                @if(filled($loan->sn))
                                    <div class="borrower-email">
                                        SN: {{ $loan->sn }}
                                    </div>
                                @endif

                                @if(
                                    !($requiresHostname && filled($loan->hostname)) &&
                                    !filled($loan->type) &&
                                    !filled($loan->sn)
                                )
                                    -
                                @endif
                            </td>

                            <td>
                                @if(filled($loan->others))
                                    <button
                                        type="button"
                                        class="btn-view-others"
                                        onclick="openOthersModal(
                                            @js($loan->others),
                                            @js($loan->name)
                                        )"
                                    >
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                {{ $loan->start_date?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td>
                                @if($loan->status === 'process')
                                    <span class="badge badge-info">Process</span>
                                @elseif($loan->status === 'selesai')
                                    <span class="badge badge-success">Completed</span>
                                @elseif($loan->status === 'pending')
                                    <span class="badge badge-secondary">Pending</span>
                                @elseif($loan->status === 'ditolak')
                                    <span class="badge badge-secondary">Rejected</span>
                                @else
                                    <span class="badge badge-secondary">
                                        {{ ucfirst($loan->status) }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if($loan->status === 'selesai')
                                    <span class="badge badge-success loan-completed-action">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Completed</span>
                                    </span>

                                @elseif(
                                    $loan->status === 'process' &&
                                    $loanItem &&
                                    ($requiresHostname || $tracksSerial)
                                )
                                    <button
                                        type="button"
                                        class="btn-table btn-warning"
                                        onclick="openEditModal(
                                            {{ $loan->id }},
                                            @js($loan->name),
                                            @js($loan->hostname),
                                            @js($loan->type),
                                            @js($loan->sn),
                                            {{ $requiresHostname ? 'true' : 'false' }},
                                            {{ $tracksSerial ? 'true' : 'false' }},
                                            @js($serialOptions)
                                        )"
                                    >
                                        <i class="fas fa-edit"></i> Edit
                                    </button>

                                @elseif($loan->status === 'process')
                                    <form
                                        action="{{ route('admin.loans.update-status', $loan->id) }}"
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirmCompleteLoan(this)"
                                    >
                                        @csrf
                                        @method('PUT')

                                        <input type="hidden" name="status" value="selesai">

                                        <button type="submit" class="btn-table btn-success">
                                            <i class="fas fa-check-circle"></i>
                                            Completed
                                        </button>
                                    </form>

                                @else
                                    <span style="color:#888;">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No loan data yet.</p>
                            </td>
                        </tr>
                    @endforelse

                    <tr id="noLoanResults" style="display:none;">
                        <td colspan="9" class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>No matching loan records found.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- EDIT LOAN MODAL --}}
<div id="editModal" class="edit-modal" aria-hidden="true">
    <div
        class="edit-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editModalTitle"
    >
        <div class="edit-modal-header">
            <div>
                <h3 id="editModalTitle">
                    <i class="fas fa-edit"></i> Edit Loan Data
                </h3>

                <small id="editUserName"></small>
            </div>

            <button
                type="button"
                onclick="closeEditModal()"
                class="edit-modal-close"
                aria-label="Close modal"
            >&times;</button>
        </div>

        <form id="editLoanForm" method="POST">
            @csrf
            @method('PUT')

            <div class="edit-modal-body">

                {{-- SERIAL NUMBER --}}
                <div class="edit-field" id="editSerialField">
                    <label for="editSn">
                        Serial Number (SN)
                        <span id="serialRequiredMark"></span>
                    </label>

                    <select id="editSn" name="sn">
                        <option value="">-- Select serial number --</option>
                    </select>
                </div>

                {{-- HOSTNAME --}}
                <div class="edit-field" id="editHostnameField">
                    <label for="editHostname">
                        Hostname <span id="hostnameRequiredMark"></span>
                    </label>

                    <input
                        type="text"
                        id="editHostname"
                        name="hostname"
                        maxlength="255"
                        placeholder="Enter hostname"
                    >
                </div>

                {{-- TYPE / MODEL --}}
                <div class="edit-field">
                    <label for="editType">Type / Model</label>

                    <input
                        type="text"
                        id="editType"
                        name="type"
                        maxlength="255"
                        placeholder="Example: Laptop / PC"
                        required
                    >
                </div>
            </div>

            <div class="edit-modal-footer">
                <button
                    type="button"
                    onclick="closeEditModal()"
                    class="btn-outline"
                >
                    Cancel
                </button>

                <button type="submit" id="saveLoanButton" class="btn-table btn-success">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- OTHERS MODAL --}}
<div id="othersModal" class="others-modal" aria-hidden="true">
    <div
        class="others-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="othersModalTitle"
    >
        <div class="others-modal-header">
            <div>
                <h3 id="othersModalTitle">
                    <i class="fas fa-info-circle"></i> Others Information
                </h3>

                <small id="othersBorrower"></small>
            </div>

            <button
                type="button"
                class="others-modal-close"
                onclick="closeOthersModal()"
                aria-label="Close modal"
            >&times;</button>
        </div>

        <div class="others-modal-body">
            <p id="othersContent"></p>
        </div>

        <div class="others-modal-footer">
            <button type="button" class="btn-close-others" onclick="closeOthersModal()">
                Close
            </button>
        </div>
    </div>
</div>

{{-- COMPLETE LOAN CONFIRMATION --}}
<div id="completeLoanModal" class="portal-modal-overlay" aria-hidden="true">
    <div
        class="portal-confirm-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="completeLoanTitle"
    >
        <div class="portal-confirm-icon">
            <i class="fas fa-check-circle"></i>
        </div>

        <h3 id="completeLoanTitle">Complete this loan?</h3>
        <p>Are you sure you want to mark this loan as completed?</p>

        <div class="portal-confirm-actions">
            <button
                type="button"
                class="portal-modal-cancel"
                onclick="closeCompleteLoanModal()"
            >
                Cancel
            </button>

            <button
                type="button"
                class="portal-modal-confirm"
                onclick="submitCompleteLoan()"
            >
                Yes, complete
            </button>
        </div>
    </div>
</div>

<script>
/* =========================================================
   FILTERS
========================================================= */
function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const item = document.getElementById('itemFilter').value;
    const search = document.getElementById('searchFilter')
        .value.toLowerCase().trim();

    const rows = document.querySelectorAll('.loan-row');
    let visibleCount = 0;

    rows.forEach(function (row) {
        const statusMatch =
            status === 'all' || row.dataset.status === status;

        const itemMatch =
            item === 'all' || row.dataset.item === item;

        const searchMatch =
            (row.dataset.search || '').includes(search);

        const show = statusMatch && itemMatch && searchMatch;

        row.style.display = show ? '' : 'none';

        if (show) visibleCount++;
    });

    const noResults = document.getElementById('noLoanResults');

    if (noResults) {
        noResults.style.display = visibleCount === 0 ? '' : 'none';
    }
}

function filterStatus(status) {
    document.getElementById('statusFilter').value = status;
    applyFilters();
}

function resetFilters() {
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('itemFilter').value = 'all';
    document.getElementById('searchFilter').value = '';
    applyFilters();
}

const urlStatus = new URLSearchParams(window.location.search).get('status');

if (urlStatus && ['pending', 'process', 'selesai', 'ditolak'].includes(urlStatus)) {
    document.getElementById('statusFilter').value = urlStatus;
}

applyFilters();


/* =========================================================
   EDIT LOAN MODAL
========================================================= */
function openEditModal(
    loanId,
    userName,
    hostname,
    type,
    sn,
    requiresHostname,
    tracksSerial,
    serialOptions
) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editLoanForm');

    const hostnameField = document.getElementById('editHostnameField');
    const hostnameInput = document.getElementById('editHostname');

    const serialField = document.getElementById('editSerialField');
    const serialSelect = document.getElementById('editSn');

    const typeInput = document.getElementById('editType');
    const saveButton = document.getElementById('saveLoanButton');

    serialOptions = Array.isArray(serialOptions) ? serialOptions : [];

    document.getElementById('editUserName').textContent =
        'Borrower: ' + (userName || '-');

    // Tampilkan hostname hanya jika item membutuhkannya.
    hostnameField.style.display = requiresHostname ? '' : 'none';
    hostnameInput.required = Boolean(requiresHostname);
    hostnameInput.disabled = !requiresHostname;
    hostnameInput.value = requiresHostname ? (hostname || '') : '';

    document.getElementById('hostnameRequiredMark').textContent =
        requiresHostname ? '*' : '';

    // Tampilkan dropdown SN hanya jika item menggunakan serial number.
    serialField.style.display = tracksSerial ? '' : 'none';
    serialSelect.required = Boolean(tracksSerial);
    serialSelect.disabled = !tracksSerial;

    document.getElementById('serialRequiredMark').textContent =
        tracksSerial ? '*' : '';

    // Bangun ulang pilihan SN.
    serialSelect.replaceChildren();

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '-- Select serial number --';
    serialSelect.appendChild(placeholder);

    serialOptions.forEach(function (serial) {
        if (!serial || !serial.serial_number) return;

        const option = document.createElement('option');
        option.value = serial.serial_number;
        option.textContent = serial.serial_number;

        if (
            serial.selected ||
            String(serial.serial_number) === String(sn || '')
        ) {
            option.selected = true;
        }

        serialSelect.appendChild(option);
    });

    typeInput.value = type || '';

    // Tidak ada pesan tambahan seperti "Select SN..." atau
    // "Hostname tidak diperlukan untuk item ini".

    form.action = `/admin/loans/${loanId}`;

    saveButton.disabled = false;
    saveButton.innerHTML = '<i class="fas fa-save"></i> Save Changes';

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    setTimeout(function () {
        if (tracksSerial) {
            serialSelect.focus();
        } else if (requiresHostname) {
            hostnameInput.focus();
        } else {
            typeInput.focus();
        }
    }, 100);
}

function closeEditModal() {
    const modal = document.getElementById('editModal');

    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');

    restoreBodyScrollIfNoModal();
}


/* =========================================================
   OTHERS MODAL
========================================================= */
function openOthersModal(content, borrowerName) {
    const modal = document.getElementById('othersModal');

    document.getElementById('othersContent').textContent = content || '-';
    document.getElementById('othersBorrower').textContent =
        'Borrower: ' + (borrowerName || '-');

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeOthersModal() {
    const modal = document.getElementById('othersModal');

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');

    restoreBodyScrollIfNoModal();
}


/* =========================================================
   MODAL UTILITIES
========================================================= */
function restoreBodyScrollIfNoModal() {
    const editOpen = document
        .getElementById('editModal')
        .classList.contains('active');

    const othersOpen = document
        .getElementById('othersModal')
        .classList.contains('show');

    const completeOpen = document
        .getElementById('completeLoanModal')
        .classList.contains('show');

    if (!editOpen && !othersOpen && !completeOpen) {
        document.body.style.overflow = '';
    }
}

document.getElementById('editModal').addEventListener('click', function (event) {
    if (event.target === this) closeEditModal();
});

document.getElementById('othersModal').addEventListener('click', function (event) {
    if (event.target === this) closeOthersModal();
});


/* =========================================================
   COMPLETE LOAN
========================================================= */
let pendingCompleteLoanForm = null;

function confirmCompleteLoan(form) {
    pendingCompleteLoanForm = form;

    const modal = document.getElementById('completeLoanModal');

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    return false;
}

function closeCompleteLoanModal() {
    const modal = document.getElementById('completeLoanModal');

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');

    pendingCompleteLoanForm = null;
    restoreBodyScrollIfNoModal();
}

function submitCompleteLoan() {
    if (!pendingCompleteLoanForm) return;

    const form = pendingCompleteLoanForm;
    pendingCompleteLoanForm = null;

    form.removeAttribute('onsubmit');
    form.submit();
}


/* =========================================================
   ESCAPE KEY
========================================================= */
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeEditModal();
        closeOthersModal();
        closeCompleteLoanModal();
    }
});


/* =========================================================
   FORM SUBMISSION
========================================================= */
document.getElementById('editLoanForm').addEventListener('submit', function (event) {
    const form = this;
    const saveButton = document.getElementById('saveLoanButton');

    if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
        return;
    }

    saveButton.disabled = true;
    saveButton.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Saving...';
});
</script>
@endsection