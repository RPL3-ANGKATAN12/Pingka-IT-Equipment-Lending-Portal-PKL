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
            <p><i class="fas fa-check-circle"></i> Done</p>
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
                <option value="selesai">Done</option>
            </select>

            <select id="itemFilter" onchange="applyFilters()" style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:white;">
                <option value="all">All Items</option>

                @foreach($items as $item)
                    <option value="{{ strtolower($item->name) }}">
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>

            <input type="text" id="searchFilter" placeholder="🔍 Search name/department/hostname..." oninput="applyFilters()" autocomplete="off" style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; min-width:260px;">

            <button type="button" class="btn-outline" onclick="resetFilters()">
                <i class="fas fa-undo"></i> Reset
            </button>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
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
                            <td> {{ $no + 1 }} </td>
                            <td> <strong>{{ $loan->name }}</strong> </td>
                            <td> {{ $loan->email ?? '-' }} </td>
                            <td> {{ $loan->departement }} </td>
                            <td> {{ $loan->item->name ?? '-' }} </td>
                            <td> {{ $loan->hostname ?? '-' }} </td>
                            <td> {{ $loan->type ?? '-' }} </td>
                            <td> {{ $loan->sn ?? '-' }} </td>
                            <td> {{ $loan->others ?? '-' }} </td>
                            <td> {{ $loan->start_date?->format('d/m/Y') ?? '-' }} </td>
                            <td>
                                @if($loan->status === 'process')

                                    <span class="badge badge-info">
                                        Process
                                    </span>

                                @elseif($loan->status === 'selesai')

                                    <span class="badge badge-success">
                                        Completed
                                    </span>
                                @else

                                    <span class="badge badge-secondary">
                                        {{ ucfirst($loan->status) }}
                                    </span>

                                @endif
                            </td>

                            <td>
                                <div style="display:flex; gap:7px; align-items:center; flex-wrap:wrap;">

                                    <form
                                        action="{{ route('admin.loans.update-status', $loan) }}"
                                        method="POST"
                                    >
                                        @csrf
                                        @method('PUT')

                                        <select
                                            name="status"
                                            onchange="this.form.submit()"
                                            style="padding:7px 9px; border:1px solid #ddd; border-radius:7px; background:white; cursor:pointer;"
                                        >
                                            <option
                                                value="process"
                                                {{ $loan->status === 'process' ? 'selected' : '' }}
                                            >
                                                Process
                                            </option>

                                            <option
                                                value="selesai"
                                                {{ $loan->status === 'selesai' ? 'selected' : '' }}
                                            >
                                                Completed
                                            </option>
                                        </select>
                                    </form>

                                    <button
                                        type="button"
                                        class="btn-table btn-warning"
                                        onclick="openEditModal(
                                            {{ $loan->id }},
                                            @js($loan->name),
                                            @js($loan->hostname),
                                            @js($loan->type),
                                            @js($loan->sn)
                                        )"
                                        title="Edit loan data"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="12" class="empty-state">
                                <i class="fas fa-inbox"></i>

                                <p>
                                    No loan data yet.
                                </p>
                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>
        </div>
    </div>
</div>


{{-- EDIT MODAL --}}
<div id="editModal" class="edit-modal">

    <div class="edit-modal-content">

        <div class="edit-modal-header">

            <div>
                <h3>
                    <i class="fas fa-edit"></i>
                    Edit Loan Data
                </h3>

                <small id="editUserName"></small>
            </div>

            <button
                type="button"
                onclick="closeEditModal()"
                class="edit-modal-close"
            >
                &times;
            </button>

        </div>

        <form id="editLoanForm" method="POST">

            @csrf
            @method('PUT')

            <div class="edit-modal-body">

                <div class="edit-field">

                    <label for="editHostname">
                        Hostname
                    </label>

                    <input
                        type="text"
                        id="editHostname"
                        name="hostname"
                        maxlength="100"
                        placeholder="Enter hostname"
                    >

                </div>


                <div class="edit-field">

                    <label for="editType">
                        Type
                    </label>

                    <input
                        type="text"
                        id="editType"
                        name="type"
                        maxlength="50"
                        placeholder="Example: Laptop / PC"
                    >

                </div>


                <div class="edit-field">

                    <label for="editSn">
                        Serial Number (SN)
                    </label>

                    <input
                        type="text"
                        id="editSn"
                        name="sn"
                        maxlength="100"
                        placeholder="Enter serial number"
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

                <button
                    type="submit"
                    class="btn-table btn-success"
                >
                    <i class="fas fa-save"></i>
                    Save
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
    background: rgba(0, 0, 0, 0.5);
    z-index: 999999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

