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

            <div class= "stat-card">
                <span class="stat-number">{{ $loans->where('status', 'pending')->count() }}</span>
                <span class="stat-label">Pending</span>
            </div>

            <div class="stat-card">
                <span class="stat-number">{{ $loans->where('status', 'process')->count() }}</span>
                <span class="stat-label">Process</span>
            </div>

            <div class="stat-card">
                <span class="stat-number">{{ $loans->where('status', 'selesai')->count() }}</span>
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
                    <option value="selesai">Completed</option>
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

        <div class="table-wrapper">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Name</th>
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
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination" id="pagination"></div>
    </div>
</section>

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


function getStatusBadge(status) {
    if (status === 'pending') {
        return `<span class="badge badge-warning">Pending</span>`;
    }
    if (status === 'process') {
        return `<span class="badge badge-info">Process</span>`;
    }
    if (status === 'selesai') {
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
                <td colspan="11" class="empty-state">
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
                        <strong>${escapeHtml(loan.name || '-')}</strong>
                    </td>
                    <td>
                        ${escapeHtml(loan.departement || '-')}
                    </td>
                    <td>
                        ${escapeHtml(loan.item || '-')}
                    </td>
                    <td>
                        ${escapeHtml(loan.hostname || '-')}
                    </td>
                    <td>
                        ${escapeHtml(loan.type || '-')}
                    </td>
                    <td>
                        ${escapeHtml(loan.sn || '-')}
                    </td>
                    <td title="${escapeHtml(loan.others || '-')}">
                        ${escapeHtml( loan.others || '-')}
                    </td>
                    <td>
                        ${escapeHtml(loan.start_date || '-')}
                    </td>
                    <td>
                        ${getStatusBadge(loan.status)}
                    </td>
                    <td>
                        <button type="button" class="btn-detail-table"
                            onclick="openDetail(
                                ${Number(loan.id)}
                            )">
                            <i class="fas fa-eye"></i>
                            Detail
                        </button>
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
                ${escapeHtml(
                    loan.hostname || '-'
                )}
            </p>
        </div>
        <div class="detail-group">
            <label>
                <i class="fas fa-laptop"></i>
                Type
            </label>
            <p>
                ${escapeHtml(
                    loan.type || '-'
                )}
            </p>
        </div>
        <div class="detail-group">
            <label>
                <i class="fas fa-barcode"></i>
                Serial Number
            </label>
            <p>
                ${escapeHtml(
                    loan.sn || '-'
                )}
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
    document.body.style.overflow ='';
}

window.addEventListener('click',
    function(event) {
        const modal = document.getElementById('detailModal');
        if (event.target === modal
        ) {
            closeDetail();
        }
    }
);


document.addEventListener('keydown',
    function(event) {
        if (
            event.key === 'Escape'
        ) {
            closeDetail();
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