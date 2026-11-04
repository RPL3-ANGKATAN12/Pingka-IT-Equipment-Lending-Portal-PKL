@extends('layouts.app')

@section('title', 'Item Portal - Loan Data')

@section('content')

<section class="page-header">
    <div class="page-header-container">
        <h1>Loan Data</h1>
        <p>Item loan data</p>
        <div class="stats-row">
            <div class="stat-card">
                <span class="stat-number">{{ $loans->count() }}</span>
                <span class="stat-label">Total Loans</span>
            </div>

            <div class="stat-card">
                <span class="stat-number">{{ $loans->where('status', 'pending')->count() }}</span>
                <span class="stat-label">Pending</span>
            </div>

            <div class="stat-card">
                <span class="stat-number">{{ $loans->where('status', 'process')->count() }}</span>
                <span class="stat-label">Process</span>
            </div>

            <div class="stat-card">
                <span class="stat-number">{{ $loans->where('status', 'selesai')->count() }}</span>
                <span class="stat-label">Ready for Pickup</span>
            </div>

            <div class="stat-card">
                <span class="stat-number">{{ $loans->where('status', 'borrowed')->count() }}</span>
                <span class="stat-label">Completed</span>
            </div>

            <div class="stat-card">
                <span class="stat-number">{{ $loans->where('status', 'ditolak')->count() }}</span>
                <span class="stat-label">Rejected</span>
            </div>
        </div>
    </div>
</section>

<section class="content-section">
    <div class="content-container">
        <div class="filter-bar">
            <div class="filter-group">
                <select id="statusFilter" class="filter-input" onchange="applyFilters()">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="process">Process</option>
                    <option value="selesai">Please pick up your item</option>
                    <option value="borrowed">Completed</option>
                    <option value="ditolak">Rejected</option>
                </select>
                <select id="itemFilter" class="filter-input" onchange="applyFilters()">
                    <option value="all">All Items</option>
                    @foreach($items as $item)
                        <option value="{{ strtolower($item->name) }}">
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
                <input type="text" id="searchFilter" class="filter-input" placeholder="Search name/department/hostname..." autocomplete="off" oninput="applyFilters()">
            </div>
            <button type="button" class="btn-reset" onclick="resetFilters()">
                <i class="fas fa-undo-alt"></i>Reset
            </button>
        </div>

        <div class="table-wrapper loan-table-wrapper">
            <div class="table-scroll loan-table-scroll">
                <table class="data-table loan-data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Name</th>
                            <th>Item</th>
                            <th>Hostname</th>
                            <th>Type</th>
                            <th>SN</th>
                            <th>Others</th>
                            <th>Start Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="loanTable">
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination" id="pagination"></div>
    </div>
</section>

@push('styles')
<style>
    .loan-table-scroll {
        width: 100%;
        overflow-x: auto;
    }

    .loan-data-table {
        width: 100%;
        min-width: 860px;
        table-layout: fixed;
    }

    .loan-data-table th,
    .loan-data-table td {
        padding: 12px 10px;
        white-space: normal;
        overflow-wrap: anywhere;
        vertical-align: middle;
    }

    .loan-data-table th:nth-child(1),
    .loan-data-table td:nth-child(1) { width: 4%; }
    .loan-data-table th:nth-child(2),
    .loan-data-table td:nth-child(2) { width: 16%; }
    .loan-data-table th:nth-child(3),
    .loan-data-table td:nth-child(3) { width: 14%; }
    .loan-data-table th:nth-child(4),
    .loan-data-table td:nth-child(4) { width: 12%; }
    .loan-data-table th:nth-child(5),
    .loan-data-table td:nth-child(5) { width: 12%; }
    .loan-data-table th:nth-child(6),
    .loan-data-table td:nth-child(6) { width: 11%; }
    .loan-data-table th:nth-child(7),
    .loan-data-table td:nth-child(7) { width: 8%; }
    .loan-data-table th:nth-child(8),
    .loan-data-table td:nth-child(8) { width: 10%; }
    .loan-data-table th:nth-child(9),
    .loan-data-table td:nth-child(9) { width: 13%; }

    .loan-data-table td {
        max-width: none;
        overflow: visible;
        text-overflow: clip;
    }

    .loan-data-table .name-cell,
    .loan-data-table .department-cell {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        line-height: 1.4;
    }

    .loan-data-table .department-cell {
        margin-top: 4px;
        color: var(--text-muted);
        font-size: 0.78rem;
    }

    .loan-data-table .name-cell i,
    .loan-data-table .department-cell i {
        flex: 0 0 14px;
        margin-top: 3px;
        text-align: center;
    }

    .loan-data-table .status-badge {
        display: flex;
        justify-content: flex-start;
    }

    .loan-data-table .badge {
        max-width: 100%;
        white-space: normal;
        text-align: center;
        line-height: 1.35;
    }

    .loan-data-table .detail-button {
        margin-top: 6px;
    }

    .loan-data-table .loan-data-unavailable {
        color: var(--text-muted);
        font-size: 0.78rem;
    }

    @media (max-width: 768px) {
        .loan-data-table {
            min-width: 860px;
        }

        .loan-data-table th,
        .loan-data-table td {
            padding: 10px 8px;
            font-size: 0.78rem;
        }
    }