.edit-modal-content {
    width: 100%;
    max-width: 500px;
    max-height: calc(100vh - 40px);
    background: #ffffff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;
}

.edit-modal-header {
    padding: 18px 20px;
    border-bottom: 1px solid #eeeeee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.edit-modal-header h3 {
    margin: 0;
}

.edit-modal-header h3 i {
    margin-right: 6px;
}

.edit-modal-header small {
    color: #777777;
    display: block;
    margin-top: 4px;
}

.edit-modal-close {
    border: none;
    background: transparent;
    font-size: 28px;
    cursor: pointer;
    line-height: 1;
    color: #555555;
    padding: 4px 8px;
}

.edit-modal-close:hover {
    color: #222222;
}

.edit-modal-body {
    padding: 20px;
    overflow-y: auto;
}

.edit-field {
    margin-bottom: 15px;
}

.edit-field:last-child {
    margin-bottom: 0;
}

.edit-field label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
}

.edit-field input,
.edit-field textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #dddddd;
    border-radius: 8px;
    box-sizing: border-box;
    font-family: inherit;
    font-size: 14px;
    outline: none;
}

.edit-field input:focus,
.edit-field textarea:focus {
    border-color: #8aa95a;
    box-shadow: 0 0 0 2px rgba(138, 169, 90, 0.12);
}

.edit-field textarea {
    resize: vertical;
    min-height: 90px;
}

.edit-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #eeeeee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-shrink: 0;
}

@media (max-width: 600px) {

    .edit-modal {
        padding: 12px;
    }

    .edit-modal-content {
        max-width: 100%;
        max-height: calc(100vh - 24px);
        border-radius: 12px;
    }

    .edit-modal-header {
        padding: 15px;
    }

    .edit-modal-body {
        padding: 15px;
    }

    .edit-modal-footer {
        padding: 12px 15px;
    }

}

@media (max-height: 600px) {

    .edit-modal {
        align-items: flex-start;
        overflow-y: auto;
        padding-top: 15px;
        padding-bottom: 15px;
    }

    .edit-modal-content {
        max-height: none;
    }

}

</style>


<script>

function applyFilters() {

    const status =
        document.getElementById('statusFilter').value;

    const item =
        document.getElementById('itemFilter').value;

    const search =
        document.getElementById('searchFilter')
            .value
            .toLowerCase()
            .trim();

    document
        .querySelectorAll('.loan-row')
        .forEach(row => {

            const rowStatus =
                row.dataset.status || '';

            const rowItem =
                row.dataset.item || '';

            const rowSearch =
                row.dataset.search || '';

            const statusMatch =
                status === 'all' ||
                rowStatus === status;

            const itemMatch =
                item === 'all' ||
                rowItem === item;

            const searchMatch =
                !search ||
                rowSearch.includes(search);

            row.style.display =
                statusMatch &&
                itemMatch &&
                searchMatch
                    ? ''
                    : 'none';

        });
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
    [
        'pending',
        'process',
        'selesai'
    ].includes(urlStatus)
) {

    document.getElementById('statusFilter').value =
        urlStatus;

}

applyFilters();


function openEditModal(
    loanId,
    userName,
    hostname,
    type,
    sn
) {

    document.getElementById('editUserName').textContent =
        'Peminjam: ' + userName;

    document.getElementById('editHostname').value =
        hostname || '';

    document.getElementById('editType').value =
        type || '';

    document.getElementById('editSn').value =
        sn || '';

    document.getElementById('editLoanForm').action =
        `/admin/loans/${loanId}`;

    document.getElementById('editModal').style.display =
        'flex';

    setTimeout(() => {

        document
            .getElementById('editHostname')
            .focus();

    }, 100);

}


function closeEditModal() {

    document.getElementById('editModal').style.display =
        'none';

}


document
    .getElementById('editModal')
    .addEventListener(
        'click',
        function(event) {

            if (event.target === this) {

                closeEditModal();

            }

        }
    );


let currentLoanId = null;



document.addEventListener(
    'keydown',
    function(event) {

        if (event.key !== 'Escape') {

            return;

        }

        const editModal =
            document.getElementById('editModal');

        if (
            editModal &&
            editModal.style.display === 'flex'
        ) {

            closeEditModal();

        }

    }
);

</script>  

@endsection