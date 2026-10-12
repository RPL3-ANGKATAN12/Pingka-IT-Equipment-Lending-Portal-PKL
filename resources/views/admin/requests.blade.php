@extends('layouts.app')

@section('title', 'Loan Request')

@section('content')

<div class="dashboard">
<h2 class="page-title">
    <i class="fas fa-clock"></i>
    Loan Request
</h2>

<div class="dashboard-card">
    <h3>
        <i class="fas fa-hourglass-half"></i>
        Waiting for Approval
        <span class="badge badge-warning" style="margin-left:10px;">
            {{ $pendingRequests->count() }}
        </span>
    </h3>

    @if($pendingRequests->count())
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
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
                            <td>{{ $no + 1 }}</td>
                            <td><strong>{{ $loan->name }}</strong></td>
                            <td>{{ $loan->departement }}</td>
                            <td>{{ $loan->item->name ?? '-' }}</td>
                            <td>{{ $loan->start_date?->format('d/m/Y') ?? '-' }}</td>

                            <td>
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">

                                    <button
                                        type="button"
                                        class="btn-table btn-success"
                                        onclick="openConfirmModal(
                                            'approve',
                                            '{{ route('admin.requests.approve', $loan) }}',
                                            '{{ addslashes($loan->name) }}',
                                            '{{ addslashes($loan->item->name ?? '-') }}'
                                        )">
                                        <i class="fas fa-check"></i>
                                        Approve
                                    </button>

                                    <button
                                        type="button"
                                        class="btn-table btn-danger"
                                        onclick="openConfirmModal(
                                            'reject',
                                            '{{ route('admin.requests.reject', $loan) }}',
                                            '{{ addslashes($loan->name) }}',
                                            '{{ addslashes($loan->item->name ?? '-') }}'
                                        )">
                                        <i class="fas fa-times"></i>
                                        Reject
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <p>There are no requests waiting for approval.</p>
        </div>
    @endif
</div>


<div class="dashboard-card">
    <h3>
        <i class="fas fa-history"></i>
        Request History

        <span class="badge" style="background:var(--gray-light); margin-left:10px;">
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

                <tbody>
                    @foreach($historyRequests as $loan)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $loan->name }}</td>
                            <td>{{ $loan->departement }}</td>
                            <td>{{ $loan->item->name ?? '-' }}</td>

                            <td>
                                @if($loan->status === 'approved')
                                    <span class="badge badge-success">
                                        Disetujui
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
                                {{ $loan->created_at?->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @endforeach
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

<div id="confirmModal" class="confirm-modal">
    <div class="confirm-modal-overlay" onclick="closeConfirmModal()"></div>

<div class="confirm-modal-box">

    <button type="button" class="confirm-modal-close" onclick="closeConfirmModal()">
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

    <form id="confirmModalForm" method="POST">
        @csrf

        <div class="confirm-modal-actions">

            <button
                type="button"
                class="confirm-modal-cancel"
                onclick="closeConfirmModal()">
                Cancel
            </button>

            <button
                type="submit"
                id="confirmModalSubmit"
                class="confirm-modal-submit">
                Confirm
            </button>

        </div>
    </form>

</div>

</div>

<style>
    .confirm-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .confirm-modal.active {
        display: flex;
    }

    .confirm-modal-overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(4px);
    }

    .confirm-modal-box {
        position: relative;
        z-index: 2;
        width: min(420px, calc(100% - 32px));
        padding: 30px;
        background: #fff;
        border-radius: 18px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
        animation: modalShow .2s ease;
    }

    .confirm-modal-close {
        position: absolute;
        top: 12px;
        right: 15px;
        width: 32px;
        height: 32px;
        border: 0;
        background: transparent;
        font-size: 25px;
        color: #94a3b8;
        cursor: pointer;
        border-radius: 50%;
    }

    .confirm-modal-close:hover {
        background: #f1f5f9;
        color: #475569;
    }

    .confirm-modal-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 25px;
    }

    .confirm-modal-icon.approve {
        background: #dcfce7;
        color: #16a34a;
    }

    .confirm-modal-icon.reject {
        background: #fee2e2;
        color: #dc2626;
    }

    .confirm-modal-box h3 {
        margin: 0 0 10px;
        font-size: 20px;
        color: #1e293b;
    }

    .confirm-modal-box p {
        margin: 0;
        line-height: 1.6;
        color: #64748b;
    }

    .confirm-modal-box strong {
        color: #334155;
    }

    .confirm-modal-actions {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 25px;
    }

    .confirm-modal-cancel,
    .confirm-modal-submit {
        border: 0;
        border-radius: 9px;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: .2s ease;
    }

    .confirm-modal-cancel {
        background: #f1f5f9;
        color: #475569;
    }

    .confirm-modal-cancel:hover {
        background: #e2e8f0;
    }

    .confirm-modal-submit {
        background: #16a34a;
        color: #fff;
    }

    .confirm-modal-submit:hover {
        opacity: .9;
        transform: translateY(-1px);
    }

    .confirm-modal-submit.reject {
        background: #dc2626;
    }

    @keyframes modalShow {
        from {
            opacity: 0;
            transform: scale(.95) translateY(8px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    body.modal-open {
        overflow: hidden;
    }
</style>

<script>
    function openConfirmModal(type, action, name, item) {
        const modal = document.getElementById('confirmModal');
        const form = document.getElementById('confirmModalForm');
        const icon = document.getElementById('confirmModalIcon');
        const title = document.getElementById('confirmModalTitle');
        const text = document.getElementById('confirmModalText');
        const submit = document.getElementById('confirmModalSubmit');

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

    function closeConfirmModal() {
        document.getElementById('confirmModal').classList.remove('active');
        document.body.classList.remove('modal-open');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeConfirmModal();
        }
    });
</script>

@endsection