</style>
@endpush

<div id="detailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <i class="fas fa-file-alt"></i>Loan Details
            </h3>
            <button type="button" class="close-modal" onclick="closeDetail()">
                &times;
            </button>
        </div>
        <div class="modal-body" id="detailContent"
        ></div>
    </div>
</div>

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

@php
    $loanData = $loans->map(function ($loan) {
        return [
            'id' =>
                $loan->id,
            'name' =>
                $loan->name,
            'departement' =>
                $loan->departement,
            'item' =>
                $loan->item->name ?? null,
            'hostname' =>
                $loan->hostname,
            'type' =>
                $loan->type,
            'sn' =>
                $loan->sn,
            'others' =>
                $loan->others,
            'start_date' =>
                $loan->start_date
                    ? $loan->start_date->format('d/m/Y')
                    : null,
            'status' =>
                $loan->status,
        ];
    })->values();
@endphp

<script>
const loans = @json($loanData);
const itemsPerPage = 10;
let currentPage = 1;
let filteredLoans = [...loans];

function escapeHtml(text) {
    if (text === null || text === undefined) {
        return '';
    }
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function getDeviceInfo(value) {
    if (String(value || '').trim() !== '') {
        return escapeHtml(value);
    }

    return `<span class="loan-data-unavailable">N/A</span>`;
}


function getStatusBadge(status) {
    if (status === 'pending') {
        return `<span class="badge badge-warning">Pending</span>`;
    }
    if (status === 'process') {
        return `<span class="badge badge-info">Process</span>`;
    }
    if (status === 'selesai') {
        return `<span class="badge badge-warning">Please pick up your item</span>`;
    }
    if (status === 'borrowed') {
        return `<span class="badge badge-success">Completed</span>`;
    }
    if (status === 'ditolak') {
        return `<span class="badge badge-danger">Rejected</span>`;
    }
    return `<span class="badge badge-danger">${escapeHtml(status || '-')}</span>`;
}


function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const item = document.getElementById('itemFilter').value;
    const search = document.getElementById('searchFilter').value.toLowerCase().trim();

    filteredLoans = loans.filter(loan => {const loanItem = loan.item
        ? loan.item.toLowerCase()
        : '';
        const loanSearch = (
            (loan.name || '') + ' ' +
            (loan.departement || '') + ' ' +
            (loan.item || '') + ' ' +
            (loan.hostname || '') + ' ' +
            (loan.type || '') + ' ' +
            (loan.sn || '') + ' ' +
            (loan.others || '')
        ).toLowerCase();
        const statusMatch = status === 'all' || loan.status === status;
        const itemMatch = item === 'all' || loanItem === item;
        const searchMatch = !search || loanSearch.includes(search);
        return (statusMatch && itemMatch && searchMatch);
    });
    currentPage = 1;
    renderTable();
}


