
@extends('layouts.app')

@section('title', 'Loans Data')

@section('content')

<div class="dashboard">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <h2 class="page-title" style="margin-bottom:0;">
            <i class="fas fa-list"></i>
            Loans Data
        </h2>
    </div>

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

        <div class="status-card" onclick="filterStatus('ditolak')" style="cursor:pointer;">
            <h4>{{ $stats['Rejected'] ?? 0 }}</h4>
            <p><i class="fas fa-times-circle"></i> Rejected</p>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="filter-bar" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:1.5rem;">

            <select id="statusFilter" onchange="applyFilters()" style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:white;">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="process">Process</option>
                <option value="selesai">Completed</option>
                <option value="ditolak">Rejected</option>
            </select>

            <select id="itemFilter" onchange="applyFilters()" style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:white;">
                <option value="all">All Items</option>

                @foreach($items as $item)
                    <option value="{{ strtolower($item->name) }}">
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>

            <input type="text" id="searchFilter" placeholder="Search name/department/hostname..." oninput="applyFilters()" autocomplete="off" style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; min-width:260px;">

            <button type="button" class="btn-outline" onclick="resetFilters()">
                <i class="fas fa-undo"></i>
                Reset
            </button>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Item</th>
                        <th>Hostname</th>
                        <th>Type</th>
                        <th>SN</th>
                        <th>Others</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody id="loanTable">

                    @forelse($loans as $no => $loan)

                        <tr
                            class="loan-row"
                            data-status="{{ $loan->status }}"
                            data-item="{{ strtolower($loan->item->name ?? '') }}"
                            data-search="{{ strtolower(
                                ($loan->name ?? '') . ' ' .
                                ($loan->email ?? '') . ' ' .
                                ($loan->departement ?? '') . ' ' .
                                ($loan->hostname ?? '') . ' ' .
                                ($loan->type ?? '') . ' ' .
                                ($loan->sn ?? '') . ' ' .
                                ($loan->others ?? '')
                            ) }}"
                        >
                            <td>{{ $no + 1 }}</td>

                            <td>
                                <strong>{{ $loan->name }}</strong>
                            </td>

                            <td>{{ $loan->email ?? '-' }}</td>

                            <td>{{ $loan->departement ?? '-' }}</td>

                            <td>{{ $loan->item->name ?? '-' }}</td>

                            <td>{{ $loan->hostname ?? '-' }}</td>

                            <td>{{ $loan->type ?? '-' }}</td>

                            <td>{{ $loan->sn ?? '-' }}</td>

                            <td>{{ $loan->others ?? '-' }}</td>

                            <td>
                                {{ $loan->start_date?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td>
                                @if($loan->status === 'process')
                                    <span class="badge badge-info">
                                        Process
                                    </span>

                                @elseif($loan->status === 'selesai')
                                    <span class="badge badge-success">
                                        Completed
                                    </span>

                                @elseif($loan->status === 'ditolak')
                                    <span class="badge badge-secondary">
                                        Rejected
                                    </span>

                                @else
                                    <span class="badge badge-secondary">
                                        {{ ucfirst($loan->status) }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="btn-table btn-warning"
                                    onclick="openEditModal(
                                        {{ $loan->id }},
                                        @js($loan->name),
                                        @js($loan->hostname),
                                        @js($loan->type),
                                        @js($loan->sn),
                                        @js($loan->status)
                                    )"
                                    title="Edit loan data"
                                >
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </button>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="12" class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No loan data yet.</p>
                            </td>
                        </tr>
                    @endforelse

                    {{-- No Filter Results --}}
                    <tr id="noLoanResults" style="display:none;">
                        <td colspan="12" class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>No matching loan records found.</p>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
</div>


<div id="editModal" class="edit-modal" aria-hidden="true">
    <div class="edit-modal-content" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="edit-modal-header">
            <div>
                <h3 id="editModalTitle">
                    <i class="fas fa-edit"></i>
                    Edit Loan Data
                </h3>
                <small id="editUserName"></small>
            </div>

            <button type="button" onclick="closeEditModal()" class="edit-modal-close" aria-label="Close modal">
                &times;
            </button>
        </div>

        <form id="editLoanForm" method="POST">
            @csrf
            @method('PUT')

            <div class="edit-modal-body">

                <div class="edit-field">
                    <label for="editHostname">Hostname</label>
                    <input type="text" id="editHostname" name="hostname" maxlength="100" placeholder="Enter hostname">
                </div>

                <div class="edit-field">
                    <label for="editType">Type</label>
                    <input type="text" id="editType" name="type" maxlength="50" placeholder="Example: Laptop / PC">
                </div>

                <div class="edit-field">
                    <label for="editSn">Serial Number (SN)</label>
                    <input type="text" id="editSn" name="sn" maxlength="100" placeholder="Enter serial number">
                </div>
            </div>

            <div class="edit-modal-footer">
                <button type="button" onclick="closeEditModal()" class="btn-outline">
                    Cancel
                </button>
                <button type="submit" id="saveLoanButton" class="btn-table btn-success">
                    <i class="fas fa-save"></i>
                    Save & Complete
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.edit-modal {
    display: none;
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    background: rgba(25, 35, 22, 0.52);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 999999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

.edit-modal.active {
    display: flex;
    animation: editFadeIn 0.2s ease;
}

.edit-modal-content {
    box-sizing: border-box;
    width: 100%;
    max-width: 500px;
    max-height: calc(100vh - 40px);
    background: #fff;
    border: 1px solid #edf0e8;
    border-radius: 14px;
    overflow-y: auto;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    animation: editSlideUp 0.25s ease;
}

.edit-modal-header {
    padding: 18px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.edit-modal-header h3 {
    margin: 0;
    color: #526b36;
}

.edit-modal-header h3 i {
    margin-right: 6px;
}

.edit-modal-header small {
    color: #777;
    display: block;
    margin-top: 5px;
}

.edit-modal-close {
    flex-shrink: 0;
    border: none;
    background: transparent;
    font-size: 28px;
    cursor: pointer;
    line-height: 1;
    color: #555;
    padding: 4px 8px;
}

.edit-modal-close:hover {
    color: #222;
}

.edit-modal-body {
    padding: 20px;
}

.edit-field {
    margin-bottom: 16px;
}

.edit-field:last-child {
    margin-bottom: 0;
}

.edit-field label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #334155;
    font-size: 13px;
}

.edit-field input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-sizing: border-box;
    font-family: inherit;
    font-size: 14px;
    outline: none;
}

.edit-field input:focus {
    border-color: #8aa95a;
    box-shadow: 0 0 0 3px rgba(138, 169, 90, 0.12);
}

/* Footer */
.edit-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
}

.edit-modal-footer button {
    min-height: 38px;
}

.edit-modal-footer button:disabled {
    opacity: 0.65;
    cursor: wait;
}

/* Animations */
@keyframes editFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes editSlideUp {
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
@media (max-width: 600px) {
    .edit-modal {
        padding: 12px;
    }

    .edit-modal-content {
        max-height: calc(100vh - 24px);
        border-radius: 12px;
    }

    .edit-modal-header,
    .edit-modal-body {
        padding: 15px;
    }

    .edit-modal-footer {
        padding: 12px 15px;
    }

    .edit-modal-footer button {
        flex: 1;
    }
}

@media (prefers-reduced-motion: reduce) {
    .edit-modal.active,
    .edit-modal-content {
        animation: none;
    }
}
</style>


<script>
function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const item = document.getElementById('itemFilter').value;

    const search = document
        .getElementById('searchFilter')
        .value
        .toLowerCase()
        .trim();

    const rows = document.querySelectorAll('.loan-row');
    let visibleCount = 0;

    rows.forEach(function (row) {
        const rowStatus = row.dataset.status || '';
        const rowItem = row.dataset.item || '';
        const rowSearch = row.dataset.search || '';

        const statusMatch =
            status === 'all' || rowStatus === status;

        const itemMatch =
            item === 'all' || rowItem === item;

        const searchMatch =
            rowSearch.includes(search);

        const show =
            statusMatch && itemMatch && searchMatch;

        row.style.display = show ? '' : 'none';

        if (show) {
            visibleCount++;
        }
    });

    const noResults = document.getElementById('noLoanResults');

    if (noResults) {
        noResults.style.display =
            visibleCount === 0 ? '' : 'none';
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

const urlStatus =
    new URLSearchParams(window.location.search).get('status');

if (
    urlStatus &&
    ['pending', 'process', 'selesai', 'ditolak'].includes(urlStatus)
) {
    document.getElementById('statusFilter').value = urlStatus;
}

applyFilters();


function openEditModal(
    loanId,
    userName,
    hostname,
    type,
    sn,
    status
) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editLoanForm');

    document.getElementById('editUserName').textContent =
        'Borrower: ' + (userName || '-');

    document.getElementById('editHostname').value =
        hostname || '';

    document.getElementById('editType').value =
        type || '';

    document.getElementById('editSn').value =
        sn || '';

    form.action = `/admin/loans/${loanId}`;

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    setTimeout(function () {
        document.getElementById('editHostname').focus();
    }, 100);
}

function closeEditModal() {
    const modal = document.getElementById('editModal');

    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.getElementById('editModal').addEventListener(
    'click',
    function (event) {
        if (event.target === this) {
            closeEditModal();
        }
    }
);

document.addEventListener('keydown', function (event) {
    const modal = document.getElementById('editModal');

    if (event.key === 'Escape' && modal.classList.contains('active')) {
        closeEditModal();
    }
});

document.getElementById('editLoanForm').addEventListener(
    'submit',
    function () {
        const button = document.getElementById('saveLoanButton');

        button.disabled = true;
        button.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }
);
</script>

@endsection