function renderTable() {
    const tbody = document.getElementById(
        'loanTable'
    );
    if (!filteredLoans.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No data matches the filter.</p>
                </td>
            </tr>
        `;
        renderPagination();
        return;
    }
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const pageData = filteredLoans.slice(start, end);
    tbody.innerHTML = pageData.map(
        (loan, index) => {
            return `
                <tr class="loan-row">
                    <td>
                        <strong>${start + index + 1}</strong>
                    </td>
                    <td>
                        <div class="name-cell">
                            <i class="fas fa-user"></i>
                            ${escapeHtml(loan.name || '-')}
                        </div>
                        <div class="department-cell">
                            <i class="fas fa-building"></i>
                            ${escapeHtml(loan.departement || '-')}
                        </div>
                    </td>
                    <td>
                        ${escapeHtml(loan.item || '-')}
                    </td>
                    <td>
                        ${getDeviceInfo(loan.hostname)}
                    </td>
                    <td>
                        ${getDeviceInfo(loan.type)}
                    </td>
                    <td>
                        ${getDeviceInfo(loan.sn)}
                    </td>
                    <td>
                        ${String(loan.others || '').trim()
                            ? `<button type="button" class="btn-view-others"
                                onclick="openOthersModal(${Number(loan.id)})">
                                <i class="fas fa-eye"></i> View
                            </button>`
                            : '-'}
                    </td>
                    <td>
                        ${escapeHtml(loan.start_date || '-')}
                    </td>
                    <td>
                        <div class="status-badge">
                            ${getStatusBadge(loan.status)}
                        </div>
                        <div class="detail-button">
                            <button type="button" class="btn-detail-table"
                                onclick="openDetail(
                                    ${Number(loan.id)}
                                )">
                                <i class="fas fa-eye"></i>
                                Detail
                            </button>
                        </div>
                    </td>
                </tr>
                `;
            }
        ).join('');
    renderPagination();
}


function renderPagination() {
    const pagination = document.getElementById(
        'pagination'
    );
    const totalPages = Math.ceil(
        filteredLoans.length /
        itemsPerPage
    );
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    let html = '';
    html += `
        <button
            type="button"
            class="page-btn"
            onclick="changePage(
                ${currentPage - 1}
            )"
            ${currentPage === 1
                ? 'disabled'
                : ''
            }
        >
            «
        </button>
    `;
    for (
        let i = 1;
        i <= totalPages;
        i++
    ) {
        html += `
            <button
                type="button"
                class="page-btn ${
                    i === currentPage
                        ? 'active'
                        : ''
                }"
                onclick="changePage(${i})"
            >
                ${i}
            </button>
        `;
    }
    html += `
        <button
            type="button"
            class="page-btn"
            onclick="changePage(
                ${currentPage + 1}
            )"
            ${
                currentPage === totalPages
                    ? 'disabled'
                    : ''
            }
        >
            »
        </button>
    `;
    pagination.innerHTML =
        html;
}


function changePage(page) {
    const totalPages = Math.ceil(
        filteredLoans.length /
        itemsPerPage
    );
    if (page < 1 || page > totalPages) {
        return;
    }
    currentPage = page;
    renderTable();
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}


function resetFilters() {
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('itemFilter').value = 'all';
    document.getElementById('searchFilter').value = '';
    filteredLoans =
        [...loans];
    currentPage = 1;
    renderTable();
}


function openDetail(loanId) {
    const loan = loans.find(
        data =>
        Number(data.id) ===
        Number(loanId)
    );
    if (!loan) {
        alert('Loan data not found.');
        return;
    }
    const detailContent = document.getElementById('detailContent');
    detailContent.innerHTML = `
        <div class="detail-group">
            <label>
                <i class="fas fa-user"></i>
                Name
            </label>
            <p>
                <strong>
                    ${escapeHtml(
                        loan.name || '-'
                    )}
                </strong>
            </p>
        </div>
        <div class="detail-group">
            <label>
                <i class="fas fa-building"></i>
                Department
            </label>
            <p>
                ${escapeHtml(
                    loan.departement || '-'
                )}
            </p>
        </div>
        <div class="detail-group">
            <label>
                <i class="fas fa-box"></i>
                Item
            </label>
            <p>
                ${escapeHtml(
                    loan.item || '-'
                )}
            </p>
        </div>
        <div class="detail-group">
            <label>
                <i class="fas fa-desktop"></i>
                Hostname
            </label>
            <p>
                ${getDeviceInfo(loan.hostname)}
            </p>
        </div>
        <div class="detail-group">
            <label>
                <i class="fas fa-laptop"></i>
                Type
            </label>
            <p>
                ${getDeviceInfo(loan.type)}
            </p>
        </div>
        <div class="detail-group">
            <label>
                <i class="fas fa-barcode"></i>
                Serial Number
            </label>
            <p>
                ${getDeviceInfo(loan.sn)}
            </p>
        </div>
        <div class="detail-group">
            <label>
                <i class="fas fa-comment"></i>
                Description / Others
            </label>
            <p>
                ${escapeHtml(
                    loan.others || '-'
                )}
            </p>
        </div>
        <div class="detail-group">
            <label>
                <i class="fas fa-calendar-alt"></i>
                Start Date
            </label>
            <p>
                ${escapeHtml(
                    loan.start_date || '-'
                )}
            </p>
        </div>
        <div class="detail-group">
            <label>
                <i class="fas fa-info-circle"></i>
                Status
            </label>
            <p>
                ${getStatusBadge(
                    loan.status
                )}
            </p>
        </div>
    `;

    document.getElementById('detailModal').style.display = 'flex';
    document.body.style.overflow ='hidden';
}

function closeDetail() {
    document.getElementById('detailModal').style.display = 'none';
    restoreBodyScrollIfNoModal();
}

function openOthersModal(loanId) {
    const loan = loans.find(item => Number(item.id) === Number(loanId));
    if (!loan) {
        return;
    }

    document.getElementById('othersContent').textContent = loan.others || '-';
    document.getElementById('othersBorrower').textContent =
        'Borrower: ' + (loan.name || '-');

    const modal = document.getElementById('othersModal');
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

function restoreBodyScrollIfNoModal() {
    const detailOpen =
        document.getElementById('detailModal').style.display === 'flex';
    const othersOpen =
        document.getElementById('othersModal').classList.contains('show');

    if (!detailOpen && !othersOpen) {
        document.body.style.overflow = '';
    }
}

window.addEventListener('click',
    function(event) {
        const modal = document.getElementById('detailModal');
        if (event.target === modal) {
            closeDetail();
        }
    }
);

window.addEventListener('click', function(event) {
    const modal = document.getElementById('othersModal');
    if (event.target === modal) {
        closeOthersModal();
    }
});

document.addEventListener('keydown',
    function(event) {
        if (event.key === 'Escape') {
            closeDetail();
            closeOthersModal();
        }
    }
);


document.addEventListener('DOMContentLoaded',
    function() {
        renderTable();
    }
);
</script>
@endsection